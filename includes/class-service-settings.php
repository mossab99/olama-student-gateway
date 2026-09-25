<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Site-wide presentation controls for the family gateway. */
final class Olama_Student_Gateway_Service_Settings {
    const OPTION = 'olama_student_gateway_hidden_services';

    public static function groups() {
        return array(
            'main' => array('label' => 'الخدمات الرئيسية', 'items' => array(
                'dashboard' => 'لوحة المتابعة',
                'ministry' => 'استكمال البيانات الإحصائية',
            )),
            'family' => array('label' => 'معلومات العائلة', 'items' => array(
                'family' => 'بطاقة العائلة',
                'stores' => 'الزي والكتب',
                'transportation' => 'المواصلات',
            )),
            'daily' => array('label' => 'المتابعة اليومية', 'items' => array(
                'weekly_plan' => 'الخطة الأسبوعية',
                'schedule' => 'الجدول الدراسي',
                'teachers' => 'الساعات المكتبية',
            )),
            'video' => array('label' => 'مكتبة الفيديو', 'items' => array(
                'video_library' => 'مكتبة الفيديو',
            )),
            'performance' => array('label' => 'التقييم والأداء', 'items' => array(
                'attendance' => 'الحضور والغياب',
                'evaluations' => 'التقييمات',
            )),
            'exams' => array('label' => 'الامتحانات', 'items' => array(
                'exams.schedule' => 'جدول الامتحانات',
                'exams.online' => 'اختبارات التقويم',
                'exams.short' => 'الاختبارات القصيرة',
                'exams.finished' => 'الاختبارات المنجزة',
                'exams.online-results' => 'نتائج الامتحانات الإلكترونية',
                'exams.hall' => 'قاعات الامتحان',
            )),
            'messages' => array('label' => 'الرسائل', 'items' => array(
                'messages.compose' => 'إرسال رسالة',
                'messages.inbox' => 'صندوق البريد',
            )),
        );
    }

    public static function keys() {
        $keys = array();
        foreach (self::groups() as $group) {
            $keys = array_merge($keys, array_keys($group['items']));
        }
        return $keys;
    }

    public static function hidden() {
        $stored = function_exists('get_option') ? get_option(self::OPTION, array()) : array();
        return is_array($stored) ? array_values(array_intersect(self::keys(), $stored)) : array();
    }

    public static function visible($key) {
        return !in_array($key, self::hidden(), true);
    }

    public static function hidden_from_selection($selected) {
        $selected = is_array($selected) ? array_map('sanitize_text_field', $selected) : array();
        return array_values(array_diff(self::keys(), $selected));
    }

    private static function availability_note($key) {
        if ('ministry' === $key) {
            return function_exists('olama_core') && get_option('olama_ministry_family_enabled', false) ? '' : 'يتطلب تفعيل خدمة البيانات الإحصائية في OLAMA Core.';
        }
        $provider = explode('.', $key)[0];
        $provider = in_array($provider, array('schedule', 'teachers'), true) ? 'school_context' : $provider;
        $provider = 'family' === $provider ? 'core' : $provider;
        if (in_array($provider, array('dashboard'), true)) return '';
        $registry = Olama_Student_Gateway_Plugin::instance()->providers();
        return $registry->available($provider) ? '' : 'مصدر البيانات غير متاح حالياً.';
    }

    public static function exam_sections($is_temp_family = false) {
        $sections = array('schedule', 'online', 'short', 'finished', 'online-results', 'hall');
        if ($is_temp_family) {
            $sections = array('online', 'short', 'finished');
        }
        return array_values(array_filter($sections, static function ($section) {
            return self::visible('exams.' . $section);
        }));
    }

    public static function register_admin_menu() {
        add_menu_page('بوابة الطالب', 'بوابة الطالب', 'manage_options', 'olama-student-gateway', array(__CLASS__, 'render_page'), 'dashicons-welcome-learn-more', 58);
        add_submenu_page('olama-student-gateway', 'إعدادات بوابة الطالب', 'الإعدادات', 'manage_options', 'olama-student-gateway', array(__CLASS__, 'render_page'));
    }

    public static function save() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to change these settings.', 'olama-student-gateway'));
        }
        check_admin_referer('olama_student_gateway_services');
        $posted = isset($_POST['services']) && is_array($_POST['services']) ? wp_unslash($_POST['services']) : array();
        update_option(self::OPTION, self::hidden_from_selection($posted));
        wp_safe_redirect(add_query_arg('updated', '1', admin_url('admin.php?page=olama-student-gateway')));
        exit;
    }

    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to view these settings.', 'olama-student-gateway'));
        }
        ?>
        <div class="wrap" dir="rtl">
            <h1>إعدادات بوابة الطالب</h1>
            <?php if (isset($_GET['updated']) && '1' === sanitize_key(wp_unslash($_GET['updated']))) : ?>
                <div class="notice notice-success is-dismissible"><p>تم حفظ إعدادات الخدمات.</p></div>
            <?php endif; ?>
            <p>اختر الخدمات التي تظهر للعائلات. إخفاء الخدمة يمنع فتح صفحتها من رابط محفوظ أيضاً. تظل صلاحيات الحساب ومصادر البيانات سارية.</p>
            <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
                <input type="hidden" name="action" value="olama_student_gateway_save_services">
                <?php wp_nonce_field('olama_student_gateway_services'); ?>
                <?php foreach (self::groups() as $group) : ?>
                    <h2><?php echo esc_html($group['label']); ?></h2>
                    <fieldset style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:10px;max-width:900px">
                        <?php foreach ($group['items'] as $key => $label) : ?>
                            <label style="display:block;padding:12px;background:#fff;border:1px solid #c3c4c7;border-radius:4px">
                                <input type="checkbox" name="services[]" value="<?php echo esc_attr($key); ?>" <?php checked(self::visible($key)); ?>>
                                <?php echo esc_html($label); ?>
                                <?php $note = self::availability_note($key); if ($note) : ?><small style="display:block;color:#646970;margin:4px 22px 0"><?php echo esc_html($note); ?></small><?php endif; ?>
                            </label>
                        <?php endforeach; ?>
                    </fieldset>
                <?php endforeach; ?>
                <?php submit_button('حفظ الخدمات'); ?>
            </form>
        </div>
        <?php
    }
}
