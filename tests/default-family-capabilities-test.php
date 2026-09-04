<?php

define('ABSPATH', __DIR__);

$registered_module = null;

function __($text) {
    return $text;
}

function olama_users_register_module(array $definition) {
    global $registered_module;
    $registered_module = $definition;
}

function assert_true($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require_once dirname(__DIR__) . '/includes/class-plugin.php';

$reflection = new ReflectionClass('Olama_Student_Gateway_Plugin');
$plugin = $reflection->newInstanceWithoutConstructor();
$plugin->register_access_module();

$expected = array(
    'olama_student_gateway_family_view',
    'olama_student_gateway_weekly_plan_view',
    'olama_student_gateway_exams_view',
    'olama_student_gateway_transportation_view',
    'olama_student_gateway_stores_view',
);

assert_true(is_array($registered_module), 'The Student Gateway module should be registered.');
foreach ($expected as $capability) {
    assert_true(
        in_array($capability, $registered_module['default_grant_capabilities'], true),
        "Family defaults should include {$capability}."
    );
}

foreach (array(
    'olama_student_gateway_evaluations_view',
    'olama_student_gateway_attendance_view',
    'olama_student_gateway_messages_view',
) as $future_capability) {
    assert_true(
        !in_array($future_capability, $registered_module['default_grant_capabilities'], true),
        "Unimplemented service {$future_capability} must remain opt-in."
    );
}

echo "Default family capability tests passed.\n";
