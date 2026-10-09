<?php
define('ABSPATH', __DIR__);
function sanitize_text_field($value) { return trim((string) $value); }
function absint($value) { return abs((int) $value); }
function apply_filters($hook, $value) { return $value; }
class Olama_Exam_Identity {
    public static function can_access_student($uid) { return $uid === 'STU-123'; }
}
class Olama_School_Exam {
    public static function get_student_specific_exams($uid) {
        return array('year_id' => 4, 'semester_id' => 7, 'section_id' => 12, 'exams' => array());
    }
}
class Results_DB {
    public $prefix = 'wp_';
    public $users = 'wp_users';
    public $calls = 0;
    public $query;
    public $params;
    public function prepare($query, $params) { $this->query = $query; $this->params = $params; return $query; }
    public function get_results($query) {
        $this->calls++;
        return strpos($query, 'AS attempt_id') !== false ? array((object) array('attempt_id' => 31, 'score' => 8)) : array();
    }
}
function assert_true($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
$wpdb = new Results_DB();
require dirname(__DIR__, 2) . '/olama-exam-engine/includes/class-exam-manager.php';
require dirname(__DIR__) . '/includes/class-provider-interface.php';
require dirname(__DIR__) . '/includes/providers/class-exams-provider.php';
assert_true(array() === Olama_Exam_Manager::get_student_results('OTHER'), 'Another student must be rejected.');
assert_true(0 === $wpdb->calls, 'Unauthorized reads must not query results.');
$rows = Olama_Exam_Manager::get_student_results('STU-123', array('academic_year_id' => 4, 'semester_id' => 7));
assert_true(1 === count($rows), 'Authorized results should be returned.');
foreach (array('a.student_uid = %s', 'a.submitted_at IS NOT NULL', 'a.is_preview = 0', "a.exam_type = 'school'", 'e.is_placement = 0', 'e.show_results = 1', "e.status IN ('published', 'active', 'closed')", 'e.academic_year_id = %d', 'e.semester_id = %d') as $predicate) {
    assert_true(strpos($wpdb->query, $predicate) !== false, 'Missing result visibility constraint: ' . $predicate);
}
assert_true(array('STU-123', 4, 7) === $wpdb->params, 'Student and academic scope should be parameterized.');
assert_true(strpos($wpdb->query, 'answers_json') === false && strpos($wpdb->query, 'snapshot') === false, 'Summary service must not expose answers.');
$provider = new Olama_Student_Gateway_Exams_Provider();
$data = $provider->get_data(array('student' => array('student_uid' => 'STU-123')));
assert_true(31 === $data['online_results'][0]->attempt_id, 'Gateway should consume the producer service.');
assert_true(array('STU-123', 4, 7) === $wpdb->params, 'Gateway should pass the schedule academic scope.');
echo "Exam results provider tests passed.\n";
