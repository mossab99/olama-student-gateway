<?php
if (!defined('ABSPATH')) {
    exit;
}

$context = $model['context'];
$family = $context['family'];
$students = $context['students'];
$student = $model['student'];
$data = $model['data'];
$active_view = $model['active_view'];
$make_url = static function (array $args = array()) use ($model) {
    return add_query_arg($args, $model['base_url']);
};
$student_url = static function ($student_uid, $view = 'dashboard') use ($make_url, $model) {
    if ('dashboard' === $view && !isset($model['views']['dashboard'])) {
        $view = isset($model['views']['family']) ? 'family' : (array_key_first($model['views']) ?: 'unavailable');
    }
    return $make_url(array('og_student' => $student_uid, 'og_view' => $view));
};
$field = static function ($record, $key, $fallback = '—') {
    if (is_object($record)) {
        $record = (array) $record;
    }
    return is_array($record) && isset($record[$key]) && '' !== (string) $record[$key] ? $record[$key] : $fallback;
};
$student_grade = static function ($item) {
    $academic = isset($item['academic']) && is_array($item['academic']) ? $item['academic'] : array();
    $grade = !empty($academic['class_name']) ? $academic['class_name'] : 'غير محدد';
    $section = !empty($academic['section_name']) ? $academic['section_name'] : '';
    return $section ? $grade . ' · ' . $section : $grade;
};
$initial = static function ($name) {
    return function_exists('mb_substr') ? mb_substr((string) $name, 0, 1) : substr((string) $name, 0, 1);
};
$student_first_name = static function ($item) {
    $name = is_array($item) && !empty($item['student_name']) ? (string) $item['student_name'] : '';
    return $name ? strtok($name, ' ') : 'طالب';
};
$mask_phone = static function ($phone) {
    $phone = preg_replace('/\s+/', '', (string) $phone);
    if (strlen($phone) < 7) {
        return $phone ?: '—';
    }
    return substr($phone, 0, 2) . '•••••' . substr($phone, -3);
};
$first_name = $student && !empty($student['student_name']) ? strtok($student['student_name'], ' ') : '';
$academic_context = isset($context['academic']) ? $context['academic'] : array();
$academic_year_id = absint($field($academic_context, 'academic_year_id', 0));
$semester_id = absint($field($academic_context, 'semester_id', 0));
$is_temp_family = !empty($context['is_temp_family']);
$menu_url = static function ($view_key, array $args = array()) use ($make_url, $student) {
    $args['og_view'] = $view_key;
    if ('family' !== $view_key && $student) {
        $args['og_student'] = $student['student_uid'];
    }
    return $make_url($args);
};
$menu_groups = array(
    array('key' => 'family', 'label' => 'معلومات العائلة', 'icon' => 'dashicons-groups', 'items' => array(
        array('view' => 'family', 'label' => 'بطاقة العائلة'),
        array('view' => 'stores', 'label' => 'الزي والكتب'),
        array('view' => 'transportation', 'label' => 'المواصلات'),
    )),
    array('key' => 'daily', 'label' => 'المتابعة اليومية', 'icon' => 'dashicons-calendar-alt', 'items' => array(
        array('view' => 'weekly_plan', 'label' => 'الخطة الأسبوعية'),
        array('view' => 'schedule', 'label' => 'الجدول الدراسي'),
        array('view' => 'teachers', 'label' => 'الساعات المكتبية'),
    )),
    array('key' => 'video', 'label' => 'مكتبة الفيديو', 'icon' => 'dashicons-video-alt3', 'items' => array(
        array('view' => 'video_library', 'label' => 'مكتبة الفيديو'),
    )),
    array('key' => 'performance', 'label' => 'التقييم والأداء', 'icon' => 'dashicons-chart-bar', 'items' => array(
        array('view' => 'attendance', 'label' => 'الحضور والغياب'),
        array('view' => 'evaluations', 'label' => 'التقييمات'),
    )),
    array('key' => 'exams', 'label' => 'الامتحانات', 'icon' => 'dashicons-clipboard', 'items' => array(
        array('view' => 'exams', 'label' => 'جدول الامتحانات', 'standard_only' => true, 'args' => array('og_exam_section' => 'schedule')),
        array('view' => 'exams', 'label' => 'اختبارات التقويم', 'args' => array('og_exam_section' => 'online')),
        array('view' => 'exams', 'label' => 'الاختبارات القصيرة', 'args' => array('og_exam_section' => 'short')),
        array('view' => 'exams', 'label' => 'الاختبارات المنجزة', 'args' => array('og_exam_section' => 'finished')),
        array('view' => 'exams', 'label' => 'نتائج الامتحانات الإلكترونية', 'standard_only' => true, 'args' => array('og_exam_section' => 'online-results')),
        array('view' => 'exams', 'label' => 'قاعات الامتحان', 'standard_only' => true, 'args' => array('og_exam_section' => 'hall')),
    )),
    array('key' => 'messages', 'label' => 'الرسائل', 'icon' => 'dashicons-email-alt', 'items' => array(
        array('view' => 'messages', 'label' => 'إرسال رسالة', 'args' => array('og_message' => 'compose')),
        array('view' => 'messages', 'label' => 'صندوق البريد', 'args' => array('og_message' => 'inbox')),
    )),
);
?>
<div class="olama-gateway olama-gateway--<?php echo esc_attr($active_view); ?>" dir="rtl" data-olama-gateway>
    <a class="olama-gateway__skip" href="#olama-gateway-content">انتقل إلى المحتوى</a>
    <button class="olama-gateway__menu" type="button" data-gateway-menu aria-expanded="false" aria-controls="olama-gateway-sidebar">
        <span class="dashicons dashicons-menu" aria-hidden="true"></span>
        <span>القائمة</span>
    </button>
    <button class="olama-gateway__backdrop" type="button" data-gateway-backdrop aria-label="إغلاق القائمة" tabindex="-1"></button>

    <aside class="olama-gateway__sidebar" id="olama-gateway-sidebar" data-gateway-sidebar>
        <div class="olama-gateway__brand">
            <span class="olama-gateway__brand-mark">ع</span>
            <span><strong>بوابة الطالب</strong><small>أكاديمية علماء المستقبل</small></span>
        </div>
        <?php if ($students) : ?>
            <div class="olama-gateway__student-menu" aria-label="الطلاب">
                <span class="olama-gateway__nav-label">الطلاب</span>
                <?php foreach ($students as $family_student) :
                    $is_current_student = $student && $student['student_uid'] === $family_student['student_uid'];
                    ?>
                    <a class="<?php echo $is_current_student ? 'is-current' : ''; ?>" href="<?php echo esc_url($student_url($family_student['student_uid'])); ?>" <?php echo $is_current_student ? 'aria-current="page"' : ''; ?>><span class="olama-gateway__student-menu-name"><?php echo esc_html($student_first_name($family_student) . ' · ' . $student_grade($family_student)); ?></span></a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <nav class="olama-gateway__nav" aria-label="أقسام البوابة">
            <span class="olama-gateway__nav-label">الخدمات الرئيسية</span>
            <?php if (isset($model['views']['dashboard'])) : ?>
                <a class="olama-gateway__nav-link olama-gateway__nav-link--primary <?php echo 'dashboard' === $active_view ? 'is-active' : ''; ?>" href="<?php echo esc_url($menu_url('dashboard')); ?>" <?php echo 'dashboard' === $active_view ? 'aria-current="page"' : ''; ?>><span class="dashicons dashicons-dashboard" aria-hidden="true"></span>لوحة المتابعة</a>
            <?php endif; ?>
            <?php if (isset($model['views']['ministry'])) : ?>
                <a class="olama-gateway__nav-link <?php echo 'ministry' === $active_view ? 'is-active' : ''; ?>" href="<?php echo esc_url($menu_url('ministry')); ?>" <?php echo 'ministry' === $active_view ? 'aria-current="page"' : ''; ?>><span class="dashicons dashicons-id-alt" aria-hidden="true"></span>استكمال البيانات الإحصائية</a>
            <?php endif; ?>

            <?php foreach ($menu_groups as $group) :
                $items = array_values(array_filter($group['items'], static function ($item) use ($model, $is_temp_family) {
                    $service = $item['view'];
                    if (isset($item['args']['og_exam_section'])) $service .= '.' . $item['args']['og_exam_section'];
                    if (isset($item['args']['og_message'])) $service .= '.' . $item['args']['og_message'];
                    return Olama_Student_Gateway_Service_Settings::visible($service) && isset($model['views'][$item['view']]) && (empty($item['standard_only']) || !$is_temp_family);
                }));
                if (!$items) {
                    continue;
                }
                $group_is_active = in_array($active_view, array_column($items, 'view'), true);
                if ('video' === $group['key']) :
                    $video_item = $items[0];
                    ?>
                    <a class="olama-gateway__nav-link <?php echo $group_is_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($menu_url($video_item['view'])); ?>" <?php echo $group_is_active ? 'aria-current="page"' : ''; ?>><span class="dashicons <?php echo esc_attr($group['icon']); ?>" aria-hidden="true"></span><?php echo esc_html($group['label']); ?></a>
                    <?php continue;
                endif;
                ?>
                <details class="olama-gateway__nav-group" <?php echo $group_is_active ? 'open' : ''; ?>>
                    <summary><span class="dashicons <?php echo esc_attr($group['icon']); ?>" aria-hidden="true"></span><span><?php echo esc_html($group['label']); ?></span><span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></summary>
                    <div class="olama-gateway__nav-group-links">
                        <?php foreach ($items as $item) :
                            $item_args = isset($item['args']) ? $item['args'] : array();
                            $item_active = $active_view === $item['view'];
                            if (isset($item_args['og_message'])) {
                                $item_active = $item_active && $item_args['og_message'] === $model['message_mode'];
                            } elseif (isset($item_args['og_exam_section'])) {
                                $item_active = $item_active && $item_args['og_exam_section'] === $model['exam_section'];
                            }
                            ?>
                            <a class="<?php echo $item_active ? 'is-active' : ''; ?>" href="<?php echo esc_url($menu_url($item['view'], $item_args)); ?>" <?php echo $item_active ? 'aria-current="page"' : ''; ?>><?php echo esc_html($item['label']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endforeach; ?>

        </nav>
        <a class="olama-gateway__logout" href="<?php echo esc_url($model['logout_url']); ?>">
            <span class="dashicons dashicons-exit" aria-hidden="true"></span> تسجيل الخروج
        </a>
    </aside>

    <main class="olama-gateway__main" id="olama-gateway-content" tabindex="-1">
        <header class="olama-gateway__topbar">
            <?php if ($student) : ?>
                <div class="olama-gateway__student-context">
                    <span class="olama-gateway__avatar"><?php echo esc_html($initial($student['student_name'])); ?></span>
                    <span><strong><?php echo esc_html($student_first_name($student)); ?></strong><small><?php echo esc_html($student_grade($student)); ?></small></span>
                </div>
                <div class="olama-gateway__top-actions">
                    <span class="olama-gateway__year"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><?php echo esc_html($context['study_year'] ?: 'السنة الحالية'); ?></span>
                    <label class="olama-gateway__student-switch">
                        <span>تبديل الطالب</span>
                        <select data-student-switch>
                            <?php foreach ($students as $family_student) : ?>
                                <option value="<?php echo esc_url($student_url($family_student['student_uid'])); ?>" <?php selected($student['student_uid'], $family_student['student_uid']); ?>>
                                    <?php echo esc_html($student_first_name($family_student) . ' · ' . $student_grade($family_student)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>
            <?php else : ?>
                <div><strong>لوحة العائلة</strong><small><?php echo esc_html($context['study_year'] ?: 'السنة الدراسية غير محددة'); ?></small></div>
            <?php endif; ?>
        </header>

        <div class="olama-gateway__content">
            <?php if ('ministry' === $active_view) : ?>
                <section class="olama-gateway__heading">
                    <div><h2>استكمال البيانات الإحصائية</h2><p>تُعرض المعلومات المسجلة تلقائياً. أرسل المعلومات الناقصة أو اطلب تصحيح المعلومة غير الصحيحة.</p></div>
                </section>
                <?php if (!empty($model['ministry_notice'])) : ?><p role="status"><?php echo esc_html($model['ministry_notice']); ?></p><?php endif; ?>
                <?php if (is_wp_error($data)) : ?>
                    <p><?php echo esc_html($data->get_error_message()); ?></p>
                <?php else : ?>
                    <p><strong><?php echo esc_html($data['completion_percentage']); ?>%</strong> · <?php echo esc_html($data['primary_status']); ?></p>
                    <div class="olama-ministry-fields">
                        <?php
                        $domain_labels = array(
                            'SCHOOL' => 'بيانات المدرسة', 'ACADEMIC' => 'البيانات الدراسية',
                            'CORE_IDENTITY' => 'هوية الطالب', 'RESIDENCE' => 'مكان السكن',
                            'DERIVED' => 'بيانات محسوبة', 'GUARDIAN' => 'ولي الأمر',
                            'FAMILY_PROFILE' => 'بيانات الأسرة', 'SENSITIVE_FAMILY_DATA' => 'معلومات إضافية مطلوبة',
                        );
                        $last_domain = '';
                        ?>
                        <?php foreach ($data['fields'] as $key => $item) :
                            if (!$item['applicable'] || $item['domain'] === 'SYSTEM') continue;
                            $school_owned = in_array($item['policy'], array('READ_ONLY', 'ACADEMIC_ONLY', 'SYSTEM_DERIVED'), true);
                            if ($item['domain'] !== $last_domain) {
                                $last_domain = $item['domain'];
                                echo '<h3 class="olama-ministry-section-heading">' . esc_html(isset($domain_labels[$last_domain]) ? $domain_labels[$last_domain] : $last_domain) . '</h3>';
                            }
                            ?>
                            <section class="olama-ministry-field">
                                <h3><?php echo esc_html($item['label']); ?></h3>
                                <?php if ($item['value'] !== '') : ?>
                                    <p><?php echo esc_html($item['value']); ?> <small>· موجود في النظام</small></p>
                                <?php elseif ($school_owned) : ?>
                                    <p>بانتظار استكمال المدرسة</p>
                                <?php elseif ($item['has_pending']) : ?>
                                    <p>تم إرسال معلومة وتنتظر المراجعة</p>
                                <?php else : ?>
                                    <p>مطلوب استكماله</p>
                                <?php endif; ?>
                                <?php if (!$school_owned && !$item['has_pending']) : ?>
                                    <form method="post" action="<?php echo esc_url($menu_url('ministry')); ?>">
                                        <?php wp_nonce_field('olama_ministry_submit', '_olama_ministry_nonce'); ?>
                                        <input type="hidden" name="olama_ministry_submit" value="1">
                                        <input type="hidden" name="ministry_student_uid" value="<?php echo esc_attr($student['student_uid']); ?>">
                                        <input type="hidden" name="ministry_field_key" value="<?php echo esc_attr($key); ?>">
                                        <label><?php echo $item['value'] !== '' ? 'القيمة الصحيحة المقترحة' : 'المعلومة المطلوبة'; ?>
                                            <?php $options = olama_core()->student_statistics()->allowed_values($key); ?>
                                            <?php if ($options) : ?>
                                                <select name="ministry_value" required>
                                                    <option value="">اختر</option>
                                                    <?php foreach ($options as $choice) : ?>
                                                        <option value="<?php echo esc_attr($choice); ?>" <?php selected($item['draft_value'], $choice); ?>><?php echo esc_html($choice); ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php else : ?>
                                                <input name="ministry_value" type="<?php echo $key === 'birth_date' ? 'date' : (in_array($key, array('monthly_income', 'family_size', 'sibling_order'), true) ? 'number' : 'text'); ?>" <?php echo $key === 'monthly_income' ? 'min="0" step="0.001"' : (in_array($key, array('family_size', 'sibling_order'), true) ? 'min="1" max="50" step="1"' : ''); ?> value="<?php echo esc_attr($item['draft_value']); ?>" required maxlength="500" autocomplete="off">
                                            <?php endif; ?>
                                        </label>
                                        <button class="olama-gateway__button" type="submit"><?php echo $item['value'] !== '' ? 'طلب تصحيح' : 'إرسال للمراجعة'; ?></button>
                                        <button class="olama-gateway__button olama-gateway__button--ghost" type="submit" name="ministry_save_draft" value="1">حفظ مسودة</button>
                                    </form>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif ('family' === $active_view) : ?>
                <section class="olama-gateway__heading">
                    <div><h2>مرحباً بكم في بوابة العائلة</h2><p>اختر أحد الأبناء لمتابعة معلوماته، أو راجع بطاقة العائلة والملخص المالي.</p></div>
                    <span class="olama-gateway__status">بيانات OLAMA Core</span>
                </section>

                <section class="olama-gateway__member-grid" aria-label="أفراد العائلة">
                    <?php if (!$students) : ?>
                        <div class="olama-gateway__empty">لا يوجد طلاب مرتبطون بحساب العائلة في OLAMA Core.</div>
                    <?php endif; ?>
                    <?php foreach ($students as $family_student) : ?>
                        <a class="olama-gateway__member" href="<?php echo esc_url($student_url($family_student['student_uid'])); ?>">
                            <span class="olama-gateway__avatar"><?php echo esc_html($initial($family_student['student_name'])); ?></span>
                            <span><strong><?php echo esc_html($student_first_name($family_student)); ?></strong><small><?php echo esc_html($student_grade($family_student)); ?></small><em>عرض بوابة الطالب</em></span>
                            <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                        </a>
                    <?php endforeach; ?>
                </section>

                <section class="olama-gateway__grid olama-gateway__grid--2">
                    <?php if (current_user_can('olama_student_gateway_family_view')) : ?>
                    <article class="olama-gateway__panel olama-gateway__panel--family-profile">
                        <header><h3>بطاقة العائلة</h3><span class="olama-gateway__status olama-gateway__status--success">فعالة</span></header>
                        <dl class="olama-gateway__details">
                            <div><dt>ولي الأمر</dt><dd><?php echo esc_html($field($family, 'sponsor_full_name')); ?></dd></div>
                            <div><dt>اسم الأم</dt><dd><?php echo esc_html($field($family, 'mother_name')); ?></dd></div>
                            <div><dt>الهاتف المعتمد</dt><dd><?php echo esc_html($mask_phone($field($family, 'primary_mobile', ''))); ?></dd></div>
                            <div><dt>المنطقة</dt><dd><?php echo esc_html($field($family, 'trans_region_name')); ?></dd></div>
                            <div class="is-wide"><dt>العنوان</dt><dd><?php echo esc_html($field($family, 'family_address')); ?></dd></div>
                        </dl>
                    </article>
                    <?php endif; ?>

                    <?php if (current_user_can('olama_student_gateway_finance_view')) : ?>
                    <article class="olama-gateway__panel">
                        <header><h3>الملخص المالي</h3><span class="olama-gateway__source">OLAMA Core</span></header>
                        <?php
                        $finance = isset($data['finance']) ? $data['finance'] : null;
                        if (is_wp_error($finance)) :
                            ?>
                            <div class="olama-gateway__empty"><?php echo esc_html($finance->get_error_message()); ?></div>
                        <?php elseif (!$finance || empty($finance['family_summary'])) : ?>
                            <div class="olama-gateway__empty">لا توجد بيانات مالية منشورة للسنة الدراسية الحالية.</div>
                        <?php else : $summary = $finance['family_summary']; ?>
                            <div class="olama-gateway__metrics">
                                <div><span>الرصيد</span><strong><?php echo esc_html(number_format_i18n((float) $field($summary, 'balance', 0), 3)); ?></strong><small><?php echo esc_html($field($summary, 'currency', 'JOD')); ?></small></div>
                                <div><span>مدين السنة</span><strong><?php echo esc_html(number_format_i18n((float) $field($summary, 'year_debit', 0), 3)); ?></strong></div>
                                <div><span>دائن السنة</span><strong><?php echo esc_html(number_format_i18n((float) $field($summary, 'year_credit', 0), 3)); ?></strong></div>
                            </div>
                            <p class="olama-gateway__synced">آخر مزامنة: <?php echo esc_html($field($summary, 'last_synced_at')); ?></p>
                        <?php endif; ?>
                    </article>
                    <?php endif; ?>
                </section>

                <?php
                $family_transportation = isset($data['transportation']) && is_array($data['transportation']) ? $data['transportation'] : array();
                $transportation_student = isset($data['transportation_student']) && is_array($data['transportation_student']) ? $data['transportation_student'] : null;
                $registration = !empty($family_transportation['registration']) ? $family_transportation['registration'] : array();
                $family_student_names = array();
                foreach ($students as $family_student) {
                    $family_student_names[] = $student_first_name($family_student);
                }
                ?>
                <?php if ($family_transportation && $transportation_student) : ?>
                    <section class="olama-gateway__panel olama-gateway__family-transportation">
                        <header><h3>بطاقة المواصلات</h3><span class="olama-gateway__source">OLAMA Core</span></header>
                        <article class="olama-gateway__family-transport-card">
                            <header><span class="olama-gateway__avatar"><?php echo esc_html($initial($transportation_student['student_name'])); ?></span><span><strong>الطلاب: <?php echo esc_html(implode('، ', $family_student_names)); ?></strong><small><?php echo esc_html($student_first_name($transportation_student) . ' · ' . $student_grade($transportation_student)); ?></small></span></header>
                            <?php if (!$registration) : ?>
                                <div class="olama-gateway__transport-walk"><span class="dashicons dashicons-location-alt" aria-hidden="true"></span><strong>مشي</strong></div>
                            <?php else : ?>
                                <dl class="olama-gateway__details"><div><dt>الذهاب</dt><dd><?php echo esc_html($field($registration, 'departure_bus_name', $field($registration, 'departure_bus'))); ?></dd></div><div><dt>العودة</dt><dd><?php echo esc_html($field($registration, 'arrival_bus_name', $field($registration, 'arrival_bus'))); ?></dd></div><div class="is-wide"><dt>المسار</dt><dd><?php echo esc_html($field($registration, 'trans_route_name')); ?></dd></div></dl>
                                <a href="<?php echo esc_url($make_url(array('og_view' => 'transportation', 'og_student' => $transportation_student['student_uid']))); ?>">تفاصيل المواصلات</a>
                            <?php endif; ?>
                        </article>
                    </section>
                <?php endif; ?>

                <?php if (current_user_can('olama_student_gateway_finance_view') && !empty($finance) && !is_wp_error($finance) && !empty($finance['due_allocations'])) : ?>
                    <section class="olama-gateway__panel">
                        <header><h3>جدول المستحقات</h3></header>
                        <div class="olama-gateway__table-wrap"><table><thead><tr><th>التاريخ</th><th>المبلغ</th><th>المدفوع</th><th>الرصيد</th><th>الحالة</th></tr></thead><tbody>
                            <?php foreach ($finance['due_allocations'] as $due) : ?>
                                <tr><td><?php echo esc_html($field($due, 'due_date')); ?></td><td><?php echo esc_html($field($due, 'due_amount', '0')); ?></td><td><?php echo esc_html($field($due, 'paid_amount', '0')); ?></td><td><?php echo esc_html($field($due, 'balance', '0')); ?></td><td><?php echo esc_html($field($due, 'due_status')); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    </section>
                <?php endif; ?>

                <?php if (current_user_can('olama_student_gateway_finance_view') && !empty($finance) && !is_wp_error($finance) && !empty($finance['student_transactions'])) : ?>
                    <section class="olama-gateway__panel">
                        <header><h3>الحركات والدفعات</h3><span class="olama-gateway__source">OLAMA Core</span></header>
                        <div class="olama-gateway__table-wrap"><table><thead><tr><th>التاريخ</th><th>البيان</th><th>الطالب</th><th>مدين</th><th>دائن / دفعة</th><th>الحالة</th></tr></thead><tbody>
                            <?php foreach (array_reverse($finance['student_transactions']) as $transaction) : ?>
                                <tr><td><?php echo esc_html($field($transaction, 'transaction_date')); ?></td><td><strong><?php echo esc_html($field($transaction, 'title')); ?></strong><small><?php echo esc_html($field($transaction, 'receipt_id', '')); ?></small></td><td><?php echo esc_html($field($transaction, 'student_name')); ?></td><td><?php echo esc_html($field($transaction, 'debit_amount', '0')); ?></td><td><?php echo esc_html($field($transaction, 'credit_amount', '0')); ?></td><td><?php echo esc_html($field($transaction, 'transaction_status')); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody></table></div>
                    </section>
                <?php endif; ?>

            <?php elseif ('dashboard' === $active_view) : ?>
                <section class="olama-gateway__hero">
                    <div class="olama-gateway__hero-copy">
                        <span class="olama-gateway__eyebrow">ملخص الطالب · <?php echo esc_html($context['study_year'] ?: 'السنة الحالية'); ?></span>
                        <h2>إليك ملخص يوم <?php echo esc_html($first_name); ?></h2>
                        <p><?php echo esc_html(wp_date('l، j F Y', current_time('timestamp'))); ?> · أحدث المعلومات المنشورة لحساب العائلة.</p>
                        <?php if (!$is_temp_family && isset($model['views']['family'])) : ?><div class="olama-gateway__hero-actions">
                                <a class="olama-gateway__button olama-gateway__button--ghost" href="<?php echo esc_url($make_url(array('og_view' => 'family'))); ?>">
                                    <span class="dashicons dashicons-groups" aria-hidden="true"></span> أفراد العائلة
                                </a>
                        </div><?php endif; ?>
                    </div>
                </section>
                <?php
                $weekly = isset($data['weekly_plan']) ? $data['weekly_plan'] : null;
                $exams = isset($data['exams']) ? $data['exams'] : null;
                $today = current_time('Y-m-d');
                $today_plans = array();
                $today_homework = array();

                if (is_array($weekly) && !empty($weekly['days'])) {
                    foreach ($weekly['days'] as $day) {
                        $day_plans = !empty($day['plans']) ? (array) $day['plans'] : array();
                        if (!empty($day['date']) && $today === $day['date']) {
                            $today_plans = $day_plans;
                        }
                    }
                }

                foreach ($today_plans as $plan) {
                    $has_homework = (bool) array_filter(array(
                        $field($plan, 'homework_student_book', ''),
                        $field($plan, 'homework_exercise_book', ''),
                        $field($plan, 'homework_notebook', ''),
                        $field($plan, 'homework_worksheet', ''),
                    ));
                    if ($has_homework) {
                        $today_homework[] = $plan;
                    }
                }

                $next_exam = null;
                $scheduled_exams = is_array($exams) && !empty($exams['schedule']['exams']) ? (array) $exams['schedule']['exams'] : array();
                $exam_dates = array();
                if ($scheduled_exams) {
                    foreach ($scheduled_exams as $candidate_exam) {
                        $candidate_date = (string) $field($candidate_exam, 'exam_date', '');
                        if ($candidate_date) {
                            $exam_dates[] = $candidate_date;
                        }
                        if ($candidate_date && substr($candidate_date, 0, 10) >= $today) {
                            if (!$next_exam || $candidate_date < (string) $field($next_exam, 'exam_date', '')) {
                                $next_exam = $candidate_exam;
                            }
                        }
                    }
                }
                sort($exam_dates);
                ?>
                <?php if (isset($model['views']['ministry']) && !$is_temp_family && function_exists('olama_core') && $context['study_year'] &&
                    get_option('olama_ministry_family_enabled', false)) : ?>
                    <section class="olama-gateway__panel" aria-labelledby="ministry-dashboard-title">
                        <header><div><h3 id="ministry-dashboard-title">استكمال بيانات الطلبة</h3><p>راجع المعلومات المطلوبة لكل ابن.</p></div></header>
                        <?php foreach ($students as $family_student) :
                            $ministry_status = olama_core()->student_statistics()->evaluate($family_student['student_uid'], $context['study_year']);
                            if (is_wp_error($ministry_status)) continue;
                            $family_count = count($ministry_status['family_missing']);
                            ?>
                            <p>
                                <strong><?php echo esc_html($family_student['student_name']); ?></strong>
                                <?php if ($ministry_status['is_complete']) : ?>
                                    <span>✓ البيانات مكتملة</span>
                                <?php elseif ($family_count) : ?>
                                    <span><?php echo esc_html($family_count); ?> معلومات مطلوبة من الأسرة</span>
                                <?php else : ?>
                                    <span>لا توجد معلومات مطلوبة من الأسرة حالياً</span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($student_url($family_student['student_uid'], 'ministry')); ?>">مراجعة البيانات</a>
                            </p>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
                <section class="olama-gateway__dashboard-grid">
                    <div class="olama-gateway__dashboard-main">
                        <?php if (null !== $weekly) : ?>
                            <article class="olama-gateway__panel olama-gateway__panel--feature">
                                <header>
                                    <div><h3>واجبات اليوم</h3><p>الواجبات المنشورة لهذا اليوم فقط.</p></div>
                                </header>
                                <?php if (is_wp_error($weekly)) : ?>
                                    <div class="olama-gateway__empty"><?php echo esc_html($weekly->get_error_message()); ?></div>
                                <?php elseif (!$today_homework) : ?>
                                    <div class="olama-gateway__empty">لا توجد واجبات منشورة لهذا اليوم.</div>
                                <?php else : ?>
                                    <?php foreach ($today_homework as $plan) : ?>
                                        <div class="olama-gateway__homework">
                                            <strong><span class="dashicons dashicons-edit" aria-hidden="true"></span><?php echo esc_html($field($plan, 'subject')); ?></strong>
                                            <?php foreach (array('homework_student_book' => 'كتاب الطالب', 'homework_exercise_book' => 'دفتر التمارين', 'homework_notebook' => 'الدفتر', 'homework_worksheet' => 'ورقة العمل') as $homework_key => $homework_label) : ?>
                                                <?php if ('' !== (string) $field($plan, $homework_key, '')) : ?><div><small><?php echo esc_html($homework_label); ?></small><span><?php echo esc_html($field($plan, $homework_key, '')); ?></span></div><?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </article>
                        <?php endif; ?>

                        <?php if (null !== $weekly) : ?>
                            <article class="olama-gateway__panel">
                                <header><div><h3>خطة اليوم</h3><p>الدروس المنشورة لليوم الحالي.</p></div></header>
                                <?php if (is_wp_error($weekly)) : ?>
                                    <div class="olama-gateway__empty"><?php echo esc_html($weekly->get_error_message()); ?></div>
                                <?php elseif (!$today_plans) : ?>
                                    <p class="olama-gateway__quiet">لا توجد دروس منشورة لهذا اليوم.</p>
                                <?php else : ?>
                                    <div class="olama-gateway__today-list olama-gateway__today-list--standalone">
                                        <?php foreach ($today_plans as $plan) : ?>
                                            <div class="olama-gateway__today-row">
                                                <span class="olama-gateway__subject-mark" aria-hidden="true"></span>
                                                <span><strong><?php echo esc_html($field($plan, 'subject')); ?></strong><small><?php echo esc_html($field($plan, 'lesson', $field($plan, 'topic'))); ?></small></span>
                                                <small>الحصة <?php echo esc_html($field($plan, 'period', '—')); ?></small>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endif; ?>

                        <?php if (null !== $exams) : ?><section class="olama-gateway__quick-stats olama-gateway__quick-stats--single" aria-label="ملخص الامتحانات">
                            <?php if (is_array($exams)) : ?>
                                <div><span class="dashicons dashicons-clipboard" aria-hidden="true"></span><span><strong><?php echo esc_html(count($scheduled_exams)); ?></strong><small>عدد الامتحانات</small><small><?php echo $exam_dates ? 'من ' . esc_html($exam_dates[0]) . ' إلى ' . esc_html($exam_dates[count($exam_dates) - 1]) : 'لا توجد امتحانات في الجدول'; ?></small></span></div>
                            <?php endif; ?>
                        </section><?php endif; ?>
                    </div>

                    <div class="olama-gateway__dashboard-side olama-gateway__dashboard-side--single">
                        <?php if (null !== $exams) : ?>
                            <article class="olama-gateway__panel olama-gateway__summary-card">
                                <header><h3>الامتحان القادم</h3><span class="olama-gateway__summary-icon is-warning"><span class="dashicons dashicons-clipboard" aria-hidden="true"></span></span></header>
                                <?php if (is_wp_error($exams)) : ?>
                                    <div class="olama-gateway__empty"><?php echo esc_html($exams->get_error_message()); ?></div>
                                <?php elseif (!$next_exam) : ?>
                                    <p class="olama-gateway__quiet">لا يوجد امتحان قادم في الجدول المعتمد.</p>
                                <?php else : ?>
                                    <strong class="olama-gateway__summary-value"><?php echo esc_html($field($next_exam, 'subject_name')); ?></strong>
                                    <p><?php echo esc_html($field($next_exam, 'exam_date')); ?> · <?php echo esc_html($field($next_exam, 'evaluation_type')); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($make_url(array('og_view' => 'exams', 'og_student' => $student['student_uid'], 'og_exam_section' => 'schedule'))); ?>">جدول الامتحانات</a>
                            </article>
                        <?php endif; ?>
                    </div>
                </section>

            <?php elseif ('weekly_plan' === $active_view) : ?>
                <?php if (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (shortcode_exists('olama_weekly_plan')) :
                    $weekly_plan_shortcode = sprintf(
                        '[olama_weekly_plan year="%d" semester="%d" section="%d" week="%s"]',
                        $academic_year_id,
                        $semester_id,
                        absint($data['section_id']),
                        esc_attr($data['week_start'])
                    );
                    ?>
                    <section class="olama-gateway__standard-report olama-gateway__standard-report--weekly" aria-label="الخطة الأسبوعية">
                        <?php echo do_shortcode($weekly_plan_shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted OLAMA School shortcode. ?>
                    </section>
                <?php else : ?>
                    <div class="olama-gateway__empty">تقرير الخطة الأسبوعية غير متاح حالياً.</div>
                <?php endif; ?>

            <?php elseif ('schedule' === $active_view) : ?>
                <?php if (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (shortcode_exists('olama_weekly_schedule')) :
                    $weekly_schedule_shortcode = sprintf(
                        '[olama_weekly_schedule semester="%d" section="%d" schedule_type="normal"]',
                        absint($data['semester_id']),
                        absint($data['section_id'])
                    );
                    ?>
                    <section class="olama-gateway__standard-report olama-gateway__standard-report--schedule" aria-label="الجدول الدراسي الأسبوعي">
                        <?php echo do_shortcode($weekly_schedule_shortcode); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted OLAMA School shortcode. ?>
                    </section>
                <?php else : ?>
                    <div class="olama-gateway__empty">تقرير الجدول الدراسي غير متاح حالياً.</div>
                <?php endif; ?>

            <?php elseif ('teachers' === $active_view) : ?>
                <section class="olama-gateway__heading"><div><span class="olama-gateway__eyebrow">OLAMA School</span><h2>معلمو الشعبة والساعات المكتبية</h2><p>المعلمون المكلّفون بمواد شعبة الطالب ومواعيد استقبال أولياء الأمور.</p></div><span class="olama-gateway__source">بيانات الفصل الحالي</span></section>
                <?php if (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (empty($data['teachers'])) : ?>
                    <div class="olama-gateway__empty">لا يوجد معلمون مرتبطون بهذه الشعبة في السنة الحالية.</div>
                <?php else : ?>
                    <div class="olama-gateway__teacher-toolbar">
                        <div><strong><?php echo esc_html(count($data['teachers'])); ?> معلمين</strong><small><?php echo esc_html($field($data, 'grade_name')); ?> · <?php echo esc_html($field($data, 'section_name')); ?></small></div>
                        <label><span class="screen-reader-text">البحث عن معلم أو مادة</span><span class="dashicons dashicons-search" aria-hidden="true"></span><input type="search" placeholder="ابحث باسم المعلم أو المادة" data-teacher-search></label>
                    </div>
                    <section class="olama-gateway__teacher-grid" data-teacher-list>
                        <?php foreach ($data['teachers'] as $teacher) :
                            $search_terms = $teacher['name'] . ' ' . implode(' ', array_column((array) $teacher['subjects'], 'name'));
                            ?>
                            <article class="olama-gateway__teacher-card" data-teacher-card data-search="<?php echo esc_attr($search_terms); ?>">
                                <header><span class="olama-gateway__teacher-avatar"><?php echo esc_html($initial($teacher['name'])); ?></span><span><strong><?php echo esc_html($teacher['name']); ?></strong><small>معلم الشعبة</small></span></header>
                                <div class="olama-gateway__subject-tags">
                                    <?php foreach ((array) $teacher['subjects'] as $subject) : ?><span style="--subject-color:<?php echo esc_attr($subject['color']); ?>"><?php echo esc_html($subject['name']); ?></span><?php endforeach; ?>
                                </div>
                                <div class="olama-gateway__office-hours">
                                    <h3><span class="dashicons dashicons-clock" aria-hidden="true"></span> الساعات المكتبية</h3>
                                    <?php if (empty($teacher['office_hours'])) : ?>
                                        <p>لم تُنشر ساعات مكتبية لهذا المعلم بعد.</p>
                                    <?php else : ?>
                                        <?php foreach ($teacher['office_hours'] as $slot) : ?><div><span><?php echo esc_html($slot['day']); ?></span><strong><?php echo esc_html($slot['time']); ?></strong></div><?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                    <div class="olama-gateway__empty" data-teacher-empty hidden>لا يوجد معلم أو مادة مطابقة للبحث.</div>
                <?php endif; ?>

            <?php elseif ('video_library' === $active_view) : ?>
                <section class="olama-gateway__heading"><div><span class="olama-gateway__eyebrow">OLAMA Media Library</span><h2>مكتبة الفيديو التعليمية</h2><p>الفيديوهات المعتمدة المرتبطة بمنهاج الطالب في الفصل الحالي.</p></div><span class="olama-gateway__status olama-gateway__status--success">فيديوهات معتمدة فقط</span></section>
                <?php if (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php else : ?>
                    <div class="olama-gateway__context-strip olama-gateway__context-strip--4">
                        <span><small>الصف</small><strong><?php echo esc_html($field($data, 'grade_name')); ?></strong></span>
                        <span><small>الشعبة</small><strong><?php echo esc_html($field($data, 'section_name')); ?></strong></span>
                        <span><small>الفصل</small><strong><?php echo esc_html($field($data, 'semester_name')); ?></strong></span>
                        <span><small>الفيديوهات المتاحة</small><strong><?php echo esc_html(absint($field($data, 'video_count', 0))); ?></strong></span>
                    </div>
                    <?php if (empty($data['subjects'])) : ?>
                        <div class="olama-gateway__empty">لا توجد فيديوهات معتمدة لمواد شعبة الطالب في الفصل الحالي.</div>
                    <?php else : ?>
                        <div class="olama-gateway__video-toolbar">
                            <div><strong><?php echo esc_html(count($data['subjects'])); ?> مواد</strong><small>مرتبة حسب المادة والوحدة والدرس</small></div>
                            <label><span class="screen-reader-text">البحث في مكتبة الفيديو</span><span class="dashicons dashicons-search" aria-hidden="true"></span><input type="search" placeholder="ابحث عن مادة أو وحدة أو درس" data-video-search></label>
                        </div>
                        <section class="olama-gateway__video-library" data-video-list>
                            <?php foreach ($data['subjects'] as $subject) :
                                $subject_search = $subject['name'];
                                foreach ((array) $subject['units'] as $search_unit) {
                                    $subject_search .= ' ' . $search_unit['name'];
                                    foreach ((array) $search_unit['lessons'] as $search_lesson) {
                                        $subject_search .= ' ' . $search_lesson['title'];
                                    }
                                }
                                ?>
                                <article class="olama-gateway__video-subject" data-video-subject data-search="<?php echo esc_attr($subject_search); ?>" style="--subject-color:<?php echo esc_attr($subject['color']); ?>">
                                    <header><span class="olama-gateway__video-subject-icon"><span class="dashicons dashicons-video-alt3" aria-hidden="true"></span></span><span><strong><?php echo esc_html($subject['name']); ?></strong><small><?php echo esc_html(count((array) $subject['units'])); ?> وحدات متاحة</small></span></header>
                                    <div class="olama-gateway__video-units">
                                        <?php foreach ((array) $subject['units'] as $unit_index => $unit) : ?>
                                            <details <?php echo 0 === $unit_index ? 'open' : ''; ?>>
                                                <summary><span><strong><?php echo esc_html($unit['name']); ?></strong><?php if ('' !== (string) $unit['number']) : ?><small>الوحدة <?php echo esc_html($unit['number']); ?></small><?php endif; ?></span><span><?php echo esc_html(count((array) $unit['lessons'])); ?> دروس <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span></span></summary>
                                                <div class="olama-gateway__video-lessons">
                                                    <?php foreach ((array) $unit['lessons'] as $lesson) : ?>
                                                        <div class="olama-gateway__video-lesson">
                                                            <span class="olama-gateway__play-mark"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span></span>
                                                            <span><strong><?php echo esc_html($lesson['title']); ?></strong><?php if ('' !== (string) $lesson['number']) : ?><small>الدرس <?php echo esc_html($lesson['number']); ?></small><?php endif; ?></span>
                                                            <span class="olama-gateway__video-actions">
                                                                <?php foreach ((array) $lesson['videos'] as $video) : ?>
                                                                    <a href="<?php echo esc_url($video['url']); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span><?php echo !empty($video['part']) ? 'الجزء ' . esc_html($video['part']) : 'مشاهدة الفيديو'; ?></a>
                                                                <?php endforeach; ?>
                                                            </span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </details>
                                        <?php endforeach; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </section>
                        <div class="olama-gateway__empty" data-video-empty hidden>لا توجد مادة أو وحدة أو درس مطابق للبحث.</div>
                    <?php endif; ?>
                <?php endif; ?>

            <?php elseif ('exams' === $active_view) : ?>
                <?php
                $exam_engine_view = !empty($model['allow_exam_embed']) && isset($_GET['exam_view']) ? sanitize_key(wp_unslash($_GET['exam_view'])) : '';
                $exam_section = !empty($model['exam_section']) ? $model['exam_section'] : (Olama_Student_Gateway_Service_Settings::exam_sections($is_temp_family)[0] ?? 'online');
                $exam_sections = array(
                    'schedule' => array('جدول الامتحانات', 'جدول الامتحانات المعتمد للطالب.'),
                    'online' => array('اختبارات التقويم', $is_temp_family ? 'تجربة اختبارات التقويم المنشورة للشعبة دون حفظ المحاولات أو الإجابات أو العلامات.' : 'اختبارات التقويم المنشورة والمتاحة للبدء أو الاستكمال.'),
                    'short' => array('الاختبارات القصيرة', $is_temp_family ? 'تجربة الاختبارات القصيرة المنشورة للشعبة دون حفظ المحاولات أو الإجابات أو العلامات.' : 'الاختبارات القصيرة المنشورة والمتاحة للبدء أو الاستكمال.'),
                    'finished' => array('الاختبارات المنجزة', 'اختبارات التقويم والاختبارات القصيرة التي انتهى وقتها أو أُغلقت.'),
                    'online-results' => array('نتائج الامتحانات الإلكترونية', 'نتائج الامتحانات الإلكترونية المنشورة للطالب.'),
                    'hall' => array('قاعات الامتحان', 'توزيع القاعة والمقعد المنشور للطالب.'),
                );
                $exam_heading = $exam_sections[$exam_section];
                $exam_id = isset($_GET['exam_id']) ? absint($_GET['exam_id']) : 0;
                $exam_list_section = in_array($exam_section, array('online', 'short'), true) ? $exam_section : (Olama_Student_Gateway_Service_Settings::visible('exams.online') ? 'online' : (Olama_Student_Gateway_Service_Settings::exam_sections($is_temp_family)[0] ?? 'short'));
                if ($exam_id && !is_wp_error($data) && !empty($data['online_exams'])) {
                    foreach ((array) $data['online_exams'] as $listed_exam) {
                        if ($exam_id === absint($field($listed_exam, 'id', 0))) {
                            $listed_section = 'quiz' === $field($listed_exam, 'exam_type', 'exam') ? 'short' : 'online';
                            if (Olama_Student_Gateway_Service_Settings::visible('exams.' . $listed_section)) $exam_list_section = $listed_section;
                            break;
                        }
                    }
                }
                $exam_list_url = $make_url(array('og_view' => 'exams', 'og_student' => $student['student_uid'], 'og_exam_section' => $exam_list_section));
                $exam_back_label = 'short' === $exam_list_section ? 'الاختبارات القصيرة' : 'اختبارات التقويم';
                ?>
                <section class="olama-gateway__heading"><div><h2><?php echo esc_html($exam_heading[0]); ?></h2><p><?php echo esc_html($exam_heading[1]); ?></p></div><span class="olama-gateway__status"><?php echo $is_temp_family ? 'عرض تجريبي فقط' : 'قسم الامتحانات'; ?></span></section>
                <?php
                if ('demo' === $exam_engine_view && !empty($context['is_temp_family'])) :
                    $available_demo_exams = !is_wp_error($data) && !empty($data['online_exams']) ? (array) $data['online_exams'] : array();
                    ?>
                    <section class="olama-gateway__panel olama-gateway__exam-runner">
                        <a class="olama-gateway__exam-back" href="<?php echo esc_url($exam_list_url); ?>"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span> العودة إلى <?php echo esc_html($exam_back_label); ?></a>
                        <?php echo Olama_Student_Gateway_Demo_Exam::render(isset($_GET['exam_id']) ? absint($_GET['exam_id']) : 0, $available_demo_exams, $student['student_uid'], $exam_list_url); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes its output. ?>
                    </section>
                <?php elseif (in_array($exam_engine_view, array('take', 'results'), true) && empty($context['is_temp_family'])) :
                    ?>
                    <section class="olama-gateway__panel olama-gateway__exam-runner">
                        <a class="olama-gateway__exam-back" href="<?php echo esc_url($exam_list_url); ?>"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span> العودة إلى <?php echo esc_html($exam_back_label); ?></a>
                        <?php if (shortcode_exists('olama_exam')) : ?>
                            <?php echo do_shortcode('[olama_exam]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted producer shortcode. ?>
                        <?php else : ?>
                            <div class="olama-gateway__empty">محرك الامتحانات غير متاح حالياً.</div>
                        <?php endif; ?>
                    </section>
                <?php elseif (in_array($exam_engine_view, array('take', 'results'), true) && !empty($context['is_temp_family'])) : ?>
                    <div class="olama-gateway__empty">هذا الحساب يستخدم وضع العرض التجريبي فقط، ولا يمكنه إنشاء محاولة امتحان.</div>
                <?php elseif (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php else :
                    $schedule = !empty($data['schedule']) && is_array($data['schedule']) ? $data['schedule'] : array();
                    $scheduled_exams = isset($schedule['exams']) ? (array) $schedule['exams'] : array();
                    $hall = isset($data['hall']) ? $data['hall'] : array();
                    $online_exams = isset($data['online_exams']) ? (array) $data['online_exams'] : array();
                    $online_results = isset($data['online_results']) ? (array) $data['online_results'] : array();
                    $online_exam_rows = array('online' => array(), 'short' => array(), 'finished' => array('exam' => array(), 'quiz' => array()));
                    $exam_state_priority = array('available' => 0, 'upcoming' => 1, 'ended' => 2, 'unavailable' => 3);
                    foreach ($online_exams as $online_exam) {
                        $online_exam_action = Olama_Student_Gateway_Exam_Launcher::action($online_exam, $student['student_uid'], $exam_list_url);
                        $exam_type = 'quiz' === $field($online_exam, 'exam_type', 'exam') ? 'quiz' : 'exam';
                        $row = array(
                            'exam' => $online_exam,
                            'action' => $online_exam_action,
                            'priority' => isset($exam_state_priority[$online_exam_action['state']]) ? $exam_state_priority[$online_exam_action['state']] : 3,
                        );
                        if ('ended' === $online_exam_action['state']) {
                            $online_exam_rows['finished'][$exam_type][] = $row;
                        } elseif (in_array($online_exam_action['state'], array('available', 'upcoming'), true)) {
                            $online_exam_rows['quiz' === $exam_type ? 'short' : 'online'][] = $row;
                        }
                    }
                    $sort_exam_rows = static function ($left, $right) use ($field) {
                        if ($left['priority'] !== $right['priority']) {
                            return $left['priority'] - $right['priority'];
                        }
                        $left_state = $left['action']['state'];
                        $left_time = 'ended' === $left_state ? $field($left['exam'], 'end_time', '') : $field($left['exam'], 'start_time', '');
                        $right_time = 'ended' === $right['action']['state'] ? $field($right['exam'], 'end_time', '') : $field($right['exam'], 'start_time', '');
                        return 'ended' === $left_state ? strcmp($right_time, $left_time) : strcmp($left_time, $right_time);
                    };
                    usort($online_exam_rows['online'], $sort_exam_rows);
                    usort($online_exam_rows['short'], $sort_exam_rows);
                    usort($online_exam_rows['finished']['exam'], $sort_exam_rows);
                    usort($online_exam_rows['finished']['quiz'], $sort_exam_rows);
                    ?>
                    <?php if (empty($context['is_temp_family']) && 'hall' === $exam_section) : ?>
                    <section class="olama-gateway__grid" id="exam-hall">
                        <article class="olama-gateway__panel">
                            <header><h3>القاعة والمقعد</h3><span class="olama-gateway__source">OLAMA Exam Management</span></header>
                            <?php if (!$hall) : ?><div class="olama-gateway__empty">لم يتم نشر توزيع قاعة لهذا الطالب.</div>
                            <?php else : ?><dl class="olama-gateway__details"><div><dt>القاعة</dt><dd><?php echo esc_html($field($hall, 'hall_name')); ?></dd></div><div><dt>المقعد</dt><dd><?php echo esc_html($field($hall, 'seat_number')); ?></dd></div><div><dt>الصف</dt><dd><?php echo esc_html($field($hall, 'grade_name')); ?></dd></div><div><dt>الشعبة</dt><dd><?php echo esc_html($field($hall, 'section_name')); ?></dd></div></dl><?php endif; ?>
                        </article>
                    </section>
                    <?php endif; ?>

                    <?php if (empty($context['is_temp_family']) && 'schedule' === $exam_section && shortcode_exists('olama_exam_report')) :
                        $exam_report_shortcode = sprintf(
                            '[olama_exam_report year="%d" semester="%d" grade="%d" exam="active"]',
                            !empty($schedule['year_id']) ? absint($schedule['year_id']) : $academic_year_id,
                            !empty($schedule['semester_id']) ? absint($schedule['semester_id']) : $semester_id,
                            !empty($schedule['grade_id']) ? absint($schedule['grade_id']) : 0
                        );
                        // The canonical report accepts student_uid from the request. Supply the
                        // already ownership-validated gateway student only while it renders.
                        $had_exam_student_uid = array_key_exists('student_uid', $_GET);
                        $previous_exam_student_uid = $had_exam_student_uid ? $_GET['student_uid'] : null;
                        $_GET['student_uid'] = $student['student_uid'];
                        $exam_report_html = do_shortcode($exam_report_shortcode);
                        if ($had_exam_student_uid) {
                            $_GET['student_uid'] = $previous_exam_student_uid;
                        } else {
                            unset($_GET['student_uid']);
                        }
                        ?>
                        <section class="olama-gateway__standard-report olama-gateway__standard-report--exams" id="exam-schedule" aria-label="جدول الامتحانات المعتمد">
                            <?php echo $exam_report_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted OLAMA School shortcode. ?>
                        </section>
                    <?php elseif (empty($context['is_temp_family']) && 'schedule' === $exam_section) : ?>
                        <section class="olama-gateway__panel" id="exam-schedule">
                            <header><h3>جدول الامتحانات المعتمد</h3><span class="olama-gateway__source">OLAMA Exam Management</span></header>
                            <?php if (!$scheduled_exams) : ?><div class="olama-gateway__empty">لا توجد امتحانات معتمدة منشورة للطالب.</div>
                            <?php else : ?><div class="olama-gateway__table-wrap"><table><thead><tr><th>التاريخ</th><th>المادة</th><th>نوع التقييم</th><th>المادة المطلوبة</th><th>الغرفة</th></tr></thead><tbody>
                                <?php foreach ($scheduled_exams as $exam) : ?><tr><td><?php echo esc_html($field($exam, 'exam_date')); ?></td><td><strong><?php echo esc_html($field($exam, 'subject_name')); ?></strong></td><td><?php echo esc_html($field($exam, 'evaluation_type')); ?></td><td><?php echo esc_html($field($exam, 'student_book_material', $field($exam, 'description'))); ?></td><td><?php echo esc_html($field($exam, 'room_number', $field($exam, 'master_room'))); ?></td></tr><?php endforeach; ?>
                            </tbody></table></div><?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <?php if (in_array($exam_section, array('online', 'short', 'finished'), true)) :
                        $visible_exam_rows = 'finished' === $exam_section
                            ? array_merge($online_exam_rows['finished']['exam'], $online_exam_rows['finished']['quiz'])
                            : $online_exam_rows[$exam_section];
                        ?><section class="olama-gateway__panel olama-gateway__panel--online-exams" id="online-exams">
                        <header><h3><?php echo esc_html($exam_heading[0]); ?></h3><span class="olama-gateway__source"><?php echo $is_temp_family ? 'عرض تجريبي · بلا حفظ' : 'OLAMA Exam Engine'; ?></span></header>
                        <?php if (!$visible_exam_rows) : ?><div class="olama-gateway__empty"><?php echo 'finished' === $exam_section ? 'لا توجد اختبارات منجزة حالياً.' : 'لا توجد اختبارات منشورة حالياً.'; ?></div>
                        <?php else : ?><div class="olama-gateway__table-wrap olama-gateway__table-wrap--exams"><table class="olama-gateway__exam-table"><thead><tr><th>الإجراء</th><th>الامتحان</th><th>المادة</th><th>البداية</th><th>النهاية</th><th>المدة</th><th>الحالة</th></tr></thead><tbody>
                            <?php foreach ('finished' === $exam_section ? array('exam' => 'اختبارات التقويم', 'quiz' => 'الاختبارات القصيرة') : array($exam_section => '') as $row_type => $group_label) :
                                $group_rows = 'finished' === $exam_section ? $online_exam_rows['finished'][$row_type] : $visible_exam_rows;
                                if (!$group_rows) {
                                    continue;
                                }
                                if ($group_label) : ?><tr class="olama-gateway__exam-group"><th colspan="7"><?php echo esc_html($group_label); ?></th></tr><?php endif;
                                foreach ($group_rows as $online_exam_row) :
                                $exam = $online_exam_row['exam'];
                                $exam_action = $online_exam_row['action'];
                                $exam_subject = trim((string) $field($exam, 'subject_name', ''));
                                $exam_title = trim((string) $field($exam, 'title', ''));
                                $exam_detail = $exam_title;
                                if ($exam_subject) {
                                    $exam_detail = preg_replace('/\s*[-–—]\s*' . preg_quote($exam_subject, '/') . '\s*$/u', '', $exam_title);
                                    $exam_detail = trim((string) $exam_detail);
                                    if ($exam_detail === $exam_title && $exam_title === $exam_subject) {
                                        $exam_detail = '';
                                    }
                                }
                                ?><tr class="olama-gateway__exam-card" data-exam-state="<?php echo esc_attr($exam_action['state']); ?>"><td class="olama-gateway__exam-action" data-label="الإجراء"><?php if ('available' === $exam_action['state']) : ?><a class="olama-gateway__button olama-gateway__button--compact" href="<?php echo esc_url($exam_action['url']); ?>"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span><?php echo esc_html($exam_action['label']); ?></a><?php else : ?><span class="olama-gateway__exam-state olama-gateway__exam-state--<?php echo esc_attr($exam_action['state']); ?>"><?php echo esc_html($exam_action['label']); ?></span><?php endif; ?></td><td class="olama-gateway__exam-title" data-label="الامتحان"><strong><?php echo esc_html($exam_subject ?: $exam_title); ?></strong><?php if ($exam_subject && $exam_detail) : ?><small>(<?php echo esc_html($exam_detail); ?>)</small><?php endif; ?></td><td class="olama-gateway__exam-subject" data-label="المادة"><?php echo esc_html($field($exam, 'subject_name')); ?></td><td data-label="البداية"><?php echo esc_html($field($exam, 'start_time')); ?></td><td data-label="النهاية"><?php echo esc_html($field($exam, 'end_time')); ?></td><td data-label="المدة"><?php echo esc_html($field($exam, 'duration_minutes')); ?> دقيقة</td><td class="olama-gateway__exam-status olama-gateway__exam-status--<?php echo esc_attr($exam_action['state']); ?>" data-label="الحالة"><?php echo esc_html($exam_action['status_label']); ?></td></tr><?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody></table></div><?php endif; ?>
                    </section><?php endif; ?>

                    <?php if (empty($context['is_temp_family']) && 'online-results' === $exam_section) : ?>
                    <section class="olama-gateway__panel" id="online-results">
                        <header><h3>نتائج الامتحانات الإلكترونية</h3><span class="olama-gateway__source">OLAMA Exam Engine</span></header>
                        <?php if (!$online_results) : ?><div class="olama-gateway__empty">ينتظر هذا القسم خدمة نتائج الطالب من Exam Engine؛ لن تتم قراءة جدول المحاولات مباشرة.</div><?php else : do_action('olama_student_gateway_render_exam_results', $online_results, $context); endif; ?>
                    </section>
                    <?php endif; ?>
                <?php endif; ?>

            <?php elseif ('transportation' === $active_view) : ?>
                <section class="olama-gateway__heading"><div><h2>مواصلات الطالب</h2><p>التسجيل الرسمي من OLAMA Core والتفاصيل التشغيلية التي ينشرها نظام المواصلات.</p></div></section>
                <?php if (is_wp_error($data)) : ?><div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (empty($data['registration'])) : ?><div class="olama-gateway__empty">لا يوجد تسجيل مواصلات للطالب في السنة الحالية.</div>
                <?php else : $registration = $data['registration']; ?>
                    <section class="olama-gateway__grid olama-gateway__grid--2">
                        <article class="olama-gateway__panel"><header><h3>رحلة الذهاب</h3><span class="olama-gateway__source">OLAMA Core</span></header><dl class="olama-gateway__details"><div><dt>الحافلة</dt><dd><?php echo esc_html($field($registration, 'departure_bus_name', $field($registration, 'departure_bus'))); ?></dd></div><div><dt>التسلسل</dt><dd><?php echo esc_html($field($registration, 'departure_bus_seq')); ?></dd></div><div class="is-wide"><dt>المسار</dt><dd><?php echo esc_html($field($registration, 'trans_route_name')); ?></dd></div></dl></article>
                        <article class="olama-gateway__panel"><header><h3>رحلة العودة</h3><span class="olama-gateway__source">OLAMA Core</span></header><dl class="olama-gateway__details"><div><dt>الحافلة</dt><dd><?php echo esc_html($field($registration, 'arrival_bus_name', $field($registration, 'arrival_bus'))); ?></dd></div><div><dt>التسلسل</dt><dd><?php echo esc_html($field($registration, 'arrival_bus_seq')); ?></dd></div><div class="is-wide"><dt>الحالة</dt><dd><?php echo esc_html($field($registration, 'is_active_name')); ?></dd></div></dl></article>
                    </section>
                    <?php if (empty($data['operations'])) : ?><div class="olama-gateway__hint">لم ينشر نظام المواصلات بعد تفاصيل نقاط الالتقاء والمواعيد التشغيلية.</div><?php endif; ?>
                <?php endif; ?>

            <?php elseif ('stores' === $active_view) : ?>
                <section class="olama-gateway__heading"><div><h2>الزي والكتب</h2><p>عناصر الزي والكتب والمستلزمات التي خصصها أو سلّمها OLAMA Stores لهذا الطالب.</p></div><span class="olama-gateway__source">OLAMA Stores</span></section>
                <?php if (is_wp_error($data)) : ?><div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (!$data) : ?><div class="olama-gateway__empty">لا توجد عناصر مسجلة لهذا الطالب في السنة الحالية.</div>
                <?php else : ?>
                    <section class="olama-gateway__panel"><div class="olama-gateway__table-wrap"><table><thead><tr><th>العنصر</th><th>المستودع</th><th>الكمية</th><th>التاريخ</th><th>الحالة</th></tr></thead><tbody>
                        <?php foreach ($data as $assignment) : ?><tr><td><strong><?php echo esc_html($field($assignment, 'item_name')); ?></strong><small><?php echo esc_html($field($assignment, 'sku', '')); ?></small></td><td><?php echo esc_html($field($assignment, 'warehouse_name')); ?></td><td><?php echo esc_html($field($assignment, 'quantity', '1')); ?></td><td><?php echo esc_html($field($assignment, 'assigned_at', $field($assignment, 'created_at'))); ?></td><td><?php echo esc_html($field($assignment, 'status')); ?></td></tr><?php endforeach; ?>
                    </tbody></table></div></section>
                <?php endif; ?>

            <?php elseif ('unavailable' === $active_view) : ?>
                <div class="olama-gateway__empty">لا توجد خدمات مفعّلة لهذا الحساب حالياً.</div>
            <?php else : ?>
                <section class="olama-gateway__heading"><div><h2><?php echo esc_html(isset($model['views'][$active_view]) ? $model['views'][$active_view]['label'] : 'الخدمة'); ?></h2><p>يعرض هذا القسم المعلومات التي ينشرها النظام المسؤول.</p></div></section>
                <?php if (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (!$data) : ?>
                    <div class="olama-gateway__empty">لم ينشر النظام المسؤول معلومات لهذا الطالب بعد.</div>
                <?php else : ?>
                    <?php do_action('olama_student_gateway_render_' . $active_view, $data, $context, $model); ?>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
</div>
