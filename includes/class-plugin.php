<?php

if (!defined('ABSPATH')) {
    exit;
}

final class Olama_Student_Gateway_Plugin {
    private static $instance;
    private $providers;
    private $shortcode;

    public static function instance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        load_plugin_textdomain('olama-student-gateway', false, dirname(plugin_basename(OLAMA_STUDENT_GATEWAY_FILE)) . '/languages');
        add_action('olama_users_register_modules', array($this, 'register_access_module'));
        add_action('init', array($this, 'register_assets'), 10);
        add_action('init', array($this, 'register_shortcode'), 20);
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_assets'));
        add_action('template_redirect', array($this, 'protect_portal_response'));
        add_filter('wp_robots', array($this, 'portal_robots'));
        add_filter('members_post_error_message', array($this, 'replace_members_denial_with_gateway'), 29);
    }

    public function register_access_module() {
        if (!function_exists('olama_users_register_module')) {
            return;
        }
        $family_roles = array('family', 'olama_family');
        if (class_exists('Olama_Users_Roles')) {
            $configured_family_role = Olama_Users_Roles::default_role('family');
            if ($configured_family_role) {
                $family_roles[] = $configured_family_role;
            }
        }
        olama_users_register_module(array(
            'id' => 'olama_student_gateway',
            'plugin' => 'olama-student-gateway',
            'label' => __('Student Gateway', 'olama-student-gateway'),
            'capability' => 'olama_student_gateway_access',
            'default_grant' => true,
            'default_grant_roles' => array_values(array_unique($family_roles)),
            // Family roles receive the complete portal by default. OLAMA Users
            // records each seed once, so administrators can still revoke any
            // capability later without it being silently restored.
            'default_grant_capabilities' => array(
                'olama_student_gateway_family_view',
                'olama_student_gateway_weekly_plan_view',
                'olama_student_gateway_schedule_view',
                'olama_student_gateway_teachers_view',
                'olama_student_gateway_exams_view',
                'olama_student_gateway_evaluations_view',
                'olama_student_gateway_attendance_view',
                'olama_student_gateway_transportation_view',
                'olama_student_gateway_stores_view',
                'olama_student_gateway_messages_view',
            ),
            'items' => array(
                array('id' => 'gateway.family', 'type' => 'feature', 'label' => __('Family card', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_family_view'),
                array('id' => 'gateway.finance', 'type' => 'feature', 'label' => __('Financial card', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_finance_view'),
                array('id' => 'gateway.weekly_plan', 'type' => 'feature', 'label' => __('Weekly plan', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_weekly_plan_view'),
                array('id' => 'gateway.schedule', 'type' => 'feature', 'label' => __('Class schedule', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_schedule_view'),
                array('id' => 'gateway.teachers', 'type' => 'feature', 'label' => __('Teachers and office hours', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_teachers_view'),
                array('id' => 'gateway.exams', 'type' => 'feature', 'label' => __('Exams', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_exams_view'),
                array('id' => 'gateway.evaluations', 'type' => 'feature', 'label' => __('Evaluations', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_evaluations_view'),
                array('id' => 'gateway.attendance', 'type' => 'feature', 'label' => __('Attendance', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_attendance_view'),
                array('id' => 'gateway.transportation', 'type' => 'feature', 'label' => __('Transportation', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_transportation_view'),
                array('id' => 'gateway.stores', 'type' => 'feature', 'label' => __('School supplies', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_stores_view'),
                array('id' => 'gateway.messages', 'type' => 'feature', 'label' => __('Messages', 'olama-student-gateway'), 'capability' => 'olama_student_gateway_messages_view'),
            ),
        ));
    }

    public function register_shortcode() {
        $this->shortcode = new Olama_Student_Gateway_Shortcode($this->providers());
        add_shortcode('olama_student_gateway', array($this->shortcode, 'render'));
        // Keep the original family gateway shortcode working for existing pages.
        add_shortcode('olama_family_gateway', array($this->shortcode, 'render'));
    }

    public function register_assets() {
        wp_register_style(
            'olama-student-gateway',
            OLAMA_STUDENT_GATEWAY_URL . 'assets/css/gateway.css',
            array('dashicons'),
            OLAMA_STUDENT_GATEWAY_VERSION
        );
        wp_register_script(
            'olama-student-gateway',
            OLAMA_STUDENT_GATEWAY_URL . 'assets/js/gateway.js',
            array(),
            OLAMA_STUDENT_GATEWAY_VERSION,
            true
        );
    }

    public function maybe_enqueue_assets() {
        if ($this->is_portal_request()) {
            wp_enqueue_style('olama-student-gateway');
            wp_enqueue_script('olama-student-gateway');
        }
    }

    public function protect_portal_response() {
        if (!$this->is_portal_request()) {
            return;
        }
        if (!defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true);
    }

    public function portal_robots($robots) {
        if ($this->is_portal_request()) {
            $robots['noindex'] = true;
            $robots['nofollow'] = true;
            $robots['noarchive'] = true;
        }
        return $robots;
    }

    /**
     * Let the gateway perform its own guest login and capability checks when the
     * Members plugin protects the containing page. Other page content remains
     * protected because only the denial message is replaced.
     */
    public function replace_members_denial_with_gateway($message) {
        if (is_admin() || is_feed()) {
            return $message;
        }

        $post = get_post(get_the_ID());
        if (!$post instanceof WP_Post) {
            return $message;
        }

        if (
            has_shortcode($post->post_content, 'olama_student_gateway')
            || has_shortcode($post->post_content, 'olama_family_gateway')
        ) {
            // Members runs do_shortcode() immediately after this filter.
            return '[olama_student_gateway]';
        }

        return $message;
    }

    private function is_portal_request() {
        if (!is_singular()) {
            return false;
        }
        $post = get_queried_object();
        if (!$post instanceof WP_Post) {
            return false;
        }

        return has_shortcode($post->post_content, 'olama_student_gateway')
            || has_shortcode($post->post_content, 'olama_family_gateway');
    }

    public function providers() {
        if ($this->providers) {
            return $this->providers;
        }
        $registry = new Olama_Student_Gateway_Provider_Registry();
        $registry->register(new Olama_Student_Gateway_Core_Provider());
        $registry->register(new Olama_Student_Gateway_Weekly_Plan_Provider());
        $registry->register(new Olama_Student_Gateway_School_Context_Provider());
        $registry->register(new Olama_Student_Gateway_Exams_Provider());
        $registry->register(new Olama_Student_Gateway_Transportation_Provider());
        $registry->register(new Olama_Student_Gateway_Stores_Provider());
        foreach (array('evaluations', 'attendance', 'messages') as $key) {
            $registry->register(new Olama_Student_Gateway_Filter_Provider($key));
        }
        do_action('olama_student_gateway_register_providers', $registry);
        $this->providers = $registry;
        return $this->providers;
    }
}
