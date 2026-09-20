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
$student_url = static function ($student_uid, $view = 'dashboard') use ($make_url) {
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
        <div class="olama-gateway__family-chip">
            <span class="dashicons dashicons-groups" aria-hidden="true"></span>
            <span><strong><?php echo esc_html($field($family, 'sponsor_full_name', 'العائلة')); ?></strong><small>رقم العائلة <?php echo esc_html($context['family_id']); ?></small></span>
        </div>
        <nav class="olama-gateway__nav" aria-label="أقسام البوابة">
            <span class="olama-gateway__nav-label">الخدمات</span>
            <?php foreach ($model['views'] as $view_key => $view) :
                $args = array('og_view' => $view_key);
                if ('family' !== $view_key && $student) {
                    $args['og_student'] = $student['student_uid'];
                }
                ?>
                <a class="<?php echo $active_view === $view_key ? 'is-active' : ''; ?>" href="<?php echo esc_url($make_url($args)); ?>" <?php echo $active_view === $view_key ? 'aria-current="page"' : ''; ?>>
                    <span class="dashicons <?php echo esc_attr($view['icon']); ?>" aria-hidden="true"></span>
                    <?php echo esc_html($view['label']); ?>
                </a>
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
                    <span><strong><?php echo esc_html($student['student_name']); ?></strong><small><?php echo esc_html($student_grade($student)); ?></small></span>
                </div>
                <div class="olama-gateway__top-actions">
                    <span class="olama-gateway__year"><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><?php echo esc_html($context['study_year'] ?: 'السنة الحالية'); ?></span>
                    <label class="olama-gateway__student-switch">
                        <span>تبديل الطالب</span>
                        <select data-student-switch>
                            <?php foreach ($students as $family_student) : ?>
                                <option value="<?php echo esc_url($student_url($family_student['student_uid'])); ?>" <?php selected($student['student_uid'], $family_student['student_uid']); ?>>
                                    <?php echo esc_html($family_student['student_name']); ?>
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
            <?php if ('family' === $active_view) : ?>
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
                            <span><strong><?php echo esc_html($family_student['student_name']); ?></strong><small><?php echo esc_html($student_grade($family_student)); ?></small><em>عرض بوابة الطالب</em></span>
                            <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                        </a>
                    <?php endforeach; ?>
                </section>

                <section class="olama-gateway__grid olama-gateway__grid--2">
                    <?php if (current_user_can('olama_student_gateway_family_view')) : ?>
                    <article class="olama-gateway__panel">
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
                        <div class="olama-gateway__hero-actions">
                            <?php if (isset($model['views']['weekly_plan'])) : ?>
                                <a class="olama-gateway__button olama-gateway__button--gold" href="<?php echo esc_url($student_url($student['student_uid'], 'weekly_plan')); ?>">
                                    <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span> عرض الخطة الأسبوعية
                                </a>
                            <?php endif; ?>
                            <a class="olama-gateway__button olama-gateway__button--ghost" href="<?php echo esc_url($make_url(array('og_view' => 'family'))); ?>">
                                <span class="dashicons dashicons-groups" aria-hidden="true"></span> أفراد العائلة
                            </a>
                        </div>
                    </div>
                </section>
                <?php
                $weekly = isset($data['weekly_plan']) ? $data['weekly_plan'] : null;
                $exams = isset($data['exams']) ? $data['exams'] : null;
                $transport = isset($data['transportation']) ? $data['transportation'] : null;
                $stores = isset($data['stores']) ? $data['stores'] : null;
                $today = current_time('Y-m-d');
                $today_plans = array();
                $week_plan_count = 0;

                if (is_array($weekly) && !empty($weekly['days'])) {
                    foreach ($weekly['days'] as $day) {
                        $day_plans = !empty($day['plans']) ? (array) $day['plans'] : array();
                        $week_plan_count += count($day_plans);
                        if (!empty($day['date']) && $today === $day['date']) {
                            $today_plans = $day_plans;
                        }
                    }
                }

                $next_exam = null;
                if (is_array($exams) && !empty($exams['schedule']['exams'])) {
                    foreach ((array) $exams['schedule']['exams'] as $candidate_exam) {
                        $candidate_date = (string) $field($candidate_exam, 'exam_date', '');
                        if ($candidate_date && substr($candidate_date, 0, 10) >= $today) {
                            if (!$next_exam || $candidate_date < (string) $field($next_exam, 'exam_date', '')) {
                                $next_exam = $candidate_exam;
                            }
                        }
                    }
                }
                ?>
                <section class="olama-gateway__dashboard-grid">
                    <div class="olama-gateway__dashboard-main">
                        <?php if (null !== $weekly) : ?>
                            <article class="olama-gateway__panel olama-gateway__panel--feature">
                                <header>
                                    <div><h3>أسبوع الطالب</h3><p>الدروس والواجبات المعتمدة والمنشورة.</p></div>
                                    <a href="<?php echo esc_url($student_url($student['student_uid'], 'weekly_plan')); ?>">عرض الخطة كاملة</a>
                                </header>
                                <?php if (is_wp_error($weekly)) : ?>
                                    <div class="olama-gateway__empty"><?php echo esc_html($weekly->get_error_message()); ?></div>
                                <?php elseif (empty($weekly['days'])) : ?>
                                    <div class="olama-gateway__empty">لا توجد خطة أسبوعية متاحة.</div>
                                <?php else : ?>
                                    <div class="olama-gateway__week-summary">
                                        <?php foreach ($weekly['days'] as $day) :
                                            $is_today = !empty($day['date']) && $today === $day['date'];
                                            ?>
                                            <div class="<?php echo $is_today ? 'is-today' : ''; ?>">
                                                <strong><?php echo esc_html($day['label']); ?></strong>
                                                <span><?php echo esc_html(count((array) $day['plans'])); ?> مواد</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="olama-gateway__today-list">
                                        <div class="olama-gateway__section-title"><h4>خطة اليوم</h4><span><?php echo esc_html(count($today_plans)); ?> مواد</span></div>
                                        <?php if (!$today_plans) : ?>
                                            <p class="olama-gateway__quiet">لا توجد دروس منشورة لهذا اليوم.</p>
                                        <?php else : ?>
                                            <?php foreach (array_slice($today_plans, 0, 4) as $plan) : ?>
                                                <div class="olama-gateway__today-row">
                                                    <span class="olama-gateway__subject-mark" aria-hidden="true"></span>
                                                    <span><strong><?php echo esc_html($field($plan, 'subject')); ?></strong><small><?php echo esc_html($field($plan, 'lesson', $field($plan, 'topic'))); ?></small></span>
                                                    <small>الحصة <?php echo esc_html($field($plan, 'period', '—')); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endif; ?>

                        <section class="olama-gateway__quick-stats" aria-label="ملخص الخدمات">
                            <?php if (is_array($weekly)) : ?>
                                <div><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><span><strong><?php echo esc_html($week_plan_count); ?></strong><small>دروس منشورة هذا الأسبوع</small></span></div>
                            <?php endif; ?>
                            <?php if (is_array($exams)) : ?>
                                <div><span class="dashicons dashicons-clipboard" aria-hidden="true"></span><span><strong><?php echo esc_html(count((array) ($exams['schedule']['exams'] ?? array()))); ?></strong><small>امتحانات في الجدول المعتمد</small></span></div>
                            <?php endif; ?>
                            <?php if (is_array($stores)) : ?>
                                <div><span class="dashicons dashicons-archive" aria-hidden="true"></span><span><strong><?php echo esc_html(count($stores)); ?></strong><small>عناصر مسجلة للطالب</small></span></div>
                            <?php endif; ?>
                        </section>
                    </div>

                    <div class="olama-gateway__dashboard-side">
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
                                <a href="<?php echo esc_url($student_url($student['student_uid'], 'exams')); ?>">تفاصيل الامتحانات</a>
                            </article>
                        <?php endif; ?>

                        <?php if (null !== $transport) : ?>
                            <article class="olama-gateway__panel olama-gateway__summary-card">
                                <header><h3>المواصلات</h3><span class="olama-gateway__summary-icon"><span class="dashicons dashicons-location-alt" aria-hidden="true"></span></span></header>
                                <?php if (is_wp_error($transport) || empty($transport['registration'])) : ?>
                                    <p class="olama-gateway__quiet">لا توجد معلومات مواصلات منشورة للطالب.</p>
                                <?php else : $registration = $transport['registration']; ?>
                                    <div class="olama-gateway__route-summary">
                                        <span><small>الذهاب</small><strong><?php echo esc_html($field($registration, 'departure_bus_name', $field($registration, 'departure_bus'))); ?></strong></span>
                                        <span class="dashicons dashicons-arrow-left-alt" aria-hidden="true"></span>
                                        <span><small>العودة</small><strong><?php echo esc_html($field($registration, 'arrival_bus_name', $field($registration, 'arrival_bus'))); ?></strong></span>
                                    </div>
                                    <p><?php echo esc_html($field($registration, 'trans_route_name')); ?></p>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($student_url($student['student_uid'], 'transportation')); ?>">تفاصيل المواصلات</a>
                            </article>
                        <?php endif; ?>

                        <?php if (null !== $stores) : ?>
                            <article class="olama-gateway__panel olama-gateway__summary-card">
                                <header><h3>مستلزمات المدرسة</h3><span class="olama-gateway__summary-icon is-success"><span class="dashicons dashicons-archive" aria-hidden="true"></span></span></header>
                                <?php if (is_wp_error($stores)) : ?>
                                    <div class="olama-gateway__empty"><?php echo esc_html($stores->get_error_message()); ?></div>
                                <?php elseif (!$stores) : ?>
                                    <p class="olama-gateway__quiet">لا توجد عناصر مسجلة للطالب.</p>
                                <?php else : ?>
                                    <strong class="olama-gateway__summary-value"><?php echo esc_html(count($stores)); ?> عناصر</strong>
                                    <p>سجل التخصيص والتسليم المتاح من OLAMA Stores.</p>
                                <?php endif; ?>
                                <a href="<?php echo esc_url($student_url($student['student_uid'], 'stores')); ?>">عرض المستلزمات</a>
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
                <section class="olama-gateway__heading"><div><h2>الامتحانات والنتائج</h2><p>جدول الامتحانات المعتمد، القاعة، الامتحانات الإلكترونية، والنتائج حسب مصدرها.</p></div><span class="olama-gateway__status">المصدر موضح لكل مجموعة</span></section>
                <?php
                $exam_engine_view = isset($_GET['exam_view']) ? sanitize_key(wp_unslash($_GET['exam_view'])) : '';
                if (in_array($exam_engine_view, array('take', 'results'), true)) :
                    ?>
                    <section class="olama-gateway__panel olama-gateway__exam-runner">
                        <a class="olama-gateway__exam-back" href="<?php echo esc_url($student_url($student['student_uid'], 'exams')); ?>"><span class="dashicons dashicons-arrow-right-alt2" aria-hidden="true"></span> العودة إلى قائمة الامتحانات</a>
                        <?php if (shortcode_exists('olama_exam')) : ?>
                            <?php echo do_shortcode('[olama_exam]'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted producer shortcode. ?>
                        <?php else : ?>
                            <div class="olama-gateway__empty">محرك الامتحانات غير متاح حالياً.</div>
                        <?php endif; ?>
                    </section>
                <?php elseif (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php else :
                    $schedule = !empty($data['schedule']) && is_array($data['schedule']) ? $data['schedule'] : array();
                    $scheduled_exams = isset($schedule['exams']) ? (array) $schedule['exams'] : array();
                    $hall = isset($data['hall']) ? $data['hall'] : array();
                    $online_exams = isset($data['online_exams']) ? (array) $data['online_exams'] : array();
                    $online_results = isset($data['online_results']) ? (array) $data['online_results'] : array();
                    $official_marks = isset($data['official_marks']) ? (array) $data['official_marks'] : array();
                    ?>
                    <section class="olama-gateway__grid olama-gateway__grid--2">
                        <article class="olama-gateway__panel">
                            <header><h3>القاعة والمقعد</h3><span class="olama-gateway__source">OLAMA Exam Management</span></header>
                            <?php if (!$hall) : ?><div class="olama-gateway__empty">لم يتم نشر توزيع قاعة لهذا الطالب.</div>
                            <?php else : ?><dl class="olama-gateway__details"><div><dt>القاعة</dt><dd><?php echo esc_html($field($hall, 'hall_name')); ?></dd></div><div><dt>المقعد</dt><dd><?php echo esc_html($field($hall, 'seat_number')); ?></dd></div><div><dt>الصف</dt><dd><?php echo esc_html($field($hall, 'grade_name')); ?></dd></div><div><dt>الشعبة</dt><dd><?php echo esc_html($field($hall, 'section_name')); ?></dd></div></dl><?php endif; ?>
                        </article>
                        <article class="olama-gateway__panel">
                            <header><h3>العلامات الرسمية</h3><span class="olama-gateway__source">Oracle · مرحلة لاحقة</span></header>
                            <?php if (!$official_marks) : ?><div class="olama-gateway__empty">لم يتم تفعيل استيراد العلامات الرسمية من Oracle بعد.</div><?php else : do_action('olama_student_gateway_render_official_marks', $official_marks, $context); endif; ?>
                        </article>
                    </section>

                    <?php if (shortcode_exists('olama_exam_report')) :
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
                        <section class="olama-gateway__standard-report olama-gateway__standard-report--exams" aria-label="جدول الامتحانات المعتمد">
                            <?php echo $exam_report_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted OLAMA School shortcode. ?>
                        </section>
                    <?php else : ?>
                        <section class="olama-gateway__panel">
                            <header><h3>جدول الامتحانات المعتمد</h3><span class="olama-gateway__source">OLAMA Exam Management</span></header>
                            <?php if (!$scheduled_exams) : ?><div class="olama-gateway__empty">لا توجد امتحانات معتمدة منشورة للطالب.</div>
                            <?php else : ?><div class="olama-gateway__table-wrap"><table><thead><tr><th>التاريخ</th><th>المادة</th><th>نوع التقييم</th><th>المادة المطلوبة</th><th>الغرفة</th></tr></thead><tbody>
                                <?php foreach ($scheduled_exams as $exam) : ?><tr><td><?php echo esc_html($field($exam, 'exam_date')); ?></td><td><strong><?php echo esc_html($field($exam, 'subject_name')); ?></strong></td><td><?php echo esc_html($field($exam, 'evaluation_type')); ?></td><td><?php echo esc_html($field($exam, 'student_book_material', $field($exam, 'description'))); ?></td><td><?php echo esc_html($field($exam, 'room_number', $field($exam, 'master_room'))); ?></td></tr><?php endforeach; ?>
                            </tbody></table></div><?php endif; ?>
                        </section>
                    <?php endif; ?>

                    <section class="olama-gateway__panel">
                        <header><h3>الامتحانات الإلكترونية</h3><span class="olama-gateway__source">OLAMA Exam Engine</span></header>
                        <?php if (!$online_exams) : ?><div class="olama-gateway__empty">لا توجد امتحانات إلكترونية منشورة حالياً.</div>
                        <?php else : ?><div class="olama-gateway__table-wrap"><table><thead><tr><th>الإجراء</th><th>الامتحان</th><th>المادة</th><th>البداية</th><th>النهاية</th><th>المدة</th><th>الحالة</th></tr></thead><tbody>
                            <?php foreach ($online_exams as $exam) :
                                $exam_action = Olama_Student_Gateway_Exam_Launcher::action($exam, $student['student_uid'], $student_url($student['student_uid'], 'exams'));
                                ?><tr><td class="olama-gateway__exam-action"><?php if ('available' === $exam_action['state']) : ?><a class="olama-gateway__button olama-gateway__button--compact" href="<?php echo esc_url($exam_action['url']); ?>"><span class="dashicons dashicons-controls-play" aria-hidden="true"></span><?php echo esc_html($exam_action['label']); ?></a><?php else : ?><span class="olama-gateway__exam-state olama-gateway__exam-state--<?php echo esc_attr($exam_action['state']); ?>"><?php echo esc_html($exam_action['label']); ?></span><?php endif; ?></td><td><strong><?php echo esc_html($field($exam, 'title')); ?></strong></td><td><?php echo esc_html($field($exam, 'subject_name')); ?></td><td><?php echo esc_html($field($exam, 'start_time')); ?></td><td><?php echo esc_html($field($exam, 'end_time')); ?></td><td><?php echo esc_html($field($exam, 'duration_minutes')); ?> دقيقة</td><td><?php echo esc_html($field($exam, 'status')); ?></td></tr><?php endforeach; ?>
                        </tbody></table></div><?php endif; ?>
                    </section>

                    <section class="olama-gateway__panel">
                        <header><h3>نتائج الامتحانات الإلكترونية</h3><span class="olama-gateway__source">OLAMA Exam Engine</span></header>
                        <?php if (!$online_results) : ?><div class="olama-gateway__empty">ينتظر هذا القسم خدمة نتائج الطالب من Exam Engine؛ لن تتم قراءة جدول المحاولات مباشرة.</div><?php else : do_action('olama_student_gateway_render_exam_results', $online_results, $context); endif; ?>
                    </section>
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
                <section class="olama-gateway__heading"><div><h2>مستلزمات المدرسة</h2><p>العناصر التي خصصها أو سلّمها OLAMA Stores لهذا الطالب.</p></div><span class="olama-gateway__source">OLAMA Stores</span></section>
                <?php if (is_wp_error($data)) : ?><div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php elseif (!$data) : ?><div class="olama-gateway__empty">لا توجد عناصر مسجلة لهذا الطالب في السنة الحالية.</div>
                <?php else : ?>
                    <section class="olama-gateway__panel"><div class="olama-gateway__table-wrap"><table><thead><tr><th>العنصر</th><th>المستودع</th><th>الكمية</th><th>التاريخ</th><th>الحالة</th></tr></thead><tbody>
                        <?php foreach ($data as $assignment) : ?><tr><td><strong><?php echo esc_html($field($assignment, 'item_name')); ?></strong><small><?php echo esc_html($field($assignment, 'sku', '')); ?></small></td><td><?php echo esc_html($field($assignment, 'warehouse_name')); ?></td><td><?php echo esc_html($field($assignment, 'quantity', '1')); ?></td><td><?php echo esc_html($field($assignment, 'assigned_at', $field($assignment, 'created_at'))); ?></td><td><?php echo esc_html($field($assignment, 'status')); ?></td></tr><?php endforeach; ?>
                    </tbody></table></div></section>
                <?php endif; ?>

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
