<?php

if (!defined('ABSPATH')) { exit; }

class Olama_Student_Gateway_Evaluations_Provider implements Olama_Student_Gateway_Provider_Interface {
    public function key() { return 'evaluations'; }

    public function is_available() {
        return class_exists('Olama_School_EV_Record') && class_exists('Olama_School_EV_Template')
            && class_exists('Olama_School_EV_Curriculum');
    }

    public function get_data(array $context, array $args = array()) {
        global $wpdb;
        $uid = isset($context['student']['student_uid']) ? sanitize_text_field($context['student']['student_uid']) : '';
        $academic = isset($context['academic']) ? (array) $context['academic'] : array();
        $year = absint(isset($academic['academic_year_id']) ? $academic['academic_year_id'] : 0);
        $semester = absint(isset($academic['semester_id']) ? $academic['semester_id'] : 0);
        if (!$uid || !$year || !$semester) {
            return new WP_Error('olama_gateway_evaluation_context_missing', 'لا يمكن تحميل التقييمات دون تحديد الطالب والسنة والفصل الدراسي الحالي.');
        }
        // Resolve legacy numeric IDs through the student's stable Core UID.
        $records = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, t.template_name FROM {$wpdb->prefix}olama_ev_records r
             INNER JOIN {$wpdb->prefix}olama_ev_templates t ON t.id = r.template_id
             WHERE (r.student_uid = %s OR ((r.student_uid IS NULL OR r.student_uid = '')
                 AND r.student_id IN (SELECT id FROM {$wpdb->prefix}olama_students WHERE student_uid = %s)))
             AND r.academic_year_id = %d AND r.semester_id = %d
             AND r.context_type = 'student' AND t.context_type = 'student' AND r.status = 'published'
             ORDER BY r.updated_at DESC, r.id DESC", $uid, $uid, $year, $semester
        ));
        if ($wpdb->last_error) {
            return new WP_Error('olama_gateway_evaluations_failed', 'تعذر تحميل التقييمات. يرجى المحاولة لاحقاً.');
        }
        $evaluations = array();
        foreach ((array) $records as $record) {
            $config = Olama_School_EV_Template::get_score_config($record->template_id);
            $scores = Olama_School_EV_Record::get_scores($record->id);
            $domains = array();
            foreach (Olama_School_EV_Curriculum::get_full_curriculum($record->template_id) as $domain) {
                $categories = array();
                foreach ($domain->categories as $category) {
                    $indicators = array();
                    foreach ($category->indicators as $indicator) {
                        $score = isset($scores[$indicator->id]) ? $scores[$indicator->id]->score : null;
                        $indicators[] = array('text' => $indicator->indicator_text,
                            'notes' => isset($scores[$indicator->id]->notes) ? (string) $scores[$indicator->id]->notes : '',
                            'score' => $score, 'result' => null !== $score && isset($config[$score]) ? $config[$score] : 'لم يُقيّم');
                    }
                    $categories[] = array('title' => $category->title_ar, 'indicators' => $indicators);
                }
                $domains[] = array('title' => $domain->title_ar, 'categories' => $categories);
            }
            $evaluations[] = array('id' => absint($record->id), 'comments' => isset($record->supervisor_comments) ? (string) $record->supervisor_comments : '', 'scale' => $config, 'title' => $record->template_name, 'date' => $record->updated_at, 'domains' => $domains);
        }
        return array('evaluations' => $evaluations);
    }
}
