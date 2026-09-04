<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Video_Library_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'video_library';
    }

    public function is_available() {
        return class_exists('Olama_Media_Guardian_Library')
            && class_exists('Olama_Student_Gateway_School_Context_Provider');
    }

    public function get_data(array $context, array $args = array()) {
        $school_provider = new Olama_Student_Gateway_School_Context_Provider();
        $school = $school_provider->get_data($context, array('resource' => 'schedule'));
        if (is_wp_error($school)) {
            return $school;
        }
        if (empty($school['grade_id'])) {
            return new WP_Error(
                'olama_gateway_video_grade_missing',
                __('The student grade is not mapped to the OLAMA School curriculum.', 'olama-student-gateway')
            );
        }

        $subject_ids = array();
        foreach ((array) $school['days'] as $day) {
            foreach ((array) $day['lessons'] as $lesson) {
                if (!empty($lesson['subject_id'])) {
                    $subject_ids[] = absint($lesson['subject_id']);
                }
            }
        }
        $subject_ids = array_values(array_unique(array_filter($subject_ids)));

        // A section may have teacher assignments before its timetable is
        // published. Keep the library section-scoped by using those assigned
        // subjects as a fallback rather than widening to the whole grade.
        if (!$subject_ids && class_exists('Olama_School_Teacher')) {
            foreach ((array) Olama_School_Teacher::get_teachers_for_section($school['section_id'], $school['academic_year_id']) as $teacher) {
                if (empty($teacher->ID)) {
                    continue;
                }
                $subject_ids = array_merge(
                    $subject_ids,
                    (array) Olama_School_Teacher::get_assigned_subjects(
                        absint($teacher->ID),
                        $school['section_id'],
                        $school['academic_year_id']
                    )
                );
            }
            $subject_ids = array_values(array_unique(array_filter(array_map('absint', $subject_ids))));
        }

        $library = Olama_Media_Guardian_Library::for_curriculum(
            $school['academic_year_id'],
            $school['semester_id'],
            $school['grade_id'],
            $subject_ids
        );
        if (is_wp_error($library)) {
            return $library;
        }

        unset($school['days']);
        return array_merge($school, $library);
    }
}
