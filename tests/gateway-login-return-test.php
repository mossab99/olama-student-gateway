<?php
require __DIR__ . '/late-styles-test.php';
function get_permalink() { return 'https://school.test/family/'; }
function home_url($path = '') { return 'https://school.test' . $path; }
function sanitize_text_field($value) { return strip_tags((string) $value); }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
$_GET = array('og_view' => 'exams', 'og_exam_section' => 'direct', 'exam_view' => 'take', 'exam_id' => '703', 'og_student' => 'STU-123', 'redirect_to' => 'https://external.test/');
$method = new ReflectionMethod($shortcode, 'login_return_url');
$url = $method->invoke($shortcode);
parse_str(parse_url($url, PHP_URL_QUERY), $args);
assert_true('703' === $args['exam_id'] && 'take' === $args['exam_view'] && 'direct' === $args['og_exam_section'], 'Login must preserve the intended direct exam.');
assert_true('STU-123' === $args['og_student'], 'Login must preserve an explicit family member selection.');
assert_true(!isset($args['redirect_to']) && 0 === strpos($url, get_permalink()), 'Login returns to the gateway and ignores arbitrary redirect destinations.');
echo "Gateway exam login return passed.\n";

$_GET = array('og_view' => 'video_library', 'og_plan' => '42', 'og_video' => 'v2-9', 'video_url' => 'https://external.test/');
parse_str(parse_url($method->invoke($shortcode), PHP_URL_QUERY), $args);
assert_true($args === array('og_view' => 'video_library', 'og_plan' => '42', 'og_video' => 'v2-9'), 'Login must preserve only the weekly plan and video identifiers.');
