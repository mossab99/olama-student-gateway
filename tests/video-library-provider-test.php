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

echo "Video library provider tests passed.\n";
