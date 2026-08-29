<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Core_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'core';
    }

    public function is_available() {
        return function_exists('olama_core');
    }

    public function get_data(array $context, array $args = array()) {
        $resource = isset($args['resource']) ? sanitize_key($args['resource']) : 'family';

        if ('finance' === $resource) {
            if (empty($context['study_year'])) {
                return array('family_summary' => array(), 'due_allocations' => array(), 'student_transactions' => array());
            }
            return olama_core()->financial()->get_family_card($context['family_id'], $context['study_year']);
        }

        return array(
            'family' => $context['family'],
            'students' => $context['students'],
            'academic' => $context['academic'],
            'study_year' => $context['study_year'],
        );
    }
}

