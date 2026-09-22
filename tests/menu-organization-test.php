<?php

$root = dirname(__DIR__);
$template = file_get_contents($root . '/templates/gateway.php');
$shortcode = file_get_contents($root . '/includes/class-shortcode.php');

function assert_menu_organization($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

foreach (array('لوحة المتابعة', 'معلومات العائلة', 'المتابعة اليومية', 'مكتبة الفيديو', 'التقييم والأداء', 'الامتحانات', 'الرسائل') as $label) {
    assert_menu_organization(false !== strpos($template, $label), "The organized navigation should include {$label}.");
}

foreach (array('بطاقة العائلة', 'الزي والكتب', 'الخطة الأسبوعية', 'الجدول الدراسي', 'الساعات المكتبية', 'الحضور والغياب', 'التقييمات', 'جدول الامتحانات', 'الامتحانات الإلكترونية', 'نتائج الامتحانات الإلكترونية', 'قاعات الامتحان', 'إرسال رسالة', 'صندوق البريد') as $label) {
    assert_menu_organization(false !== strpos($template, $label), "The organized navigation should include {$label}.");
}

assert_menu_organization(false !== strpos($template, "'og_message' => 'compose'"), 'The compose link should carry its message mode.');
assert_menu_organization(false !== strpos($template, "'og_message' => 'inbox'"), 'The inbox link should carry its message mode.');
assert_menu_organization(false !== strpos($template, "'og_exam_section' => 'schedule'"), 'The schedule link should target the schedule section.');
assert_menu_organization(false !== strpos($template, "'og_exam_section' => 'online-results'"), 'The results link should target the online-results section.');
assert_menu_organization(false !== strpos($template, 'id="exam-hall"'), 'The hall panel should expose its navigation target.');
assert_menu_organization(false !== strpos($template, 'id="online-results"'), 'The results panel should expose its navigation target.');
assert_menu_organization(false !== strpos($shortcode, "array('mode' => \$message_mode)"), 'The messages provider should receive the requested mode.');

echo "Menu organization tests passed.\n";
