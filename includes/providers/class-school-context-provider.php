<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Guardian-safe class schedule and teacher directory from OLAMA School.
 */
class Olama_Student_Gateway_School_Context_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'school_context';
    }

    public function is_available() {
        return class_exists('Olama_School_Section')
            && class_exists('Olama_School_Schedule')
            && class_exists('Olama_School_Teacher')
            && class_exists('Olama_School_Subject');
    }

    public function get_data(array $context, array $args = array()) {
        $scope = $this->resolve_scope($context);
        if (is_wp_error($scope)) {
            return $scope;
        }

        $section_id = $scope['section_id'];
        $academic_year_id = $scope['academic_year_id'];
        $semester_id = $scope['semester_id'];
        $base = $scope;

        $resource = isset($args['resource']) ? sanitize_key($args['resource']) : 'schedule';
        if ('teachers' === $resource) {
            $base['teachers'] = $this->teachers($section_id, $academic_year_id, $semester_id);
            return $base;
        }

        $base['days'] = $this->schedule($section_id, $semester_id);
        return $base;
    }

    public function resolve_scope(array $context) {
        $student = isset($context['student']) && is_array($context['student']) ? $context['student'] : null;
        if (!$student || empty($student['academic']) || !is_array($student['academic'])) {
            return new WP_Error(
                'olama_gateway_academic_assignment_missing',
                __('The student does not have an academic assignment for the active year.', 'olama-student-gateway')
            );
        }

        $academic_context = isset($context['academic']) ? (array) $context['academic'] : array();
        $academic_year_id = !empty($academic_context['academic_year_id'])
            ? absint($academic_context['academic_year_id'])
            : 0;
        $semester_id = !empty($academic_context['semester_id'])
            ? absint($academic_context['semester_id'])
            : 0;
        $section_id = $this->resolve_school_section_id($student['academic']);

        if (!$section_id || !$academic_year_id || !$semester_id) {
            return new WP_Error(
                'olama_gateway_school_context_missing',
                __('The student section or active semester is not mapped to OLAMA School.', 'olama-student-gateway')
            );
        }

        $section = Olama_School_Section::get_section($section_id);
        return array(
            'academic_year_id' => $academic_year_id,
            'semester_id' => $semester_id,
            'semester_name' => sanitize_text_field(isset($academic_context['semester_name']) ? $academic_context['semester_name'] : ''),
            'section_id' => $section_id,
            'grade_id' => $section && isset($section->grade_id) ? absint($section->grade_id) : 0,
            'section_name' => sanitize_text_field($section && isset($section->section_name) ? $section->section_name : ''),
            'grade_name' => sanitize_text_field($section && isset($section->grade_name) ? $section->grade_name : ''),
        );
    }

    private function schedule($section_id, $semester_id) {
        $schedule = (array) Olama_School_Schedule::get_schedule($section_id, $semester_id, 'normal');
        $by_day = array();
        foreach ($schedule as $day => $periods) {
            $by_day[strtolower((string) $day)] = (array) $periods;
        }

        $days = array();
        foreach ($this->day_labels() as $key => $label) {
            $lessons = array();
            $periods = isset($by_day[strtolower($key)]) ? $by_day[strtolower($key)] : array();
            ksort($periods, SORT_NUMERIC);
            foreach ($periods as $period => $lesson) {
                $color = sanitize_hex_color($this->value($lesson, 'color_code'));
                $lessons[] = array(
                    'period' => absint($this->value($lesson, 'period_number') ?: $period),
                    'subject_id' => absint($this->value($lesson, 'subject_id')),
                    'subject' => sanitize_text_field($this->value($lesson, 'subject_name')),
                    'color' => $color ?: '#1f7ac0',
                );
            }
            $days[] = array('key' => $key, 'label' => $label, 'lessons' => $lessons);
        }
        return $days;
    }

    private function teachers($section_id, $academic_year_id, $semester_id) {
        $assigned = (array) Olama_School_Teacher::get_teachers_for_section($section_id, $academic_year_id);
        $active = array();
        foreach ((array) Olama_School_Teacher::get_teachers() as $teacher) {
            if (!empty($teacher->ID)) {
                $active[(int) $teacher->ID] = $teacher;
            }
        }

        $teachers = array();
        foreach ($assigned as $assigned_teacher) {
            $teacher_id = !empty($assigned_teacher->ID) ? absint($assigned_teacher->ID) : 0;
            if (!$teacher_id || empty($active[$teacher_id])) {
                continue;
            }

            $subjects = array();
            foreach ((array) Olama_School_Teacher::get_assigned_subjects($teacher_id, $section_id, $academic_year_id) as $subject_id) {
                $subject = Olama_School_Subject::get_subject(absint($subject_id));
                if (!$subject) {
                    continue;
                }
                $color = sanitize_hex_color($this->value($subject, 'color_code'));
                $subjects[] = array(
                    'name' => sanitize_text_field($this->value($subject, 'subject_name')),
                    'color' => $color ?: '#1f7ac0',
                );
            }

            $hours = array();
            foreach ((array) Olama_School_Teacher::get_office_hours($teacher_id, $academic_year_id, $semester_id) as $slot) {
                $day = sanitize_text_field($this->value($slot, 'day_name'));
                $hours[] = array(
                    'day' => isset($this->day_labels()[$day]) ? $this->day_labels()[$day] : $day,
                    'time' => sanitize_text_field($this->value($slot, 'available_time')),
                );
            }

            $teachers[] = array(
                'id' => $teacher_id,
                'name' => sanitize_text_field($this->value($active[$teacher_id], 'display_name')),
                'subjects' => $subjects,
                'office_hours' => $hours,
            );
        }

        return $teachers;
    }

    private function resolve_school_section_id(array $academic) {
        $year = $this->normalize_year(isset($academic['study_year']) ? $academic['study_year'] : '');
        $grade_id = isset($academic['class_id']) ? (string) $academic['class_id'] : '';
        $section_id = isset($academic['section_id']) ? (string) $academic['section_id'] : '';

        foreach ((array) Olama_School_Section::get_sections() as $section) {
            $section_year = isset($section->core_study_year) ? $this->normalize_year($section->core_study_year) : '';
            if (
                $year === $section_year
                && $grade_id === (string) (isset($section->core_grade_id) ? $section->core_grade_id : '')
                && $section_id === (string) (isset($section->core_section_id) ? $section->core_section_id : '')
            ) {
                return absint($section->id);
            }
        }
        return 0;
    }

    private function normalize_year($year) {
        return str_replace('/', '-', trim((string) $year));
    }

    private function day_labels() {
        return array(
            'Sunday' => 'الأحد',
            'Monday' => 'الاثنين',
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
        );
    }

    private function value($record, $key) {
        if (is_object($record) && isset($record->{$key})) {
            return (string) $record->{$key};
        }
        if (is_array($record) && isset($record[$key])) {
            return (string) $record[$key];
        }
        return '';
    }
}
