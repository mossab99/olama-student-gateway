<?php

define('ABSPATH', __DIR__);

$test_now = '2026-09-14 12:00:00';
function __($text) { return $text; }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function sanitize_text_field($value) { return trim((string) $value); }
function absint($value) { return abs((int) $value); }
function current_time($type) { global $test_now; return $test_now; }
function add_query_arg($args, $url) {
    $parts = parse_url($url);
    $query = array();
    parse_str(isset($parts['query']) ? $parts['query'] : '', $query);
    $query = array_merge($query, $args);
    return $parts['scheme'] . '://' . $parts['host'] . $parts['path'] . '?' . http_build_query($query);
}

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require dirname(__DIR__) . '/includes/class-exam-launcher.php';

$exam = (object) array(
    'id' => 17,
    'status' => 'published',
    'start_time' => '2026-09-14 00:00:00',
    'end_time' => '2026-10-14 00:00:00',
);

$gateway_url = 'https://school.test/student-gateway/?og_student=STU-123&og_view=exams';
$action = Olama_Student_Gateway_Exam_Launcher::action($exam, 'STU-123', $gateway_url);
assert_true('available' === $action['state'], 'An in-window published exam should be available.');
assert_true(0 === strpos($action['url'], 'https://school.test/student-gateway/'), 'The action should stay on the existing gateway page.');
assert_true(false !== strpos($action['url'], 'og_view=exams'), 'The action should preserve the gateway exams view.');
assert_true(false !== strpos($action['url'], 'exam_view=take'), 'The action should open the Exam Engine taking view.');
assert_true(false !== strpos($action['url'], 'exam_id=17'), 'The action should include the exam ID.');
assert_true(false !== strpos($action['url'], 'student_uid=STU-123'), 'The action should include the selected student UID.');

$test_now = '2026-09-13 23:59:59';
$action = Olama_Student_Gateway_Exam_Launcher::action($exam, 'STU-123', $gateway_url);
assert_true('upcoming' === $action['state'] && '' === $action['url'], 'A future exam should not have a launch URL.');

$test_now = '2026-10-14 00:00:01';
$action = Olama_Student_Gateway_Exam_Launcher::action($exam, 'STU-123', $gateway_url);
assert_true('ended' === $action['state'] && '' === $action['url'], 'An ended exam should not have a launch URL.');

$exam->status = 'draft';
$action = Olama_Student_Gateway_Exam_Launcher::action($exam, 'STU-123', $gateway_url);
assert_true('unavailable' === $action['state'], 'A draft exam should not be launchable.');

$exam->status = 'active';
$exam->id = 0;
$action = Olama_Student_Gateway_Exam_Launcher::action($exam, 'STU-123', $gateway_url);
assert_true('unavailable' === $action['state'], 'An exam without an ID should not be launchable.');

$exam->id = 17;
$test_now = '2026-09-14 12:00:00';
$action = Olama_Student_Gateway_Exam_Launcher::action($exam, 'STU-123', $gateway_url);
assert_true(false === strpos($action['url'], '/exams/'), 'The action should not use the old nonexistent fallback page.');

echo "Exam launcher tests passed.\n";
