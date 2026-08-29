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
            return array(
                'weekly_plan' => $this->providers->available('weekly_plan')
                    ? $this->providers->data('weekly_plan', $context, array('week' => ''))
                    : array(),
                'transportation' => $this->providers->data('transportation', $context),
            );
        }
        if ('weekly_plan' === $view) {
            $week = isset($_GET['og_week']) ? wp_unslash($_GET['og_week']) : '';
            return $this->providers->data('weekly_plan', $context, array('week' => $week));
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
                <h2><?php esc_html_e('OLAMA Family Gateway', 'olama-student-gateway'); ?></h2>
                <p><?php esc_html_e('Use the existing family number and password to view all family members and school information.', 'olama-student-gateway'); ?></p>
            </div>
            <div class="olama-gateway-login__form">
                <?php
                wp_login_form(array(
                    'redirect' => get_permalink() ? get_permalink() : home_url('/'),
                    'label_username' => __('Family number', 'olama-student-gateway'),
                    'label_password' => __('Password', 'olama-student-gateway'),
                    'label_remember' => __('Remember me', 'olama-student-gateway'),
                    'label_log_in' => __('Sign in', 'olama-student-gateway'),
                    'remember' => true,
                ));
                ?>
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
