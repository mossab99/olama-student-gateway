<?php

define('ABSPATH', __DIR__);

class WP_Error {
}

function __($text) { return $text; }
function absint($value) { return abs((int) $value); }
function is_wp_error($value) { return $value instanceof WP_Error; }

class Olama_Student_Gateway_School_Context_Provider {
    public function get_data(array $context, array $args = array()) {
        return array(
            'academic_year_id' => 4,
            'semester_id' => 7,
            'grade_id' => 8,
            'section_id' => 12,
            'grade_name' => 'الصف الثامن',
            'section_name' => 'أ',
            'semester_name' => 'الفصل الأول',
            'days' => array(
                array('lessons' => array(array('subject_id' => 20), array('subject_id' => 21))),
                array('lessons' => array(array('subject_id' => 20))),
            ),
        );
    }
}

class Olama_Media_Guardian_Library {
    public static $request;
    public static function for_curriculum($year_id, $semester_id, $grade_id, array $subject_ids) {
        self::$request = array($year_id, $semester_id, $grade_id, $subject_ids);
        return array('subjects' => array(array('id' => 20)), 'video_count' => 1);
    }
}

require dirname(__DIR__) . '/includes/class-provider-interface.php';
require dirname(__DIR__) . '/includes/providers/class-video-library-provider.php';

function assert_same($expected, $actual, $message) {
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$provider = new Olama_Student_Gateway_Video_Library_Provider();
$data = $provider->get_data(array());

assert_same(array(4, 7, 8, array(20, 21)), Olama_Media_Guardian_Library::$request, 'Video scope must use the active year, semester, mapped grade, and unique class subjects.');
assert_same(12, $data['section_id'], 'The validated student section should remain in the view model.');
assert_same(1, $data['video_count'], 'The published video total should be returned.');

if (!class_exists('Olama_School_Subject')) {
class Olama_School_Subject {
    public static $visible = array(20);
    public static function get_for_student_gateway($grade) {
        assert_same(8, $grade, 'Subject visibility must use the mapped grade.');
        return array_map(static function ($id) { return (object) array('id' => $id); }, self::$visible);
    }
}
}
$data = $provider->get_data(array());
assert_same(array(4, 7, 8, array(20)), Olama_Media_Guardian_Library::$request, 'Hidden subjects must be excluded from video requests.');
Olama_School_Subject::$visible = array();
$data = $provider->get_data(array());
assert_same(array(4, 7, 8, array()), Olama_Media_Guardian_Library::$request, 'An empty allowed list must not widen video access.');

echo "Video library provider tests passed.\n";
