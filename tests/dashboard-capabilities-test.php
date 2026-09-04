<?php

define('ABSPATH', __DIR__);

$test_capabilities = array();

function add_action() {
}

function current_user_can($capability) {
    global $test_capabilities;
    return !empty($test_capabilities[$capability]);
}

class Olama_Student_Gateway_Access_Context {
}

class Olama_Student_Gateway_Provider_Registry {
    public $calls = array();

    public function available($key) {
        return true;
    }

    public function data($key, array $context, array $args = array()) {
        $this->calls[] = $key;
        return array('provider' => $key);
    }
}

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/includes/class-shortcode.php';

$providers = new Olama_Student_Gateway_Provider_Registry();
$shortcode = new Olama_Student_Gateway_Shortcode($providers);
$reflection = new ReflectionMethod($shortcode, 'load_view_data');
$reflection->setAccessible(true);

$data = $reflection->invoke($shortcode, 'dashboard', array());
assert_same(array(), $data, 'Dashboard must not load providers without their capabilities.');
assert_same(array(), $providers->calls, 'Unauthorized dashboard providers must not be called.');

$test_capabilities = array(
    'olama_student_gateway_weekly_plan_view' => true,
    'olama_student_gateway_exams_view' => true,
);
$data = $reflection->invoke($shortcode, 'dashboard', array());
assert_same(array('weekly_plan', 'exams'), array_keys($data), 'Dashboard should include only authorized summaries.');
assert_same(array('weekly_plan', 'exams'), $providers->calls, 'Only authorized providers should be called.');

$test_capabilities['olama_student_gateway_transportation_view'] = true;
$test_capabilities['olama_student_gateway_stores_view'] = true;
$providers->calls = array();
$data = $reflection->invoke($shortcode, 'dashboard', array());
assert_same(
    array('weekly_plan', 'exams', 'transportation', 'stores'),
    array_keys($data),
    'Dashboard should include every authorized first-round summary.'
);

echo "Dashboard capability tests passed.\n";
