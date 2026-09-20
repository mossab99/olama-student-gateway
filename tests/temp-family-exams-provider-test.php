<?php

define('ABSPATH', __DIR__);

class WP_Error {
    public function __construct($code, $message) {}
}

function __($text) { return $text; }
function absint($value) { return abs((int) $value); }
function sanitize_text_field($value) { return trim((string) $value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function sanitize_hex_color($value) { return $value; }
function is_wp_error($value) { return $value instanceof WP_Error; }

class Olama_School_Section {
    public static function get_section($id) {
        return 12 === (int) $id
            ? (object) array('id' => 12, 'grade_id' => 8, 'grade_name' => 'رابع أساسي', 'section_name' => 'أ')
            : null;
    }
    public static function get_sections() { return array(); }
}

class Olama_School_Schedule {}
class Olama_School_Teacher {}
class Olama_School_Subject {}

class Olama_Exam_Manager {
    public static $filters = array();

    public static function get_exams($filters) {
        self::$filters[] = $filters;
        return array((object) array('id' => 'published' === $filters['status'] ? 31 : 32));
    }
}

require dirname(__DIR__) . '/includes/class-provider-interface.php';
require dirname(__DIR__) . '/includes/providers/class-school-context-provider.php';
require dirname(__DIR__) . '/includes/providers/class-exams-provider.php';

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$context = array(
    'is_temp_family' => true,
    'academic' => (object) array('academic_year_id' => 4, 'semester_id' => 7, 'semester_name' => 'الفصل الأول'),
    'student' => array(
        'student_uid' => 'LOCAL-MEMBER-abc',
        'academic' => array('school_section_id' => 12),
    ),
);

$provider = new Olama_Student_Gateway_Exams_Provider();
$data = $provider->get_data($context);

assert_true(!empty($data['demo_mode']), 'Temp Family exam data should be explicitly marked as demo mode.');
assert_true(2 === count($data['online_exams']), 'Published and active online exams should be returned.');
assert_true(array() === $data['online_results'] && array() === $data['official_marks'], 'Demo mode must not expose marks or result records.');
assert_true(array() === $data['hall'] && array() === $data['schedule']['exams'], 'Demo mode must not resolve canonical student exam records.');
assert_true(2 === count(Olama_Exam_Manager::$filters), 'Published and active catalogues should both be queried.');
foreach (Olama_Exam_Manager::$filters as $filters) {
    assert_true(4 === $filters['academic_year_id'], 'The active School year should scope demo exams.');
    assert_true(7 === $filters['semester_id'], 'The active School semester should scope demo exams.');
    assert_true(12 === $filters['section_id'], 'The locally selected section should scope demo exams.');
}

echo "Temp Family exams provider tests passed.\n";
