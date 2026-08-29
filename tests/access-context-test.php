<?php

define('ABSPATH', __DIR__);

class WP_Error {
    private $code;
    private $message;

    public function __construct($code, $message) {
        $this->code = $code;
        $this->message = $message;
    }

    public function get_error_code() {
        return $this->code;
    }

    public function get_error_message() {
        return $this->message;
    }
}

$test_logged_in = true;
$test_identity = array(
    'identity_type' => 'family',
    'account_status' => 'active',
    'oracle_identifier' => '459',
);

function __($text) { return $text; }
function is_user_logged_in() { global $test_logged_in; return $test_logged_in; }
function get_current_user_id() { return 77; }
function sanitize_text_field($value) { return trim((string) $value); }
function olama_users_get_identity($user_id) { global $test_identity; return $test_identity; }

class Test_Family_Service {
    public function get_by_oracle_id($id) {
        return '459' === (string) $id ? array('family_uid' => 'ORA-FAM-459', 'oracle_family_id' => '459') : null;
    }

    public function get_students($family_uid) {
        return array(
            array('student_uid' => 'ORA-STU-459-1', 'student_name' => 'أحمد'),
            array('student_uid' => 'ORA-STU-459-2', 'student_name' => 'سارة'),
        );
    }
}

class Test_Student_Service {
    public function belongs_to_family($student_uid, $family_uid) {
        return 'ORA-FAM-459' === $family_uid && in_array($student_uid, array('ORA-STU-459-1', 'ORA-STU-459-2'), true);
    }
}

class Test_Student_Year_Service {
    public function get_current_year($student_uid, $year = null) {
        return array('student_uid' => $student_uid, 'study_year' => $year, 'class_name' => 'الثامن', 'section_name' => 'أ');
    }
}

class Test_Academic_Context_Service {
    public function current() {
        return (object) array('study_year' => '2026-2027');
    }
}

class Test_Core {
    public function families() { return new Test_Family_Service(); }
    public function students() { return new Test_Student_Service(); }
    public function student_years() { return new Test_Student_Year_Service(); }
    public function academic_context() { return new Test_Academic_Context_Service(); }
}

function olama_core() { return new Test_Core(); }

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require dirname(__DIR__) . '/includes/class-access-context.php';

$access = new Olama_Student_Gateway_Access_Context();
$context = $access->current();
assert_true(is_array($context), 'Family context should resolve.');
assert_true('ORA-FAM-459' === $context['family_uid'], 'Canonical family UID should be used.');
assert_true(2 === count($context['students']), 'All family students should be returned.');

$student = $access->select_student($context, 'ORA-STU-459-2');
assert_true(is_array($student) && 'ORA-STU-459-2' === $student['student_uid'], 'Owned student should be selectable.');

$forbidden = $access->select_student($context, 'ORA-STU-999-1');
assert_true($forbidden instanceof WP_Error && 'olama_gateway_student_forbidden' === $forbidden->get_error_code(), 'Unowned student should be rejected.');

$test_identity['identity_type'] = 'employee';
$not_family = $access->current();
assert_true($not_family instanceof WP_Error && 'olama_gateway_family_identity_required' === $not_family->get_error_code(), 'Non-family identity should be rejected.');

$test_identity['identity_type'] = 'family';
$test_logged_in = false;
$guest = $access->current();
assert_true($guest instanceof WP_Error && 'olama_gateway_login_required' === $guest->get_error_code(), 'Guests should be rejected.');

echo "Access context tests passed.\n";

