<?php
require __DIR__ . '/evaluations-provider-test.php';
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function is_wp_error($v) { return $v instanceof WP_Error; }
function esc_attr($v) { return esc_html($v); }
$data['evaluations'][0]['title'] = '<script>alert(1)</script>';
$data['evaluations'][0]['domains'][0]['categories'][0]['indicators'][0]['text'] = '<b>الألف</b>';
$student = array('student_name' => 'مايا');
$context = array('study_year' => '2026-2027');
$student_grade = static function ($student) { return 'تمهيدي'; };
ob_start(); include __DIR__ . '/../templates/evaluations.php'; $html = ob_get_clean();
foreach (array('data-evaluation-tab', 'data-evaluation-print', 'data-evaluation-report', '--evaluation-progress:50%', 'مهارة لم تُقيّم بعد', '&lt;script&gt;', '&lt;b&gt;', 'ملاحظة عامة', 'ملاحظة المهارة', 'og-evaluation-comments', 'data-evaluation-print-layout', 'og-print-header', 'og-print-skills', 'og-print-note', 'og-print-comments', 'معًا ندعم التعلم') as $expected) {
    if (strpos($html, $expected) === false) { throw new Exception('Missing report output: ' . $expected); }
}
if (strpos($html, '<script>') !== false) { throw new Exception('Unescaped title'); }
$data = array('evaluations' => array());
ob_start(); include __DIR__ . '/../templates/evaluations.php'; $html = ob_get_clean();
if (strpos($html, 'لا توجد تقييمات معتمدة') === false) { throw new Exception('Missing empty state'); }
echo "Evaluation report rendering tests passed.\n";
