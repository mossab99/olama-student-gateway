<?php

define('ABSPATH', __DIR__);

class WP_Error {
    private $code;
    public function __construct($code, $message) { $this->code = $code; }
    public function get_error_code() { return $this->code; }
}

function __($text) { return $text; }
function sanitize_text_field($value) { return trim((string) $value); }
function sanitize_textarea_field($value) { return trim((string) $value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function absint($value) { return abs((int) $value); }
function wp_timezone() { return new DateTimeZone('Asia/Amman'); }

class Olama_School_Section {
    public static function get_sections() {
        return array((object) array(
            'id' => 12,
            'core_study_year' => '2026-2027',
            'core_grade_id' => '8',
            'core_section_id' => 'A',
        ));
    }
}

class Olama_School_Plan {
    public static function get_plans($section_id, $start, $end) {
        return array(
            (object) array('id' => 1, 'plan_date' => $start, 'period_number' => 1, 'subject_name' => 'رياضيات', 'status' => 'approved', 'plan_type' => 'homework'),
            (object) array('id' => 2, 'plan_date' => $start, 'period_number' => 2, 'subject_name' => 'مسودة', 'status' => 'draft', 'plan_type' => 'homework'),
            (object) array('id' => 3, 'plan_date' => (new DateTimeImmutable($start))->modify('+1 day')->format('Y-m-d'), 'period_number' => 1, 'subject_name' => 'علوم', 'status' => 'published', 'plan_type' => 'review'),
        );
    }
}

require dirname(__DIR__) . '/includes/class-provider-interface.php';
require dirname(__DIR__) . '/includes/providers/class-weekly-plan-provider.php';

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$provider = new Olama_Student_Gateway_Weekly_Plan_Provider();
$data = $provider->get_data(array(
    'student' => array(
        'academic' => array('study_year' => '2026/2027', 'class_id' => '8', 'section_id' => 'A'),
    ),
), array('week' => '2026-08-23'));

assert_true(is_array($data), 'Weekly plan should resolve.');
assert_true(12 === $data['section_id'], 'Core section mapping should resolve the School section.');
assert_true(5 === count($data['days']), 'Sunday through Thursday should be returned.');
assert_true(1 === count($data['days'][0]['plans']), 'Draft plans must be excluded.');
assert_true('رياضيات' === $data['days'][0]['plans'][0]['subject'], 'Approved plan should be present.');
assert_true(1 === count($data['days'][1]['plans']), 'Published plan should be present.');

echo "Weekly plan provider tests passed.\n";

