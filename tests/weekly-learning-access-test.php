<?php
define('ABSPATH', __DIR__);
function add_action() {}
function __($text) { return $text; }
function current_user_can($capability) { return true; }
function is_wp_error($value) { return false; }
class Olama_Student_Gateway_Provider_Registry {}
class Olama_Student_Gateway_Access_Context {
    public function select_student(array $context, $uid) {
        foreach ($context['students'] as $student) {
            if ($student['student_uid'] === $uid) return $student;
        }
        return null;
    }
}
class Olama_Student_Gateway_School_Context_Provider {
    public function resolve_scope(array $context) { return $context['student']['scope']; }
}
class Olama_School_Section {
    public static function get_section($id) { return (object) array('grade_id' => 10); }
}
class Olama_School_Plan {
    public static $video_reads = 0;
    public static function get_published_plan($id) {
        return $id === 42 ? (object) array('section_id' => 101, 'subject_id' => 1, 'lesson_id' => 7,
            'lesson_title' => 'Lesson', 'academic_year_id' => 2, 'semester_id' => 3) : null;
    }
    public static function get_report_exams($plan) { return array((object) array('id' => 703)); }
    public static function get_lesson_videos(...$scope) {
        self::$video_reads++;
        return array(array('id' => 'v2-9', 'url' => 'https://video.example/preview', 'part' => 1));
    }
}
require dirname(__DIR__) . '/includes/class-shortcode.php';

function expect_access($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
}
function student($uid, $grade, $section, $year = 2, $semester = 3) {
    return array('student_uid' => $uid, 'scope' => array(
        'grade_id' => $grade, 'section_id' => $section, 'academic_year_id' => $year, 'semester_id' => $semester,
    ));
}
$shortcode = new Olama_Student_Gateway_Shortcode(new Olama_Student_Gateway_Provider_Registry());
$target = new ReflectionMethod($shortcode, 'weekly_learning_target');
$context = array('students' => array(student('fourth', 4, 41), student('fifth', 5, 51)));
expect_access($target->invoke($shortcode, $context, 42, 'v2-9', 0) === array(), 'Fourth and fifth grade students cannot watch the tenth grade video.');
expect_access(Olama_School_Plan::$video_reads === 0, 'The video source must not be read for an ineligible family.');
$context['students'][] = student('other-section', 10, 102);
$video = $target->invoke($shortcode, $context, 42, 'v2-9', 0);
expect_access($video['students'][0]['student_uid'] === 'other-section' && $video['video']['part'] === 1, 'An eligible grade receives the exact video.');
expect_access($target->invoke($shortcode, $context, 42, '', 703) === array(), 'Direct exams require the exact section.');
$context['students'][] = student('wrong-year', 10, 101, 1);
$context['students'][] = student('wrong-semester', 10, 101, 2, 4);
$context['students'][] = student('eligible', 10, 101);
$exam = $target->invoke($shortcode, $context, 42, '', 703);
expect_access(array_column($exam['students'], 'student_uid') === array('eligible'), 'Only students in the exact academic scope can take the exam.');
expect_access($target->invoke($shortcode, $context, 42, '', 999) === array(), 'An unrelated exam cannot be opened using the plan.');
expect_access($target->invoke($shortcode, $context, 42, 'v2-99', 0) === array(), 'An unrelated video cannot be opened using the plan.');
expect_access($target->invoke($shortcode, $context, 99, 'v2-9', 0) === array(), 'An unpublished plan cannot authorize a video.');
echo "Weekly learning access tests passed.\n";
