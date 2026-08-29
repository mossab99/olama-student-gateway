<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Access_Context {
    public function current() {
        if (!is_user_logged_in()) {
            return new WP_Error('olama_gateway_login_required', __('Please sign in with the family account.', 'olama-student-gateway'));
        }
        if (!function_exists('olama_users_get_identity')) {
            return new WP_Error('olama_gateway_users_missing', __('OLAMA Users is required to identify the family account.', 'olama-student-gateway'));
        }
        if (!function_exists('olama_core')) {
            return new WP_Error('olama_gateway_core_missing', __('OLAMA Core is required to load family information.', 'olama-student-gateway'));
        }

        $identity = olama_users_get_identity(get_current_user_id());
        if (!$identity || 'family' !== (string) $identity['identity_type'] || 'active' !== (string) $identity['account_status']) {
            return new WP_Error('olama_gateway_family_identity_required', __('This gateway is available to active family accounts only.', 'olama-student-gateway'));
        }

        $family_id = sanitize_text_field((string) $identity['oracle_identifier']);
        $family = olama_core()->families()->get_by_oracle_id($family_id);
        if (!$family) {
            return new WP_Error('olama_gateway_family_missing', __('The family record is not available in OLAMA Core.', 'olama-student-gateway'));
        }

        $academic = olama_core()->academic_context()->current();
        $study_year = $academic && !empty($academic->study_year) ? (string) $academic->study_year : '';
        $students = olama_core()->families()->get_students($family['family_uid']);
        $students = is_array($students) ? $students : array();

        foreach ($students as &$student) {
            $student['academic'] = $study_year
                ? olama_core()->student_years()->get_current_year($student['student_uid'], $study_year)
                : olama_core()->student_years()->get_current_year($student['student_uid']);
        }
        unset($student);

        return array(
            'user_id' => get_current_user_id(),
            'identity' => $identity,
            'family_id' => $family_id,
            'family_uid' => (string) $family['family_uid'],
            'family' => $family,
            'students' => $students,
            'academic' => $academic,
            'study_year' => $study_year,
        );
    }

    public function select_student(array $context, $requested_uid) {
        $requested_uid = sanitize_text_field((string) $requested_uid);
        if ('' === $requested_uid) {
            return null;
        }

        foreach ($context['students'] as $student) {
            if (hash_equals((string) $student['student_uid'], $requested_uid)) {
                if (!olama_core()->students()->belongs_to_family($requested_uid, $context['family_uid'])) {
                    break;
                }
                return $student;
            }
        }

        return new WP_Error('olama_gateway_student_forbidden', __('The selected student is not available to this family account.', 'olama-student-gateway'));
    }
}

