<?php
 define('ABSPATH', __DIR__);
 class WP_Error { public function __construct($code, $message) {} }
 function sanitize_text_field($v) { return trim((string) $v); }
 function absint($v) { return abs((int) $v); }
 class Olama_School_EV_Template { static function get_score_config($id) { return array(3 => 'ممتاز'); } }
 class Olama_School_EV_Record { static function get_scores($id) { return array(7 => (object) array('score' => 3, 'notes' => 'ملاحظة المهارة')); } }
 class Olama_School_EV_Curriculum {
     static function get_full_curriculum($id) { return array((object) array('title_ar' => 'القراءة', 'categories' => array((object) array('title_ar' => 'الحروف', 'indicators' => array((object) array('id' => 7, 'indicator_text' => 'الألف'), (object) array('id' => 8, 'indicator_text' => 'الباء')))))); }
 }
 class Evaluation_DB {
     public $prefix = 'wp_', $last_error = '', $calls = 0;
     public function prepare($sql, ...$args) {
         if ($args !== array('ORA-STU-634-3', 'ORA-STU-634-3', 4, 2)) { throw new Exception('Wrong student or academic scope'); }
         foreach (array("r.status = 'published'", "r.context_type = 'student'", 'r.academic_year_id = %d', 'r.semester_id = %d', 'r.student_uid = %s', 'olama_students WHERE student_uid = %s') as $clause) {
             if (strpos($sql, $clause) === false) { throw new Exception('Missing scope: ' . $clause); }
         }
         return $sql;
     }
     public function get_results($sql) { $this->calls++; return array((object) array('id' => 1, 'template_id' => 2, 'template_name' => 'تقييم القراءة', 'updated_at' => '2026-10-09', 'supervisor_comments' => 'ملاحظة عامة')); }
 }
 require __DIR__ . '/../includes/class-provider-interface.php';
 require __DIR__ . '/../includes/providers/class-evaluations-provider.php';
 $wpdb = new Evaluation_DB();
 $provider = new Olama_Student_Gateway_Evaluations_Provider();
 $data = $provider->get_data(array('student' => array('student_uid' => 'ORA-STU-634-3'), 'academic' => (object) array('academic_year_id' => 4, 'semester_id' => 2)));
 if ($data['evaluations'][0]['comments'] !== 'ملاحظة عامة' || $data['evaluations'][0]['domains'][0]['categories'][0]['indicators'][0]['notes'] !== 'ملاحظة المهارة') { throw new Exception('Missing evaluation notes'); }
 $rows = $data['evaluations'][0]['domains'][0]['categories'][0]['indicators'];
 if ($rows[0]['result'] !== 'ممتاز' || $rows[1]['result'] !== 'لم يُقيّم') { throw new Exception('Incorrect score labels'); }
 if (!($provider->get_data(array()) instanceof WP_Error) || $wpdb->calls !== 1) { throw new Exception('Missing scope must never query records'); }
 $wpdb->last_error = 'database failure';
 if (!($provider->get_data(array('student' => array('student_uid' => 'ORA-STU-634-3'), 'academic' => array('academic_year_id' => 4, 'semester_id' => 2))) instanceof WP_Error)) { throw new Exception('Query failure hidden'); }
 echo "Evaluation provider tests passed.\n";
