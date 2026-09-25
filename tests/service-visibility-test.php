<?php

define('ABSPATH', __DIR__);

$hidden_services = array();
$capabilities = array();

function add_action() {}
function __($message) { return $message; }
function sanitize_text_field($value) { return is_scalar($value) ? trim((string) $value) : ''; }
function get_option($name, $default = false) {
    global $hidden_services;
    return 'olama_student_gateway_hidden_services' === $name ? $hidden_services : $default;
}
function current_user_can($capability) {
    global $capabilities;
    return !empty($capabilities[$capability]);
}
class Olama_Student_Gateway_Access_Context {}
class Olama_Student_Gateway_Provider_Registry {
    public $calls = array();
    public function available($key) { return true; }
    public function data($key, array $context, array $args = array()) {
        $this->calls[] = $key;
        return array();
    }
}

function expect_visibility($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/includes/class-shortcode.php';

$providers = new Olama_Student_Gateway_Provider_Registry();
$shortcode = new Olama_Student_Gateway_Shortcode($providers);
$views_method = new ReflectionMethod($shortcode, 'allowed_views');
$views_method->setAccessible(true);
$data_method = new ReflectionMethod($shortcode, 'load_view_data');
$data_method->setAccessible(true);

$capabilities = array(
    'olama_student_gateway_weekly_plan_view' => true,
    'olama_student_gateway_exams_view' => true,
    'olama_student_gateway_messages_view' => true,
);
$all = $views_method->invoke($shortcode, true, false);
expect_visibility(isset($all['dashboard'], $all['family'], $all['weekly_plan'], $all['exams'], $all['messages']), 'Every service should be visible by default when permitted.');
expect_visibility(!in_array('exams.online', Olama_Student_Gateway_Service_Settings::hidden_from_selection(array('exams.online')), true), 'Saving a dotted exam service key must preserve it.');
expect_visibility(in_array('exams.short', Olama_Student_Gateway_Service_Settings::hidden_from_selection(array('exams.online')), true), 'Unselected services must be hidden.');

$hidden_services = array('dashboard', 'family', 'weekly_plan', 'exams.schedule', 'messages.compose');
$filtered = $views_method->invoke($shortcode, true, false);
expect_visibility(!isset($filtered['dashboard'], $filtered['family'], $filtered['weekly_plan']), 'Hidden whole views must be removed.');
expect_visibility(isset($filtered['exams'], $filtered['messages']), 'Hiding one child must preserve the other links in its group.');
expect_visibility(!in_array('schedule', Olama_Student_Gateway_Service_Settings::exam_sections(), true), 'A hidden exam section must be removed.');
expect_visibility(in_array('online', Olama_Student_Gateway_Service_Settings::exam_sections(), true), 'Other exam sections must remain visible.');
$data_method->invoke($shortcode, 'dashboard', array());
expect_visibility(!$providers->calls, 'A hidden weekly plan and exam schedule must not load dashboard providers.');

$hidden_services = array('exams.schedule', 'exams.online', 'exams.short', 'exams.finished', 'exams.online-results', 'exams.hall', 'messages.compose', 'messages.inbox');
$filtered = $views_method->invoke($shortcode, true, false);
expect_visibility(!isset($filtered['exams'], $filtered['messages']), 'Groups with all children hidden must be removed.');

echo "Service visibility tests passed.\n";
