<?php

$root = dirname(__DIR__);
$template = file_get_contents($root . '/templates/gateway.php');
$shortcode = file_get_contents($root . '/includes/class-shortcode.php');

function assert_dashboard_layout($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

assert_dashboard_layout(false !== strpos($template, 'واجبات اليوم'), 'The dashboard should show today\'s homework card.');
assert_dashboard_layout(false !== strpos($template, 'خطة اليوم'), 'The dashboard should retain today\'s plan.');
assert_dashboard_layout(false !== strpos($template, 'عدد الامتحانات'), 'The dashboard should show the total exam count.');
assert_dashboard_layout(false !== strpos($template, "'من ' . esc_html(\$exam_dates[0]) . ' إلى '"), 'The exam card should show its date range.');
assert_dashboard_layout(false === strpos($template, 'عرض الخطة الأسبوعية'), 'The dashboard hero should not link to the weekly plan.');
assert_dashboard_layout(false === strpos($template, 'دروس منشورة هذا الأسبوع'), 'The dashboard should not show the weekly lessons statistic.');
assert_dashboard_layout(false === strpos($template, 'عناصر مسجلة للطالب'), 'The dashboard should not show the stores statistic.');
assert_dashboard_layout(false === strpos($shortcode, "\$data['transportation'] = \$this->providers->data('transportation', \$context);"), 'The dashboard should not load transportation data.');
assert_dashboard_layout(false === strpos($shortcode, "\$data['stores'] = \$this->providers->data('stores', \$context);"), 'The dashboard should not load stores data.');

echo "Dashboard layout tests passed.\n";
