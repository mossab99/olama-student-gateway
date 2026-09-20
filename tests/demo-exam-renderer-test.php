<?php

define('ABSPATH', __DIR__);

class WP_Error {
    public function get_error_message() { return 'error'; }
}

function __($text) { return $text; }
function esc_html__($text) { return $text; }
function esc_attr__($text) { return $text; }
function esc_html_e($text) { echo htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
function esc_html($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_attr($text) { return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8'); }
function esc_url($text) { return (string) $text; }
function wp_kses_post($text) { return (string) $text; }
function wp_create_nonce($action) { return 'read-only-nonce'; }
function admin_url($path) { return 'https://school.test/wp-admin/' . $path; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function absint($value) { return abs((int) $value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)); }
function is_wp_error($value) { return $value instanceof WP_Error; }

class Olama_Student_Gateway_Exam_Launcher {
    public static function action($exam, $student_uid, $url) {
        return array('state' => 'available', 'label' => 'demo', 'url' => $url);
    }
}

class Olama_Exam_Manager {
    public static function preview_exam($exam_id) {
        return array(
            'exam' => (object) array('id' => $exam_id, 'title' => 'Demo Exam', 'subject_name' => 'Science'),
            'questions' => array(
                (object) array(
                    'id' => 1,
                    'type' => 'mcq',
                    'question_text' => 'Choose one',
                    'answers_json' => json_encode(array('choices' => array('A', 'B'))),
                    'explanation' => 'SECRET-EXPLANATION',
                    'image_filename' => '',
                ),
                (object) array(
                    'id' => 2,
                    'type' => 'matching',
                    'question_text' => 'Match these',
                    'answers_json' => json_encode(array(
                        'pairs' => array(array('left' => 'Left 1', 'right' => 'Right 1')),
                        'shuffled_rights' => array('Right 2', 'Right 1'),
                    )),
                    'image_filename' => '',
                ),
            ),
        );
    }
}

function assert_demo_render($condition, $message) {
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

require dirname(__DIR__) . '/includes/class-demo-exam.php';

$available = array((object) array('id' => 17, 'status' => 'published'));
$html = Olama_Student_Gateway_Demo_Exam::render(17, $available, 'LOCAL-MEMBER-abc', 'https://school.test/family/');

assert_demo_render(false !== strpos($html, 'data-demo-exam'), 'The browser-only demo shell should render.');
assert_demo_render(false !== strpos($html, 'data-question-count="2"'), 'The demo should contain the preview questions.');
assert_demo_render(false === strpos($html, '<form'), 'The rendered demo must not be submittable.');
assert_demo_render(false === strpos($html, 'SECRET-EXPLANATION'), 'Teacher explanations must not be exposed in demo mode.');
assert_demo_render(false === strpos($html, 'name="attempt'), 'The demo must not contain an attempt identifier.');

$forbidden = Olama_Student_Gateway_Demo_Exam::render(99, $available, 'LOCAL-MEMBER-abc', 'https://school.test/family/');
assert_demo_render(false !== strpos($forbidden, 'غير متاح'), 'An exam outside the selected section catalogue must be rejected.');

echo "Demo exam renderer tests passed.\n";
