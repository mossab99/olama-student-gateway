<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Transportation_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() {
        return 'transportation';
    }

    public function is_available() {
        return function_exists('olama_core');
    }

    public function get_data(array $context, array $args = array()) {
        if (empty($context['student']) || empty($context['study_year'])) {
            return array('registration' => array(), 'operations' => array());
        }
        $student = $context['student'];
        $registration = olama_core()->transportation()->get_student(
            $context['family_id'],
            $student['oracle_student_id'],
            $context['study_year']
        );

        return array(
            'registration' => $registration ? $registration : array(),
            'operations' => apply_filters('olama_student_gateway_transportation_data', array(), $context, $args),
        );
    }
}

