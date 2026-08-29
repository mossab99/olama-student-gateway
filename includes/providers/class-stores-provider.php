<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Stores_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'stores';
    }

    public function is_available() {
        return class_exists('OS_Assignment');
    }

    public function get_data(array $context, array $args = array()) {
        if (empty($context['student'])) {
            return array();
        }
        $academic_year_id = $context['academic'] && isset($context['academic']->academic_year_id)
            ? absint($context['academic']->academic_year_id)
            : 0;
        $rows = OS_Assignment::get_for_assignee('student', $context['student']['student_uid'], $academic_year_id);
        return is_array($rows) ? $rows : array();
    }
}

