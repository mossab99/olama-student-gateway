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

class WP_User {}
function wp_verify_nonce($nonce, $action) { return hash_equals(hash('sha256', $action), $nonce); }
function wp_validate_redirect($url, $fallback) { return parse_url($url, PHP_URL_HOST) === 'school.test' ? $url : $fallback; }

function esc_html_e($text) { echo esc_attr($text); }
function __($text) { return $text; }
function esc_attr($value) { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }
function wp_create_nonce($action) { return hash('sha256', $action); }
function wp_login_form($args) {
    echo $GLOBALS['test_filters']['login_form_middle']('');
}

$destination = $method->invoke($shortcode);
$form = (new ReflectionMethod($shortcode, 'render_login'))->invoke($shortcode);
preg_match_all('/<input type="hidden" name="([^"]+)" value="([^"]*)">/', $form, $fields, PREG_SET_ORDER);
$_POST = array();
foreach ($fields as $field) {
    $_POST[$field[1]] = html_entity_decode($field[2], ENT_QUOTES, 'UTF-8');
}
assert_true(!isset($GLOBALS['test_filters']['login_form_middle']), 'Gateway fields must not leak into other WordPress login forms.');
assert_true($destination === $shortcode->restore_gateway_login_destination(get_permalink(), get_permalink(), new WP_User()), 'The signed weekly video destination must win even when another login redirect drops the query.');
assert_true(get_permalink() === $shortcode->restore_gateway_login_destination(get_permalink(), $destination, null), 'A failed login must not use the success redirect.');
$_POST['olama_gateway_destination'] .= '&og_video=v2-999';
assert_true(get_permalink() === $shortcode->restore_gateway_login_destination(get_permalink(), $destination, new WP_User()), 'A modified destination must be ignored.');
$_POST = array();
assert_true(get_permalink() === $shortcode->restore_gateway_login_destination(get_permalink(), $destination, new WP_User()), 'Other login forms must keep their normal redirect.');
echo "Gateway signed login destination tests passed.\n";
