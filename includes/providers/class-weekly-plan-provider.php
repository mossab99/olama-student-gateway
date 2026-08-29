<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Weekly_Plan_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'weekly_plan';
    }

    public function is_available() {
        return class_exists('Olama_School_Plan') && class_exists('Olama_School_Section');
    }

    public function get_data(array $context, array $args = array()) {
        $student = isset($context['student']) && is_array($context['student']) ? $context['student'] : null;
        if (!$student || empty($student['academic'])) {
            return new WP_Error('olama_gateway_academic_assignment_missing', __('The student does not have an academic assignment for the active year.', 'olama-student-gateway'));
        }

        $week_start = $this->week_start(isset($args['week']) ? $args['week'] : '');
        $week_end = $week_start->modify('+4 days');
        $section_id = $this->resolve_school_section_id($student['academic']);
        if (!$section_id) {
            return new WP_Error('olama_gateway_section_missing', __('The student section is not mapped to OLAMA School.', 'olama-student-gateway'));
        }

        $plans = Olama_School_Plan::get_plans($section_id, $week_start->format('Y-m-d'), $week_end->format('Y-m-d'));
        $published = array_values(array_filter((array) $plans, static function ($plan) {
            return isset($plan->status) && in_array((string) $plan->status, array('approved', 'published'), true);
        }));

        $days = array();
        for ($offset = 0; $offset < 5; $offset++) {
            $date = $week_start->modify('+' . $offset . ' days');
            $key = $date->format('Y-m-d');
            $days[$key] = array(
                'date' => $key,
                'label' => $this->day_label((int) $date->format('w')),
                'plans' => array(),
            );
        }

        foreach ($published as $plan) {
            if (!isset($days[$plan->plan_date])) {
                continue;
            }
            $days[$plan->plan_date]['plans'][] = $this->normalize_plan($plan);
        }

        return array(
            'week_start' => $week_start->format('Y-m-d'),
            'week_end' => $week_end->format('Y-m-d'),
            'previous_week' => $week_start->modify('-7 days')->format('Y-m-d'),
            'next_week' => $week_start->modify('+7 days')->format('Y-m-d'),
            'days' => array_values($days),
            'section_id' => $section_id,
        );
    }

    private function week_start($requested) {
        $timezone = wp_timezone();
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', sanitize_text_field((string) $requested), $timezone);
        if (!$date) {
            $date = new DateTimeImmutable('today', $timezone);
        }
        $day = (int) $date->format('w');
        return $date->modify('-' . $day . ' days');
    }

    private function resolve_school_section_id(array $academic) {
        $year = $this->normalize_year(isset($academic['study_year']) ? $academic['study_year'] : '');
        $grade_id = isset($academic['class_id']) ? (string) $academic['class_id'] : '';
        $section_id = isset($academic['section_id']) ? (string) $academic['section_id'] : '';

        foreach ((array) Olama_School_Section::get_sections() as $section) {
            $section_year = isset($section->core_study_year) ? $this->normalize_year($section->core_study_year) : '';
            if (
                $year === $section_year &&
                $grade_id === (string) (isset($section->core_grade_id) ? $section->core_grade_id : '') &&
                $section_id === (string) (isset($section->core_section_id) ? $section->core_section_id : '')
            ) {
                return absint($section->id);
            }
        }
        return 0;
    }

    private function normalize_plan($plan) {
        return array(
            'id' => absint($this->value($plan, 'id')),
            'date' => sanitize_text_field($this->value($plan, 'plan_date')),
            'period' => absint($this->value($plan, 'period_number')),
            'subject' => sanitize_text_field($this->value($plan, 'subject_name')),
            'unit' => sanitize_text_field($this->value($plan, 'unit_name')),
            'lesson' => sanitize_text_field($this->value($plan, 'lesson_title')),
            'topic' => sanitize_text_field($this->value($plan, 'custom_topic')),
            'plan_type' => sanitize_key($this->value($plan, 'plan_type')),
            'homework_student_book' => sanitize_text_field($this->value($plan, 'homework_sb')),
            'homework_exercise_book' => sanitize_text_field($this->value($plan, 'homework_eb')),
            'homework_notebook' => sanitize_textarea_field($this->value($plan, 'homework_nb')),
            'homework_worksheet' => sanitize_textarea_field($this->value($plan, 'homework_ws')),
            'teacher_notes' => sanitize_textarea_field($this->value($plan, 'teacher_notes')),
            'teacher' => sanitize_text_field($this->value($plan, 'teacher_name')),
            'status' => sanitize_key($this->value($plan, 'status')),
        );
    }

    private function value($record, $key) {
        return is_object($record) && isset($record->{$key}) ? (string) $record->{$key} : '';
    }

    private function normalize_year($year) {
        return str_replace('/', '-', trim((string) $year));
    }

    private function day_label($day) {
        $labels = array(
            0 => __('Sunday', 'olama-student-gateway'),
            1 => __('Monday', 'olama-student-gateway'),
            2 => __('Tuesday', 'olama-student-gateway'),
            3 => __('Wednesday', 'olama-student-gateway'),
            4 => __('Thursday', 'olama-student-gateway'),
        );
        return isset($labels[$day]) ? $labels[$day] : '';
    }
}
