<?php

define('ABSPATH', __DIR__);

class WP_Post {
    public $post_content = '';
}

$test_enqueued_styles = array();
$test_enqueued_scripts = array();
$test_exam_assets_called = 0;

function is_singular() { return true; }
function get_queried_object() { global $test_post; return $test_post; }
function has_shortcode($content, $tag) { return false !== strpos($content, '[' . $tag . ']'); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function wp_unslash($value) { return $value; }
function wp_enqueue_style($handle) { global $test_enqueued_styles; $test_enqueued_styles[] = $handle; }
function wp_enqueue_script($handle) { global $test_enqueued_scripts; $test_enqueued_scripts[] = $handle; }
function olama_exam_enqueue_frontend_assets($force = false) {
    global $test_exam_assets_called;
    if ($force) {
        $test_exam_assets_called++;
    }
}

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require dirname(__DIR__) . '/includes/class-plugin.php';

$test_post = new WP_Post();
$reflection = new ReflectionClass('Olama_Student_Gateway_Plugin');
$plugin = $reflection->newInstanceWithoutConstructor();

$test_post->post_content = '[olama_student_gateway]';
$_GET['og_view'] = 'exams';
$_GET['exam_view'] = 'take';
$plugin->maybe_enqueue_assets();
assert_true(in_array('olama-student-gateway', $test_enqueued_styles, true), 'Gateway styles should be enqueued.');
assert_true(in_array('jquery', $test_enqueued_scripts, true), 'jQuery should be explicitly enqueued for an embedded exam.');
assert_true(1 === $test_exam_assets_called, 'Exam Engine assets should be loaded during the early enqueue phase.');

$test_enqueued_scripts = array();
$test_exam_assets_called = 0;
$_GET['exam_view'] = 'dashboard';
$plugin->maybe_enqueue_assets();
assert_true(!in_array('jquery', $test_enqueued_scripts, true), 'The normal gateway exam list should not force Exam Engine scripts.');
assert_true(0 === $test_exam_assets_called, 'The normal gateway exam list should not load Exam Engine assets.');

$test_post->post_content = 'Content replaced by an access plugin';
$test_enqueued_scripts = array();
$test_exam_assets_called = 0;
$_GET['exam_view'] = 'take';
$plugin->maybe_enqueue_assets();
assert_true(in_array('jquery', $test_enqueued_scripts, true), 'The explicit gateway exam route should load jQuery even when shortcode detection fails.');
assert_true(1 === $test_exam_assets_called, 'The explicit gateway exam route should load Exam Engine assets when shortcode detection fails.');

echo "Embedded exam asset tests passed.\n";
