<?php
// Render the real gateway with two subjects, without WordPress or student data.
define('ABSPATH', __DIR__);
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_url($value) { return esc_html($value); }
function absint($value) { return abs((int) $value); }
function sanitize_hex_color($value) { return preg_match('/^#[a-f0-9]{6}$/i', $value) ? $value : ''; }
function add_query_arg($args, $url) { return $url . '?' . http_build_query($args); }
function is_wp_error($value) { return false; }
class Olama_Student_Gateway_Service_Settings { static function visible($key) { return true; } }
function expect_video($condition, $message) { if (!$condition) throw new Exception($message); }
$subjects = array();
foreach (array('Science', 'Arabic') as $index => $name) {
    $subjects[] = array('name' => $name, 'color' => '#197c91', 'units' => array(array('name' => 'Unit 1', 'number' => 1, 'lessons' => array(array('title' => 'Lesson <one>', 'number' => 1, 'videos' => array(array('part' => 1, 'url' => 'https://drive.google.com/file/d/file_123/view?usp=sharing'), array('part' => 2, 'url' => 'https://school.test/movie.mp4')))))));
}
$model = array('base_url' => 'https://school.test/family/', 'context' => array('family' => array(), 'students' => array(array('student_uid' => 'STU-1', 'student_name' => 'Test Student')), 'study_year' => '2026-2027'), 'student' => null, 'data' => array('subjects' => $subjects, 'video_count' => 4), 'active_view' => 'video_library', 'views' => array('family' => array()), 'student_views' => array('family' => array(), 'dashboard' => array()), 'logout_url' => '#');
function render_video_fixture($model) { ob_start(); include dirname(__DIR__) . '/templates/gateway.php'; return ob_get_clean(); }
$html = render_video_fixture($model);
expect_video(strpos($html, 'og_student=STU-1&amp;og_view=dashboard') !== false, 'First student click must link straight to dashboard even with a family-only current menu.');
expect_video(substr_count($html, 'data-video-card ') === 2, 'Two subject cards should render.');
expect_video(substr_count($html, 'data-video-subject hidden') === 2, 'No subject videos should appear initially.');
expect_video(strpos($html, 'target="_blank"') === false, 'Videos must not launch outside the site.');
expect_video(strpos($html, 'Lesson &lt;one&gt;') !== false, 'Lesson titles must be escaped.');
preg_match_all('/data-video-card[^>]*--subject-color:([^;" ]+)/', $html, $colors);
expect_video(count(array_unique($colors[1])) === 2, 'Subjects sharing a configured color must still have distinct cards.');
$model['student_views'] = array('family' => array(), 'video_library' => array());
expect_video(strpos(render_video_fixture($model), 'og_student=STU-1&amp;og_view=video_library') !== false, 'A hidden dashboard must fall back to an available student service.');
if (in_array('--html', $argv, true)) { echo $html; } else { echo "Video library rendering and student navigation tests passed.\n"; }
