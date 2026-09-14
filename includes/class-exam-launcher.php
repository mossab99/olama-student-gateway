<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds student-safe links into the Exam Engine's public exam flow.
 *
 * Starting and resuming remain owned by the Exam Engine so its ownership,
 * password, time-window and attempt-limit checks cannot be bypassed here.
 */
final class Olama_Student_Gateway_Exam_Launcher {
    public static function action($exam, $student_uid) {
        $exam_id = absint(self::field($exam, 'id'));
        $status = sanitize_key((string) self::field($exam, 'status'));
        $start = (string) self::field($exam, 'start_time');
        $end = (string) self::field($exam, 'end_time');
        $now = current_time('mysql');

        if (!$exam_id || !in_array($status, array('published', 'active'), true)) {
            return array('state' => 'unavailable', 'label' => __('غير متاح', 'olama-student-gateway'), 'url' => '');
        }
        if (!$start || !$end) {
            return array('state' => 'unavailable', 'label' => __('الموعد غير مكتمل', 'olama-student-gateway'), 'url' => '');
        }
        if ($now < $start) {
            return array('state' => 'upcoming', 'label' => __('لم يبدأ بعد', 'olama-student-gateway'), 'url' => '');
        }
        if ($now > $end) {
            return array('state' => 'ended', 'label' => __('انتهى الامتحان', 'olama-student-gateway'), 'url' => '');
        }

        return array(
            'state' => 'available',
            'label' => __('بدء / استكمال الامتحان', 'olama-student-gateway'),
            'url' => add_query_arg(
                array(
                    'exam_view' => 'take',
                    'exam_id' => $exam_id,
                    'student_uid' => sanitize_text_field((string) $student_uid),
                ),
                self::exam_page_url()
            ),
        );
    }

    public static function exam_page_url() {
        $filtered = apply_filters('olama_student_gateway_exam_engine_url', '');
        if (is_string($filtered) && '' !== trim($filtered)) {
            return esc_url_raw($filtered);
        }

        static $resolved_url = '';
        if ($resolved_url) {
            return $resolved_url;
        }

        $pages = get_posts(array(
            'post_type' => 'page',
            'post_status' => 'publish',
            's' => '[olama_exam]',
            'posts_per_page' => 20,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));
        foreach ((array) $pages as $page) {
            if (isset($page->post_content) && has_shortcode($page->post_content, 'olama_exam')) {
                $permalink = get_permalink($page->ID);
                if ($permalink) {
                    $resolved_url = $permalink;
                    return $resolved_url;
                }
            }
        }

        $resolved_url = home_url('/exams/');
        return $resolved_url;
    }

    private static function field($record, $key) {
        if (is_object($record)) {
            return isset($record->{$key}) ? $record->{$key} : '';
        }
        return is_array($record) && isset($record[$key]) ? $record[$key] : '';
    }
}
