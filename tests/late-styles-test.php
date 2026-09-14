<?php

define('ABSPATH', __DIR__);
define('OLAMA_STUDENT_GATEWAY_URL', 'https://school.test/wp-content/plugins/olama-student-gateway/');
define('OLAMA_STUDENT_GATEWAY_VERSION', '0.4.5');

$test_head_done = false;
$test_gateway_style_done = false;

function add_action() {}
function did_action($hook) { global $test_head_done; return 'wp_head' === $hook && $test_head_done ? 1 : 0; }
function wp_style_is($handle, $status) { global $test_gateway_style_done; return 'olama-student-gateway' === $handle && 'done' === $status && $test_gateway_style_done; }
function wp_print_styles($handles) {
    echo '<link data-test-handles="' . implode(',', $handles) . '">';
}
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function wp_unslash($value) { return $value; }
function esc_url($url) { return $url; }
function includes_url($path) { return 'https://school.test/wp-includes/' . $path; }

class Olama_Student_Gateway_Access_Context {}
class Olama_Student_Gateway_Provider_Registry {}

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require dirname(__DIR__) . '/includes/class-shortcode.php';

$shortcode = new Olama_Student_Gateway_Shortcode(new Olama_Student_Gateway_Provider_Registry());
$method = new ReflectionMethod($shortcode, 'late_style_markup');
$method->setAccessible(true);

assert_true('' === $method->invoke($shortcode), 'Styles should use the normal head enqueue before wp_head runs.');

$test_head_done = true;
$markup = $method->invoke($shortcode);
assert_true(false !== strpos($markup, 'dashicons,olama-student-gateway'), 'Late rendering should print Dashicons and gateway styles.');

$test_gateway_style_done = true;
assert_true('' === $method->invoke($shortcode), 'Styles already printed in the head should not be duplicated.');

$_GET['og_view'] = 'exams';
$_GET['exam_view'] = 'take';
$markup = $method->invoke($shortcode);
assert_true(false !== strpos($markup, 'gateway.css?ver=0.4.5'), 'Embedded exams should always receive a direct versioned gateway stylesheet link.');

echo "Late gateway style tests passed.\n";
