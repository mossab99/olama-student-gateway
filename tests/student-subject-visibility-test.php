<?php
define('ABSPATH', __DIR__);
function sanitize_hex_color($color) { return $color; }
class Olama_School_Academic_Bridge {
    public static function get_study_year() { return '2026-2027'; }
    public static function sync($year = null) { return false; }
    public static function is_available() { return false; }
}
class Subject_Visibility_DB {
    public $prefix = 'wp_';
    public $rows;
    public function __construct() {
        $this->rows = array(
            1 => (object) array('id' => 1, 'grade_id' => 8, 'is_active' => 1),
            2 => (object) array('id' => 2, 'grade_id' => 8, 'is_active' => 1, 'appear_in_student_gateway' => 0),
            3 => (object) array('id' => 3, 'grade_id' => 8, 'is_active' => 0, 'appear_in_student_gateway' => 1),
            4 => (object) array('id' => 4, 'grade_id' => 9, 'is_active' => 1, 'appear_in_student_gateway' => 1),
        );
    }
    public function prepare($query, ...$params) { return vsprintf($query, $params); }
    public function get_results($query) {
        preg_match('/grade_id = (\d+)/', $query, $match);
        return array_values(array_filter($this->rows, static function ($row) use ($match, $query) {
            return $row->grade_id === (int) $match[1] && (strpos($query, 'AND is_active = 1') === false || $row->is_active === 1);
        }));
    }
    public function update($table, $data, $where) {
        foreach ($data as $key => $value) { $this->rows[$where['id']]->$key = $value; }
        return 1;
    }
}
function check_visibility($condition, $message) {
    if (!$condition) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
}
$wpdb = new Subject_Visibility_DB();
require dirname(__DIR__, 2) . '/olama-school/includes/class-subject.php';
$ids = static function () { return array_column(Olama_School_Subject::get_for_student_gateway(8), 'id'); };
check_visibility(array(1) === $ids(), 'Only active, enabled subjects in the selected grade appear; existing subjects default to visible.');
Olama_School_Subject::update_display_settings(1, array('appear_in_student_gateway' => false));
check_visibility(array() === $ids(), 'Unchecking gateway visibility persists and clears the subject cache.');
check_visibility(1 === $wpdb->rows[1]->is_active, 'Gateway visibility does not change the academic active status.');
Olama_School_Subject::update_display_settings(2, array('appear_in_student_gateway' => true));
check_visibility(array(2) === $ids(), 'Re-enabling a subject makes it available immediately.');
echo "Student subject visibility tests passed.\n";
