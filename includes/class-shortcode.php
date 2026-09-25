<?php

if (!defined('ABSPATH')) {
    exit;
}

class Olama_Student_Gateway_Shortcode {
    private $providers;
    private $access;

    public function __construct(Olama_Student_Gateway_Provider_Registry $providers) {
        $this->providers = $providers;
        $this->access = new Olama_Student_Gateway_Access_Context();
        add_action('wp_login_failed', array($this, 'redirect_failed_gateway_login'), 10, 2);
    }

    public function redirect_failed_gateway_login($username, $error) {
        if (empty($_POST['olama_gateway_login'])) {
            return;
        }

        $nonce = isset($_POST['olama_gateway_login_nonce'])
            ? sanitize_text_field(wp_unslash($_POST['olama_gateway_login_nonce']))
            : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'olama_student_gateway_login')) {
            return;
        }

        $redirect = isset($_POST['redirect_to'])
            ? wp_validate_redirect(wp_unslash($_POST['redirect_to']), home_url('/'))
            : home_url('/');

        wp_safe_redirect(add_query_arg('og_login', 'failed', $redirect));
        exit;
    }

    public function render($atts = array()) {
        wp_enqueue_style('olama-student-gateway');
        wp_enqueue_script('olama-student-gateway');
        $late_styles = $this->late_style_markup();

        if (!is_user_logged_in()) {
            return $late_styles . $this->render_login();
        }
        if (!current_user_can('olama_student_gateway_access')) {
            return $late_styles . $this->notice(__('Your account does not have access to the Student Gateway.', 'olama-student-gateway'), 'error');
        }

        $context = $this->access->current();
        if (is_wp_error($context)) {
            return $late_styles . $this->notice($context->get_error_message(), 'error', true);
        }

        $ministry_notice = '';
        if (isset($_POST['olama_ministry_submit'])) {
            $nonce = isset($_POST['_olama_ministry_nonce']) ? sanitize_text_field(wp_unslash($_POST['_olama_ministry_nonce'])) : '';
            if (!wp_verify_nonce($nonce, 'olama_ministry_submit')) {
                $ministry_notice = __('تعذر التحقق من الطلب. أعد تحميل الصفحة.', 'olama-student-gateway');
            } elseif (!empty($context['is_temp_family']) || !function_exists('olama_core') ||
                !get_option('olama_ministry_family_enabled', false)) {
                $ministry_notice = __('خدمة استكمال البيانات الإحصائية غير متاحة لهذا الحساب حالياً.', 'olama-student-gateway');
            } else {
                $posted_uid = isset($_POST['ministry_student_uid']) ? sanitize_text_field(wp_unslash($_POST['ministry_student_uid'])) : '';
                $authorized = $this->access->select_student($context, $posted_uid);
                if (is_wp_error($authorized) || !$authorized) {
                    $ministry_notice = __('تعذر التحقق من الطالب.', 'olama-student-gateway');
                } else {
                    $key = isset($_POST['ministry_field_key']) ? sanitize_key(wp_unslash($_POST['ministry_field_key'])) : '';
                    $value = isset($_POST['ministry_value']) ? wp_unslash($_POST['ministry_value']) : '';
                    $draft = isset($_POST['ministry_save_draft']);
                    $saved = olama_core()->student_statistics()->submit($posted_uid, $context['study_year'], $key, $value, get_current_user_id(), $draft);
                    $ministry_notice = is_wp_error($saved) ? $saved->get_error_message() :
                        ($draft ? __('تم حفظ المسودة.', 'olama-student-gateway') : __('تم إرسال المعلومة للمراجعة.', 'olama-student-gateway'));
                }
            }
        }

        $exam_view = isset($_GET['exam_view']) ? sanitize_key(wp_unslash($_GET['exam_view'])) : '';
        $requested_student = isset($_GET['og_student']) ? wp_unslash($_GET['og_student']) : '';
        if (!$requested_student && in_array($exam_view, array('dashboard', 'take', 'results', 'demo'), true) && isset($_GET['student_uid'])) {
            // Exam Engine back links carry student_uid. Revalidate it through
            // the gateway ownership check before using it as the selection.
            $requested_student = wp_unslash($_GET['student_uid']);
        }
        $student = $this->access->select_student($context, $requested_student);
        if (is_wp_error($student)) {
            return $late_styles . $this->notice($student->get_error_message(), 'error');
        }
        if ($student) {
            $context['student'] = $student;
        } elseif (!empty($context['is_temp_family']) && !empty($context['students'])) {
            $student = reset($context['students']);
            $context['student'] = $student;
        }

        $views = $this->allowed_views((bool) $student, !empty($context['is_temp_family']));
        if (empty($context['study_year'])) unset($views['ministry']);
        $requested_view = isset($_GET['og_view']) ? sanitize_key(wp_unslash($_GET['og_view'])) : '';
        $message_mode = isset($_GET['og_message']) ? sanitize_key(wp_unslash($_GET['og_message'])) : 'inbox';
        if (!in_array($message_mode, array('compose', 'inbox'), true)) {
            $message_mode = 'inbox';
        }
        $exam_section = isset($_GET['og_exam_section']) ? sanitize_key(wp_unslash($_GET['og_exam_section'])) : '';
        if (!in_array($exam_section, array('schedule', 'online', 'short', 'finished', 'online-results', 'hall'), true)) {
            $exam_section = '';
        }
        if (!$requested_view && $exam_view && isset($views['exams'])) {
            $requested_view = 'exams';
        }
        $active_view = $requested_view && isset($views[$requested_view]) ? $requested_view : ($student ? 'dashboard' : 'family');
        $base_url = get_permalink();
        if (!$base_url) {
            $base_url = home_url('/');
        }

        $model = array(
            'base_url' => $base_url,
            'context' => $context,
            'student' => $student,
            'views' => $views,
            'active_view' => $active_view,
            'message_mode' => $message_mode,
            'exam_section' => $exam_section,
            'data' => $this->load_view_data($active_view, $context, $message_mode),
            'logout_url' => wp_logout_url($base_url),
            'ministry_notice' => $ministry_notice,
        );

        ob_start();
        include OLAMA_STUDENT_GATEWAY_PATH . 'templates/gateway.php';
        return $late_styles . ob_get_clean();
    }

    /**
     * Members and other content guards may evaluate the gateway shortcode after
     * wp_head has already printed. In that case a normal enqueue is too late,
     * so print only the gateway styles (and Dashicons dependency) beside the
     * shortcode output instead of leaving the portal unstyled.
     */
    private function late_style_markup() {
        if (!did_action('wp_head')) {
            return '';
        }

        $exam_view = isset($_GET['exam_view']) ? sanitize_key(wp_unslash($_GET['exam_view'])) : '';
        $gateway_view = isset($_GET['og_view']) ? sanitize_key(wp_unslash($_GET['og_view'])) : '';
        $is_embedded_exam = 'exams' === $gateway_view && in_array($exam_view, array('take', 'results'), true);

        if ($is_embedded_exam) {
            // Some optimization/access stacks mark the registered gateway
            // handle as done even though its link tag was omitted. The Exam
            // Engine already uses a direct late link successfully, so mirror
            // that behavior for the shell on embedded exam routes.
            $markup = '<link rel="stylesheet" id="olama-student-gateway-embedded" href="'
                . esc_url(OLAMA_STUDENT_GATEWAY_URL . 'assets/css/gateway.css?ver=' . OLAMA_STUDENT_GATEWAY_VERSION)
                . '" type="text/css" media="all" />';

            if (!wp_style_is('dashicons', 'done')) {
                $markup .= '<link rel="stylesheet" id="dashicons-gateway-embedded" href="'
                    . esc_url(includes_url('css/dashicons.min.css'))
                    . '" type="text/css" media="all" />';
            }
            return $markup;
        }

        if (wp_style_is('olama-student-gateway', 'done')) {
            return '';
        }

        ob_start();
        wp_print_styles(array('dashicons', 'olama-student-gateway'));
        return (string) ob_get_clean();
    }

    private function allowed_views($has_student, $is_temp_family = false) {
        $views = $is_temp_family ? array() : array(
            'family' => array('label' => __('بطاقة العائلة', 'olama-student-gateway'), 'icon' => 'dashicons-groups'),
        );
        if ($has_student) {
            $views['dashboard'] = array('label' => __('لوحة المتابعة', 'olama-student-gateway'), 'icon' => 'dashicons-dashboard');
            if (!$is_temp_family && function_exists('olama_core') &&
                get_option('olama_ministry_family_enabled', false)) {
                $views['ministry'] = array('label' => __('البيانات الإحصائية', 'olama-student-gateway'), 'icon' => 'dashicons-id-alt');
            }
            $map = array(
                'weekly_plan' => array('olama_student_gateway_weekly_plan_view', __('الخطة الأسبوعية', 'olama-student-gateway'), 'dashicons-calendar-alt'),
                'schedule' => array('olama_student_gateway_schedule_view', __('الجدول الدراسي', 'olama-student-gateway'), 'dashicons-schedule'),
                'teachers' => array('olama_student_gateway_teachers_view', __('الساعات المكتبية', 'olama-student-gateway'), 'dashicons-welcome-learn-more'),
                'video_library' => array('olama_student_gateway_video_library_view', __('مكتبة الفيديو', 'olama-student-gateway'), 'dashicons-video-alt3'),
                'exams' => array('olama_student_gateway_exams_view', $is_temp_family ? __('الامتحانات الإلكترونية', 'olama-student-gateway') : __('الامتحانات والنتائج', 'olama-student-gateway'), 'dashicons-clipboard'),
                'evaluations' => array('olama_student_gateway_evaluations_view', __('التقييمات', 'olama-student-gateway'), 'dashicons-star-filled'),
                'attendance' => array('olama_student_gateway_attendance_view', __('الحضور والغياب', 'olama-student-gateway'), 'dashicons-yes-alt'),
                'transportation' => array('olama_student_gateway_transportation_view', __('المواصلات', 'olama-student-gateway'), 'dashicons-location-alt'),
                'stores' => array('olama_student_gateway_stores_view', __('الزي والكتب', 'olama-student-gateway'), 'dashicons-archive'),
                'messages' => array('olama_student_gateway_messages_view', __('صندوق البريد', 'olama-student-gateway'), 'dashicons-email-alt'),
            );
            foreach ($map as $key => $definition) {
                if (current_user_can($definition[0])) {
                    $views[$key] = array('label' => $definition[1], 'icon' => $definition[2]);
                }
            }
        }
        return $views;
    }

    private function load_view_data($view, array $context, $message_mode = 'inbox') {
        if ('ministry' === $view && !empty($context['student']) && function_exists('olama_core')) {
            return olama_core()->student_statistics()->evaluate($context['student']['student_uid'], $context['study_year']);
        }
        if ('family' === $view) {
            $data = $this->providers->data('core', $context, array('resource' => 'family'));
            if (current_user_can('olama_student_gateway_finance_view')) {
                $data['finance'] = $this->providers->data('core', $context, array('resource' => 'finance'));
            }
            if (
                current_user_can('olama_student_gateway_transportation_view')
                && $this->providers->available('transportation')
                && !empty($context['students'])
            ) {
                // Transportation is a shared family arrangement. The first
                // student is the canonical display record for the family card.
                $first_student = reset($context['students']);
                $student_context = $context;
                $student_context['student'] = $first_student;
                $data['transportation'] = $this->providers->data('transportation', $student_context);
                $data['transportation_student'] = $first_student;
            }
            return $data;
        }
        if ('dashboard' === $view) {
            $data = array();

            if (
                current_user_can('olama_student_gateway_weekly_plan_view')
                && $this->providers->available('weekly_plan')
            ) {
                $data['weekly_plan'] = $this->providers->data('weekly_plan', $context, array('week' => ''));
            }

            if (
                current_user_can('olama_student_gateway_exams_view')
                && $this->providers->available('exams')
            ) {
                $data['exams'] = $this->providers->data('exams', $context);
            }

            return $data;
        }
        if ('weekly_plan' === $view) {
            $week = isset($_GET['og_week']) ? wp_unslash($_GET['og_week']) : '';
            return $this->providers->data('weekly_plan', $context, array('week' => $week));
        }
        if ('schedule' === $view || 'teachers' === $view) {
            return $this->providers->data('school_context', $context, array('resource' => $view));
        }
        if ('video_library' === $view) {
            return $this->providers->data('video_library', $context);
        }
        if ('stores' === $view) {
            return $this->providers->data('stores', $context);
        }
        if ('transportation' === $view) {
            return $this->providers->data('transportation', $context);
        }
        if ('messages' === $view) {
            return $this->providers->data('messages', $context, array('mode' => $message_mode));
        }
        return $this->providers->data($view, $context);
    }

    private function render_login() {
        ob_start();
        ?>
        <div class="olama-gateway-login" dir="rtl">
            <div class="olama-gateway-login__intro">
                <span class="olama-gateway-logo">ع</span>
                <span class="olama-gateway-login__eyebrow"><?php esc_html_e('أكاديمية علماء المستقبل', 'olama-student-gateway'); ?></span>
                <h2><?php esc_html_e('كل ما يخص أبناءك في مكان واحد', 'olama-student-gateway'); ?></h2>
                <p><?php esc_html_e('تابع الخطط الأسبوعية والامتحانات والمواصلات والخدمات المنشورة للعائلة من خلال حساب آمن واحد.', 'olama-student-gateway'); ?></p>
                <ul class="olama-gateway-login__benefits">
                    <li><span class="dashicons dashicons-groups" aria-hidden="true"></span><?php esc_html_e('الوصول إلى جميع الأبناء المرتبطين بالعائلة', 'olama-student-gateway'); ?></li>
                    <li><span class="dashicons dashicons-shield-alt" aria-hidden="true"></span><?php esc_html_e('عرض المعلومات التي تسمح بها صلاحيات حسابك فقط', 'olama-student-gateway'); ?></li>
                    <li><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('بيانات منشورة من أنظمة OLAMA المعتمدة', 'olama-student-gateway'); ?></li>
                </ul>
            </div>
            <div class="olama-gateway-login__form">
                <div class="olama-gateway-login__form-head">
                    <span class="olama-gateway-login__eyebrow"><?php esc_html_e('بوابة العائلة', 'olama-student-gateway'); ?></span>
                    <h2><?php esc_html_e('تسجيل الدخول', 'olama-student-gateway'); ?></h2>
                    <p><?php esc_html_e('استخدم رقم العائلة، أو اسم المستخدم المؤقت، وكلمة المرور المسجلين لديك.', 'olama-student-gateway'); ?></p>
                </div>
                <?php
                if (isset($_GET['og_login']) && 'failed' === sanitize_key(wp_unslash($_GET['og_login']))) {
                    echo '<div class="olama-gateway-notice olama-gateway-notice--error olama-gateway-login__error" role="alert">'
                        . esc_html__('Invalid username or password. Please try again.', 'olama-student-gateway')
                        . '</div>';
                }

                wp_login_form(array(
                    'redirect' => get_permalink() ? get_permalink() : home_url('/'),
                    'label_username' => __('Family number or temporary username', 'olama-student-gateway'),
                    'label_password' => __('Password', 'olama-student-gateway'),
                    'label_remember' => __('Remember me', 'olama-student-gateway'),
                    'label_log_in' => __('Sign in', 'olama-student-gateway'),
                    'remember' => true,
                    'login_form_middle' => sprintf(
                        '<input type="hidden" name="olama_gateway_login" value="1"><input type="hidden" name="olama_gateway_login_nonce" value="%s">',
                        esc_attr(wp_create_nonce('olama_student_gateway_login'))
                    ),
                ));
                ?>
                <p class="olama-gateway-login__security"><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e('لن تظهر أي معلومات قبل التحقق من الحساب والصلاحيات.', 'olama-student-gateway'); ?></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function notice($message, $type = 'info', $logout = false) {
        $output = '<div class="olama-gateway-notice olama-gateway-notice--' . esc_attr($type) . '" dir="rtl">' . esc_html($message);
        if ($logout) {
            $output .= ' <a href="' . esc_url(wp_logout_url(get_permalink() ? get_permalink() : home_url('/'))) . '">' . esc_html__('Sign out', 'olama-student-gateway') . '</a>';
        }
        return $output . '</div>';
    }
}
