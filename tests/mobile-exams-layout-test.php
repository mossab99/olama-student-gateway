<?php

$root = dirname(__DIR__);
$template = file_get_contents($root . '/templates/gateway.php');
$styles = file_get_contents($root . '/assets/css/gateway.css');

function assert_mobile_exam_layout($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

assert_mobile_exam_layout(false !== strpos($template, 'olama-gateway__exam-table'), 'The online exams list should expose its responsive table hook.');
assert_mobile_exam_layout(false !== strpos($template, 'olama-gateway__exam-card'), 'Each online exam should expose its mobile card hook.');
assert_mobile_exam_layout(substr_count($template, 'data-label=') >= 7, 'Every online exam field should carry a readable mobile label.');
assert_mobile_exam_layout(false !== strpos($template, 'data-exam-state='), 'Exam cards should expose their launch state for visual treatment.');
assert_mobile_exam_layout(false !== strpos($template, 'online_exam_rows'), 'Online exams should be prepared for priority sorting.');
assert_mobile_exam_layout(false !== strpos($template, "'available' => 0, 'upcoming' => 1, 'ended' => 2, 'unavailable' => 3"), 'Available exams should sort before upcoming, ended, and unavailable exams.');
assert_mobile_exam_layout(false !== strpos($template, "\$exam_action['status_label']"), 'The online-exam status should use a parent-friendly label instead of the source status.');
assert_mobile_exam_layout(false !== strpos($styles, '@media (max-width: 720px)'), 'The gateway should provide a phone breakpoint.');
assert_mobile_exam_layout(false !== strpos($styles, '.olama-gateway__exam-card > td::before'), 'Mobile exam cards should render their field labels.');
assert_mobile_exam_layout(false !== strpos($styles, 'content: attr(data-label)'), 'Mobile field labels should come from the semantic table markup.');

echo "Mobile exam layout tests passed.\n";
