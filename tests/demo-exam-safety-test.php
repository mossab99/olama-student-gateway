<?php

$root = dirname(__DIR__);
$renderer = file_get_contents($root . '/includes/class-demo-exam.php');
$script = file_get_contents($root . '/assets/js/gateway.js');
$template = file_get_contents($root . '/templates/gateway.php');

function assert_demo_safe($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

assert_demo_safe(false !== strpos($renderer, 'preview_exam'), 'The demo must use the read-only preview API.');
assert_demo_safe(false === strpos($renderer, 'Olama_Exam_Engine::'), 'The demo must not create, autosave, submit, or grade attempts.');
assert_demo_safe(false === strpos($renderer, '<form'), 'The demo must not render a submittable HTML form.');
assert_demo_safe(false === strpos($script, 'fetch(') && false === strpos($script, 'XMLHttpRequest') && false === strpos($script, '$.ajax'), 'The demo UI must not transmit answers.');
assert_demo_safe(false === strpos($script, 'localStorage') && false === strpos($script, 'sessionStorage'), 'The demo UI must not retain answers in browser storage.');
assert_demo_safe(false !== strpos($template, "'demo' === \$exam_engine_view") && false !== strpos($template, 'Olama_Student_Gateway_Demo_Exam::render'), 'The gateway should route Temp Families to the demo renderer.');
assert_demo_safe(false !== strpos($template, 'ولا يمكنه إنشاء محاولة امتحان'), 'Direct attempt routes must be blocked for Temp Families.');

echo "Demo exam safety tests passed.\n";
