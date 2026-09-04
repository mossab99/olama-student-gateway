<?php

define('ABSPATH', __DIR__);

class WP_Error {
    private $code;
    public function __construct($code, $message) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}

function __($text) { return $text; }
function sanitize_text_field($value) { return trim((string) $value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function sanitize_hex_color($value) { return preg_match('/^#[0-9a-f]{6}$/i', (string) $value) ? $value : null; }
function absint($value) { return abs((int) $value); }
function is_wp_error($value) { return $value instanceof WP_Error; }

class Olama_School_Section {
    public static function get_sections() {
        return array((object) array(
            'id' => 12,
            'core_study_year' => '2026-2027',
            'core_grade_id' => '8',
            'core_section_id' => 'A',
        ));
    }
    public static function get_section($id) {
        return (object) array('id' => $id, 'grade_id' => 8, 'grade_name' => 'الصف الثامن', 'section_name' => 'أ');
    }
}

class Olama_School_Schedule {
    public static function get_schedule($section_id, $semester_id, $type) {
        return array(
            'Sunday' => array(1 => (object) array('period_number' => 1, 'subject_id' => 21, 'subject_name' => 'الرياضيات', 'color_code' => '#2563eb')),
            'Monday' => array(2 => (object) array('period_number' => 2, 'subject_id' => 22, 'subject_name' => 'العلوم', 'color_code' => '#16835b')),
        );
    }
}

class Olama_School_Teacher {
    public static function get_teachers_for_section($section_id, $year_id) {
        return array(
            (object) array('ID' => 5, 'display_name' => 'المعلم النشط', 'user_email' => 'private@example.com'),
            (object) array('ID' => 9, 'display_name' => 'المعلم غير النشط'),
        );
    }
    public static function get_teachers() {
        return array((object) array('ID' => 5, 'display_name' => 'المعلم النشط', 'phone_number' => '0799999999'));
    }
    public static function get_assigned_subjects($teacher_id, $section_id, $year_id) {
        return array(21);
    }
    public static function get_office_hours($teacher_id, $year_id, $semester_id) {
        return array((object) array('day_name' => 'Sunday', 'available_time' => '10:00 - 11:00'));
    }
}

class Olama_School_Subject {
    public static function get_subject($id) {
        return (object) array('id' => $id, 'subject_name' => 'الرياضيات', 'color_code' => '#2563eb');
    }
}

require dirname(__DIR__) . '/includes/class-provider-interface.php';
require dirname(__DIR__) . '/includes/providers/class-school-context-provider.php';

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$context = array(
    'academic' => (object) array('academic_year_id' => 4, 'semester_id' => 7, 'semester_name' => 'الفصل الأول'),
    'student' => array('academic' => array('study_year' => '2026/2027', 'class_id' => '8', 'section_id' => 'A')),
);
$provider = new Olama_Student_Gateway_School_Context_Provider();

$schedule = $provider->get_data($context, array('resource' => 'schedule'));
assert_true(12 === $schedule['section_id'], 'The canonical Core section should map to School section 12.');
assert_true('الأحد' === $schedule['days'][0]['label'], 'Schedule day labels should be Arabic.');
assert_true('الرياضيات' === $schedule['days'][0]['lessons'][0]['subject'], 'The class subject should be normalized.');
assert_true(21 === $schedule['days'][0]['lessons'][0]['subject_id'], 'The local School subject ID should be available for media scoping.');

$teachers = $provider->get_data($context, array('resource' => 'teachers'));
assert_true(1 === count($teachers['teachers']), 'Inactive or unsynchronized teachers should be excluded.');
assert_true('المعلم النشط' === $teachers['teachers'][0]['name'], 'The active teacher should be returned.');
assert_true('الرياضيات' === $teachers['teachers'][0]['subjects'][0]['name'], 'Only assigned class subjects should be returned.');
assert_true('الأحد' === $teachers['teachers'][0]['office_hours'][0]['day'], 'Office-hour days should be Arabic.');
assert_true(!isset($teachers['teachers'][0]['user_email']), 'Teacher email must not be exposed.');
assert_true(!isset($teachers['teachers'][0]['phone_number']), 'Teacher phone must not be exposed.');

echo "School context provider tests passed.\n";
