<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Browser-only exam experience for local Temp Family members.
 *
 * There is intentionally no form action, attempt ID, nonce for submission,
 * autosave request, grading call, or result persistence in this flow.
 */
final class Olama_Student_Gateway_Demo_Exam {
    public static function render($exam_id, array $available_exams, $student_uid, $back_url) {
        if (!class_exists('Olama_Exam_Manager')) {
            return self::notice(__('محرك الامتحانات غير متاح حالياً.', 'olama-student-gateway'));
        }

        $exam = self::find_exam($exam_id, $available_exams);
        if (!$exam) {
            return self::notice(__('هذا الامتحان غير متاح للعضو المحدد.', 'olama-student-gateway'));
        }
        $action = Olama_Student_Gateway_Exam_Launcher::action($exam, $student_uid, $back_url);
        if ('available' !== $action['state']) {
            return self::notice($action['label']);
        }

        $preview = Olama_Exam_Manager::preview_exam(absint($exam_id));
        if (is_wp_error($preview) || empty($preview['exam'])) {
            $message = is_wp_error($preview)
                ? $preview->get_error_message()
                : __('تعذر تحميل الامتحان.', 'olama-student-gateway');
            return self::notice($message);
        }
        $questions = !empty($preview['questions']) ? (array) $preview['questions'] : array();
        if (!$questions) {
            return self::notice(__('لا توجد أسئلة منشورة في هذا الامتحان.', 'olama-student-gateway'));
        }

        ob_start();
        ?>
        <div class="olama-gateway__demo-exam" data-demo-exam data-question-count="<?php echo esc_attr(count($questions)); ?>">
            <div class="olama-gateway__demo-banner" role="status">
                <span class="dashicons dashicons-visibility" aria-hidden="true"></span>
                <div>
                    <strong><?php esc_html_e('وضع العرض التجريبي', 'olama-student-gateway'); ?></strong>
                    <p><?php esc_html_e('لن تُحفظ الإجابات أو المحاولات أو العلامات. تختفي الإجابات عند مغادرة الصفحة أو تحديثها.', 'olama-student-gateway'); ?></p>
                </div>
            </div>
            <header class="olama-gateway__demo-header">
                <div>
                    <span><?php echo esc_html(self::field($preview['exam'], 'subject_name')); ?></span>
                    <h3><?php echo esc_html(self::field($preview['exam'], 'title')); ?></h3>
                </div>
                <div class="olama-gateway__demo-progress">
                    <strong data-demo-progress>0 / <?php echo esc_html(count($questions)); ?></strong>
                    <small><?php esc_html_e('تمت الإجابة', 'olama-student-gateway'); ?></small>
                </div>
            </header>
            <div class="olama-gateway__demo-questions">
                <?php foreach ($questions as $index => $question) : ?>
                    <?php self::render_question($question, $index + 1); ?>
                <?php endforeach; ?>
            </div>
            <div class="olama-gateway__demo-finish">
                <button type="button" class="olama-gateway__button" data-demo-finish><?php esc_html_e('إنهاء العرض التجريبي', 'olama-student-gateway'); ?></button>
                <div class="olama-gateway__demo-message" data-demo-message hidden></div>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    private static function find_exam($exam_id, array $available_exams) {
        $exam_id = absint($exam_id);
        foreach ($available_exams as $exam) {
            if ($exam_id && $exam_id === absint(self::field($exam, 'id'))) {
                return $exam;
            }
        }
        return null;
    }

    private static function render_question($question, $number) {
        $type = sanitize_key((string) self::field($question, 'type'));
        $answers = json_decode((string) self::field($question, 'answers_json'), true);
        $answers = is_array($answers) ? $answers : array();
        $question_text = (string) self::field($question, 'question_text');
        $question_id = absint(self::field($question, 'id'));
        ?>
        <article class="olama-gateway__demo-question" data-demo-question>
            <div class="olama-gateway__demo-question-number"><?php echo esc_html($number); ?></div>
            <?php if (self::field($question, 'image_filename')) : ?>
                <img class="olama-gateway__demo-question-image" src="<?php echo esc_url(add_query_arg(array('action' => 'olama_exam_stream_image', 'file' => self::field($question, 'image_filename'), 'nonce' => wp_create_nonce('olama_exam_nonce')), admin_url('admin-ajax.php'))); ?>" alt="">
            <?php endif; ?>
            <div class="olama-gateway__demo-question-text"><?php echo wp_kses_post($question_text); ?></div>
            <div class="olama-gateway__demo-answer">
                <?php self::render_answer($type, $answers, 'demo-q-' . $question_id, $question_text); ?>
            </div>
        </article>
        <?php
    }

    private static function render_answer($type, array $answers, $group, $question_text) {
        if ('mcq' === $type) {
            foreach ((array) ($answers['choices'] ?? array()) as $choice) {
                echo '<label class="olama-gateway__demo-choice"><input type="radio" name="' . esc_attr($group) . '" data-demo-answer><span>' . esc_html($choice) . '</span></label>';
            }
            return;
        }
        if ('tf' === $type) {
            echo '<div class="olama-gateway__demo-binary"><label><input type="radio" name="' . esc_attr($group) . '" data-demo-answer><span>' . esc_html__('صح', 'olama-student-gateway') . '</span></label><label><input type="radio" name="' . esc_attr($group) . '" data-demo-answer><span>' . esc_html__('خطأ', 'olama-student-gateway') . '</span></label></div>';
            return;
        }
        if ('matching' === $type) {
            $pairs = (array) ($answers['pairs'] ?? array());
            $rights = (array) ($answers['shuffled_rights'] ?? array());
            foreach ($pairs as $pair) {
                echo '<label class="olama-gateway__demo-match"><span>' . esc_html(isset($pair['left']) ? $pair['left'] : '') . '</span><select data-demo-answer><option value="">' . esc_html__('اختر المطابقة', 'olama-student-gateway') . '</option>';
                foreach ($rights as $right) {
                    echo '<option>' . esc_html($right) . '</option>';
                }
                echo '</select></label>';
            }
            return;
        }
        if ('ordering' === $type) {
            $items = (array) ($answers['shuffled_items'] ?? array());
            foreach ($items as $item) {
                echo '<label class="olama-gateway__demo-order"><select data-demo-answer><option value="">—</option>';
                for ($position = 1; $position <= count($items); $position++) {
                    echo '<option value="' . esc_attr($position) . '">' . esc_html($position) . '</option>';
                }
                echo '</select><span>' . esc_html($item) . '</span></label>';
            }
            return;
        }
        if ('essay' === $type) {
            echo '<textarea rows="6" data-demo-answer placeholder="' . esc_attr__('اكتب إجابتك هنا…', 'olama-student-gateway') . '"></textarea>';
            return;
        }

        $blank_count = 'fill_blank' === $type ? preg_match_all('/_{3,}/', $question_text, $unused) : 0;
        if ($blank_count > 1) {
            for ($index = 1; $index <= $blank_count; $index++) {
                echo '<input type="text" data-demo-answer placeholder="' . esc_attr(sprintf(__('إجابة الفراغ %d', 'olama-student-gateway'), $index)) . '">';
            }
            return;
        }
        echo '<input type="text" data-demo-answer placeholder="' . esc_attr__('اكتب إجابتك هنا…', 'olama-student-gateway') . '">';
    }

    private static function notice($message) {
        return '<div class="olama-gateway__empty">' . esc_html($message) . '</div>';
    }

    private static function field($record, $key) {
        if (is_object($record) && isset($record->{$key})) {
            return $record->{$key};
        }
        return is_array($record) && isset($record[$key]) ? $record[$key] : '';
    }
}
