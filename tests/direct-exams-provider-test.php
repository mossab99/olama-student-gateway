<?php
define('ABSPATH', __DIR__);
function absint($value) { return abs((int) $value); }
function is_wp_error($value) { return false; }
class Olama_Student_Gateway_School_Context_Provider {
    public function resolve_scope($context) { return array('academic_year_id' => 4, 'semester_id' => 7, 'grade_id' => 8, 'section_id' => 12); }
}
class Olama_Exam_Manager {
    public static $filters = array();
    public static function get_exams($filters) {
        self::$filters[] = $filters;
        $rows = array(
            (object) array('id' => 1, 'exam_type' => 'direct', 'status' => 'active', 'direct_lesson_id' => 100),
            (object) array('id' => 2, 'exam_type' => 'direct', 'status' => 'closed', 'direct_lesson_id' => 101),
            (object) array('id' => 3, 'exam_type' => 'quiz', 'status' => 'active'),
        );
        return array_values(array_filter($rows, static function ($row) use ($filters) { return $filters['status'] === $row->status; }));
    }
}
class Olama_School_Subject {
    public static function get_subjects_by_grade($grade, $active) { check(8 === $grade && $active, 'Only active subjects of the mapped grade.'); return array((object) array('id' => 5)); }
}
class Olama_School_Unit {
    public static function get_units($subject, $grade, $semester) { check(5 === $subject && 8 === $grade && 7 === $semester, 'Scope curriculum to subject, grade and semester.'); return array((object) array('id' => 91)); }
}
class Olama_School_Lesson {
    public static function get_lessons($unit) { return array((object) array('id' => 100), (object) array('id' => 101), (object) array('id' => 102)); }
}
function check($condition, $message) { if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); } }
require dirname(__DIR__) . '/includes/class-provider-interface.php';
require dirname(__DIR__) . '/includes/providers/class-exams-provider.php';
$_GET['og_exam_section'] = 'direct';
$provider = new Olama_Student_Gateway_Exams_Provider();
$data = $provider->get_data(array('is_temp_family' => true, 'student' => array('student_uid' => 'LOCAL-MEMBER-abc')));
check(count($data['online_exams']) === 1 && 1 === $data['online_exams'][0]->id, 'Only available direct exams belong in this section.');
check(count($data['direct_catalog'][0]['units'][0]['lessons']) === 3, 'Show all lessons, including closed exams and lessons without exams.');
foreach (Olama_Exam_Manager::$filters as $filters) {
    check(12 === $filters['section_id'] && 4 === $filters['academic_year_id'] && 7 === $filters['semester_id'], 'All engine reads must use the student academic scope.');
}
$method = new ReflectionMethod($provider, 'online_exams');
check(array() === $method->invoke($provider, array('academic_year_id' => 4, 'semester_id' => 7)), 'A missing section must never expose all school exams.');
echo "Direct exam curriculum and scope tests passed.\n";
