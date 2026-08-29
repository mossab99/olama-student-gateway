<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Exams_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'exams';
    }

    public function is_available() {
        return class_exists('Olama_School_Exam') || class_exists('Olama_Exam_Manager');
    }

    public function get_data(array $context, array $args = array()) {
        $student = isset($context['student']) ? $context['student'] : null;
        if (!$student) {
            return array();
        }

        $schedule = $this->schedule($student['student_uid']);
        $online = $this->online_exams($schedule);
        $hall = $this->hall_assignment($student['student_uid'], $schedule);

        $data = array(
            'schedule' => $schedule,
            'hall' => $hall,
            'online_exams' => $online,
            'online_results' => apply_filters('olama_student_gateway_exam_results_data', array(), $context, $args),
            'official_marks' => apply_filters('olama_student_gateway_official_marks_data', array(), $context, $args),
        );
        return apply_filters('olama_student_gateway_exams_data', $data, $context, $args);
    }

    private function schedule($student_uid) {
        if (!class_exists('Olama_School_Exam')) {
            return array();
        }
        $schedule = Olama_School_Exam::get_student_specific_exams($student_uid);
        if (!is_array($schedule)) {
            return array();
        }
        $schedule['exams'] = array_values(array_filter((array) $schedule['exams'], static function ($exam) {
            return isset($exam->status) && 'approved' === (string) $exam->status;
        }));
        return $schedule;
    }

    private function online_exams(array $schedule) {
        if (!class_exists('Olama_Exam_Manager') || empty($schedule['year_id']) || empty($schedule['semester_id'])) {
            return array();
        }
        $base = array(
            'academic_year_id' => absint($schedule['year_id']),
            'semester_id' => absint($schedule['semester_id']),
            'section_id' => !empty($schedule['section_id']) ? absint($schedule['section_id']) : 0,
            'is_placement' => 0,
        );
        $rows = array();
        foreach (array('published', 'active') as $status) {
            $rows = array_merge($rows, (array) Olama_Exam_Manager::get_exams(array_merge($base, array('status' => $status))));
        }
        $unique = array();
        foreach ($rows as $row) {
            if (isset($row->id)) {
                $unique[(int) $row->id] = $row;
            }
        }
        return array_values($unique);
    }

    private function hall_assignment($student_uid, array $schedule) {
        if (!class_exists('Olama_Exam_Hall') || empty($schedule['year_id'])) {
            return array();
        }
        $assignments = Olama_Exam_Hall::get_all_assignments(
            absint($schedule['year_id']),
            !empty($schedule['semester_id']) ? absint($schedule['semester_id']) : 0
        );
        foreach ((array) $assignments as $hall_rows) {
            foreach ((array) $hall_rows as $assignment) {
                if (isset($assignment->student_uid) && hash_equals((string) $assignment->student_uid, (string) $student_uid)) {
                    return $assignment;
                }
            }
        }
        return array();
    }
}
