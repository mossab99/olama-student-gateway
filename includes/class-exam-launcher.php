<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds student-safe links into the Exam Engine flow hosted by the gateway.
 *
 * Starting and resuming remain owned by the Exam Engine so its ownership,
 * password, time-window and attempt-limit checks cannot be bypassed here.
 */
final class Olama_Student_Gateway_Exam_Launcher {
    public static function action($exam, $student_uid, $gateway_url) {
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
            'label' => __('بدء أو استكمال الامتحان', 'olama-student-gateway'),
            'url' => add_query_arg(
                array(
                    'exam_view' => 'take',
                    'exam_id' => $exam_id,
                    'student_uid' => sanitize_text_field((string) $student_uid),
                ),
                $gateway_url
            ),
        );
    }

    private static function field($record, $key) {
        if (is_object($record)) {
            return isset($record->{$key}) ? $record->{$key} : '';
        }
        return is_array($record) && isset($record[$key]) ? $record[$key] : '';
    }
}
