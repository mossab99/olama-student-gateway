<?php
define('ABSPATH', __DIR__);
function add_shortcode() {}
function sanitize_text_field($value) { return $value; }
function olama_exam_translate($value) { return $value; }
function esc_html($value) { return htmlspecialchars((string) $value); }
function esc_attr($value) { return esc_html($value); }
function wp_kses_post($value) { return $value; }
class Olama_Exam_Ajax { public static function can_manage_exams() { return false; } }
class Olama_Exam_Identity { public static $allowed = true; public static function can_access_student($uid) { return self::$allowed; } }
class Olama_Exam_Grader {
    public static function grade() { throw new Exception('Review must never regrade or write an attempt.'); }
    public static function grade_question() { return 1; }
}
class Review_DB {
    public $prefix = 'wp_';
    public $attempt;
    public function prepare($query, $id) { return $query; }
    public function get_row($query) { return $this->attempt; }
}
function assert_true($value, $message) { if (!$value) { throw new Exception($message); } }
require dirname(__DIR__, 2) . '/olama-exam-engine/includes/class-exam-shortcodes.php';
$wpdb = new Review_DB();
$wpdb->attempt = (object) array('student_uid' => 'STU-123', 'show_results' => 1, 'show_correct_answers' => 0, 'exam_title' => 'Exam', 'result' => 'pass', 'score' => 1, 'max_score' => 1, 'percentage' => 100, 'questions_snapshot_json' => json_encode(array(array('question_id' => 1, 'question_text' => 'Question', 'type' => 'short_answer', 'correct' => array('answer' => 'secret'), 'explanation' => 'Hidden explanation'))), 'answers_json' => json_encode(array(1 => 'Student answer')));
$renderer = new Olama_Exam_Shortcodes();
$method = new ReflectionMethod($renderer, 'render_results');
$html = $method->invoke($renderer, 31);
assert_true(strpos($html, 'Student answer') !== false, 'Review should show the submitted answer.');
assert_true(strpos($html, 'Correct Answer:') !== false && strpos($html, 'Hidden explanation') !== false, 'Published results should display answer review as requested.');
$wpdb->attempt->show_correct_answers = 1;
$html = $method->invoke($renderer, 31);
assert_true(strpos($html, 'Correct Answer:') !== false && strpos($html, 'Hidden explanation') !== false, 'Enabled answer review should include correct answers and explanations.');
Olama_Exam_Identity::$allowed = false;
assert_true(strpos($method->invoke($renderer, 31), 'Student answer') === false, 'Unauthorized reviews must not reveal answers.');
$formatter = new ReflectionMethod($renderer, 'format_student_answer_php');
$question = array('type' => 'mcq', 'answers' => array('choices' => array('العصر الحجري', 'العصر البرونزي')));
assert_true($formatter->invoke(null, $question, '0') === 'العصر الحجري', 'Zero-index choices must render their text.');
assert_true($formatter->invoke(null, $question, '1') === 'العصر البرونزي', 'Incorrect selections must render the selected choice text.');
$correct_formatter = new ReflectionMethod($renderer, 'format_correct_answer_php');
assert_true($correct_formatter->invoke(null, 'mcq', array('correct' => 0), $question['answers']) === 'العصر الحجري', 'Correct choice text should fall back to the saved shuffled choices.');
assert_true($formatter->invoke(null, array('type' => 'tf'), 'false') === 'False', 'True/false answers should render words.');
echo "Exam review tests passed.\n";
