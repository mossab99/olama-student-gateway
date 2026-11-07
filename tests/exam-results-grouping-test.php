<?php

define('ABSPATH', __DIR__);
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_url($value) { return esc_html($value); }
function absint($value) { return abs((int) $value); }
function has_action($hook) { return false; }

function check_grouping($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

// Render the actual results section with mixed, deliberately interleaved types.
$template = file_get_contents(dirname(__DIR__) . '/templates/gateway.php');
$start = strpos($template, '<section class="olama-gateway__panel" id="online-results">');
$end = strpos($template, '</section>', $start);
check_grouping(false !== $start && false !== $end, 'Results section must exist.');
$section = substr($template, $start, $end + strlen('</section>') - $start);
$field = static function ($record, $key, $fallback = '—') {
    $record = (array) $record;
    return isset($record[$key]) && '' !== (string) $record[$key] ? $record[$key] : $fallback;
};
$make_url = static function ($args) { return '/family/?' . http_build_query($args); };
$student = array('student_uid' => 'TEST-STUDENT');
$context = array();
$online_results = array(
    (object) array('exam_type' => 'direct', 'exam_title' => 'DIRECT-ONE', 'attempt_id' => 1),
    array('exam_type' => 'quiz', 'exam_title' => 'QUIZ-ONE', 'attempt_id' => 2),
    (object) array('exam_type' => 'exam', 'exam_title' => 'ASSESSMENT-ONE', 'attempt_id' => 3),
    array('exam_type' => 'direct', 'exam_title' => 'DIRECT-TWO', 'attempt_id' => 4),
    array('exam_type' => '', 'exam_title' => 'LEGACY-ONE', 'attempt_id' => 5),
    array('exam_title' => 'LEGACY-TWO', 'attempt_id' => 6),
);
ob_start();
eval('?>' . $section);
$html = ob_get_clean();
preg_match_all('/<tr class="olama-gateway__exam-group"><th colspan="8">(.*?)<\/th><\/tr>(.*?)(?=<tr class="olama-gateway__exam-group">|<\/tbody>)/s', $html, $matches, PREG_SET_ORDER);
check_grouping(3 === count($matches), 'Mixed results must render three groups.');
$expected = array(
    'اختبارات التقويم' => array('ASSESSMENT-ONE', 'LEGACY-ONE', 'LEGACY-TWO'),
    'امتحانات الدروس المباشرة' => array('DIRECT-ONE', 'DIRECT-TWO'),
    'الاختبارات القصيرة' => array('QUIZ-ONE'),
);
check_grouping(array_keys($expected) === array_column($matches, 1), 'Groups must follow assessment, direct, quiz order.');
foreach ($matches as $group) {
    foreach ($online_results as $result) {
        $title = $field($result, 'exam_title');
        check_grouping((false !== strpos($group[2], $title)) === in_array($title, $expected[$group[1]], true), 'Result must appear only under its own type: ' . $title);
    }
}
check_grouping(6 === substr_count($html, 'exam_view=results'), 'Every result retains its review link.');

$online_results = array((object) array('exam_type' => 'direct', 'exam_title' => 'DIRECT-ONLY'));
ob_start();
eval('?>' . $section);
$html = ob_get_clean();
check_grouping(1 === substr_count($html, 'olama-gateway__exam-group'), 'Empty types must not create empty groups.');
check_grouping(false !== strpos($html, 'امتحانات الدروس المباشرة'), 'A direct-only list must have the direct heading.');

echo "Exam results grouping tests passed.\n";
