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

        if (!is_user_logged_in()) {
            return $this->render_login();
        }
        if (!current_user_can('olama_student_gateway_access')) {
            return $this->notice(__('Your account does not have access to the Student Gateway.', 'olama-student-gateway'), 'error');
        }

        $context = $this->access->current();
        if (is_wp_error($context)) {
            return $this->notice($context->get_error_message(), 'error', true);
        }

        $requested_student = isset($_GET['og_student']) ? wp_unslash($_GET['og_student']) : '';
        $student = $this->access->select_student($context, $requested_student);
        if (is_wp_error($student)) {
            return $this->notice($student->get_error_message(), 'error');
        }
        if ($student) {
            $context['student'] = $student;
        }

        $views = $this->allowed_views((bool) $student);
        $requested_view = isset($_GET['og_view']) ? sanitize_key(wp_unslash($_GET['og_view'])) : '';
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
            'data' => $this->load_view_data($active_view, $context),
            'logout_url' => wp_logout_url($base_url),
        );

        ob_start();
        include OLAMA_STUDENT_GATEWAY_PATH . 'templates/gateway.php';
        return ob_get_clean();
    }

    private function allowed_views($has_student) {
        $views = array(
            'family' => array('label' => __('الأسرة', 'olama-student-gateway'), 'icon' => 'dashicons-groups'),
        );
        if ($has_student) {
            $views['dashboard'] = array('label' => __('الرئيسية', 'olama-student-gateway'), 'icon' => 'dashicons-dashboard');
            $map = array(
                'weekly_plan' => array('olama_student_gateway_weekly_plan_view', __('الخطة الأسبوعية', 'olama-student-gateway'), 'dashicons-calendar-alt'),
                'schedule' => array('olama_student_gateway_schedule_view', __('الجدول الدراسي', 'olama-student-gateway'), 'dashicons-schedule'),
                'teachers' => array('olama_student_gateway_teachers_view', __('المعلمون والساعات المكتبية', 'olama-student-gateway'), 'dashicons-welcome-learn-more'),
                'video_library' => array('olama_student_gateway_video_library_view', __('مكتبة الفيديو', 'olama-student-gateway'), 'dashicons-video-alt3'),
                'exams' => array('olama_student_gateway_exams_view', __('الامتحانات', 'olama-student-gateway'), 'dashicons-clipboard'),
                'evaluations' => array('olama_student_gateway_evaluations_view', __('التقييمات', 'olama-student-gateway'), 'dashicons-star-filled'),
                'attendance' => array('olama_student_gateway_attendance_view', __('الحضور والغياب', 'olama-student-gateway'), 'dashicons-yes-alt'),
                'transportation' => array('olama_student_gateway_transportation_view', __('المواصلات', 'olama-student-gateway'), 'dashicons-location-alt'),
                'stores' => array('olama_student_gateway_stores_view', __('مستلزمات المدرسة', 'olama-student-gateway'), 'dashicons-archive'),
                'messages' => array('olama_student_gateway_messages_view', __('الرسائل', 'olama-student-gateway'), 'dashicons-email-alt'),
            );
            foreach ($map as $key => $definition) {
                if (current_user_can($definition[0])) {
                    $views[$key] = array('label' => $definition[1], 'icon' => $definition[2]);
                }
            }
        }
        return $views;
    }

    private function load_view_data($view, array $context) {
        if ('family' === $view) {
            $data = $this->providers->data('core', $context, array('resource' => 'family'));
            if (current_user_can('olama_student_gateway_finance_view')) {
                $data['finance'] = $this->providers->data('core', $context, array('resource' => 'finance'));
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

            if (
                current_user_can('olama_student_gateway_transportation_view')
                && $this->providers->available('transportation')
            ) {
                $data['transportation'] = $this->providers->data('transportation', $context);
            }

            if (
                current_user_can('olama_student_gateway_stores_view')
                && $this->providers->available('stores')
            ) {
                $data['stores'] = $this->providers->data('stores', $context);
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
                <p><?php esc_html_e('تابع الخطط الأسبوعية والامتحانات والمواصلات والخدمات المنشورة للأسرة من خلال حساب آمن واحد.', 'olama-student-gateway'); ?></p>
                <ul class="olama-gateway-login__benefits">
                    <li><span class="dashicons dashicons-groups" aria-hidden="true"></span><?php esc_html_e('الوصول إلى جميع الأبناء المرتبطين بالأسرة', 'olama-student-gateway'); ?></li>
                    <li><span class="dashicons dashicons-shield-alt" aria-hidden="true"></span><?php esc_html_e('عرض المعلومات التي تسمح بها صلاحيات حسابك فقط', 'olama-student-gateway'); ?></li>
                    <li><span class="dashicons dashicons-update" aria-hidden="true"></span><?php esc_html_e('بيانات منشورة من أنظمة OLAMA المعتمدة', 'olama-student-gateway'); ?></li>
                </ul>
            </div>
            <div class="olama-gateway-login__form">
                <div class="olama-gateway-login__form-head">
                    <span class="olama-gateway-login__eyebrow"><?php esc_html_e('بوابة الأسرة', 'olama-student-gateway'); ?></span>
                    <h2><?php esc_html_e('تسجيل الدخول', 'olama-student-gateway'); ?></h2>
                    <p><?php esc_html_e('استخدم رقم الأسرة وكلمة المرور المسجلين لديك.', 'olama-student-gateway'); ?></p>
                </div>
                <?php
                if (isset($_GET['og_login']) && 'failed' === sanitize_key(wp_unslash($_GET['og_login']))) {
                    echo '<div class="olama-gateway-notice olama-gateway-notice--error olama-gateway-login__error" role="alert">'
                        . esc_html__('Invalid username or password. Please try again.', 'olama-student-gateway')
                        . '</div>';
                }

                wp_login_form(array(
                    'redirect' => get_permalink() ? get_permalink() : home_url('/'),
                    'label_username' => __('Family number', 'olama-student-gateway'),
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
