<?php
define('ABSPATH', __DIR__);
function absint($value) { return abs((int) $value); }
class WP_Error { public function __construct($code, $message) {} }
class Olama_Exam_Ajax {
    public static $allowed = true;
    public static function can_supervise_exams() { return self::$allowed; }
}
class Bulk_Results_DB {
    public $prefix = 'wp_';
    public $sql;
    public $params;
    public $calls = 0;
    public $result = 12;
    public function prepare($sql, $params) { $this->sql = $sql; $this->params = $params; return $sql; }
    public function query($sql) { $this->calls++; return $this->result; }
}
function check($condition, $message) { if (!$condition) { throw new Exception($message); } }
require dirname(__DIR__, 2) . '/olama-exam-engine/includes/class-exam-manager.php';
$wpdb = new Bulk_Results_DB();
Olama_Exam_Ajax::$allowed = false;
check(Olama_Exam_Manager::publish_results(4, 7) instanceof WP_Error, 'Unauthorized publishing must fail.');
check($wpdb->calls === 0, 'Unauthorized publishing must not write.');
Olama_Exam_Ajax::$allowed = true;
check(Olama_Exam_Manager::publish_results(0, 7) instanceof WP_Error, 'Missing academic scope must fail.');
check($wpdb->calls === 0, 'Missing scope must not write.');
check(Olama_Exam_Manager::publish_results(4, 7) === 12, 'Return changed exam count.');
check($wpdb->params === array(4, 7), 'All grades must retain year and semester scope.');
check(strpos($wpdb->sql, 'COALESCE') === false, 'All grades must not add a grade constraint.');
foreach (array('SET e.show_results = 1', 'e.academic_year_id = %d', 'e.semester_id = %d', 'e.is_placement = 0', "e.status IN ('published', 'active', 'closed')", 'e.show_results = 0') as $clause) {
    check(strpos($wpdb->sql, $clause) !== false, 'Missing update scope: ' . $clause);
}
Olama_Exam_Manager::publish_results(4, 7, 8);
check($wpdb->params === array(4, 7, 8), 'Specific grade must be parameterized.');
check(strpos($wpdb->sql, 'COALESCE(s.grade_id, sub.grade_id) = %d') !== false, 'Grade must resolve from section or subject.');
$wpdb->result = 0;
check(Olama_Exam_Manager::publish_results(4, 7) === 0, 'Already published results should report zero changes.');
$wpdb->result = false;
check(Olama_Exam_Manager::publish_results(4, 7) instanceof WP_Error, 'Database errors must be reported.');
echo "Bulk exam results tests passed.\n";
