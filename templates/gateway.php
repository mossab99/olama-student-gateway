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
$first_name = $student && !empty($student['student_name']) ? strtok($student['student_name'], ' ') : '';
?>
<div class="olama-gateway" dir="rtl" data-olama-gateway>
    <button class="olama-gateway__menu" type="button" data-gateway-menu aria-expanded="false">
        <span class="dashicons dashicons-menu" aria-hidden="true"></span>
        <span>القائمة</span>
    </button>

    <aside class="olama-gateway__sidebar" data-gateway-sidebar>
        <div class="olama-gateway__brand">
            <span class="olama-gateway__brand-mark">ع</span>
            <span><strong>بوابة OLAMA</strong><small>حساب الأسرة</small></span>
        </div>
        <div class="olama-gateway__family-chip">
            <span class="dashicons dashicons-groups" aria-hidden="true"></span>
            <span><strong><?php echo esc_html($field($family, 'sponsor_full_name', 'الأسرة')); ?></strong><small>رقم الأسرة <?php echo esc_html($context['family_id']); ?></small></span>
        </div>
        <nav class="olama-gateway__nav" aria-label="أقسام البوابة">
            <?php foreach ($model['views'] as $view_key => $view) :
                $args = array('og_view' => $view_key);
                if ('family' !== $view_key && $student) {
                    $args['og_student'] = $student['student_uid'];
                }
                ?>
                <a class="<?php echo $active_view === $view_key ? 'is-active' : ''; ?>" href="<?php echo esc_url($make_url($args)); ?>">
                    <span class="dashicons <?php echo esc_attr($view['icon']); ?>" aria-hidden="true"></span>
                    <?php echo esc_html($view['label']); ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <a class="olama-gateway__logout" href="<?php echo esc_url($model['logout_url']); ?>">
            <span class="dashicons dashicons-exit" aria-hidden="true"></span> تسجيل الخروج
        </a>
    </aside>

    <main class="olama-gateway__main">
        <header class="olama-gateway__topbar">
            <?php if ($student) : ?>
                <div class="olama-gateway__student-context">
                    <span class="olama-gateway__avatar"><?php echo esc_html($initial($student['student_name'])); ?></span>
                    <span><strong><?php echo esc_html($student['student_name']); ?></strong><small><?php echo esc_html($student_grade($student)); ?></small></span>
                </div>
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
            <?php else : ?>
                <div><strong>لوحة الأسرة</strong><small><?php echo esc_html($context['study_year'] ?: 'السنة الدراسية غير محددة'); ?></small></div>
            <?php endif; ?>
        </header>

        <div class="olama-gateway__content">
            <?php if ('family' === $active_view) : ?>
                <section class="olama-gateway__heading">
                    <div><h2>مرحباً بكم في بوابة الأسرة</h2><p>اختر أحد الأبناء لمتابعة معلوماته، أو راجع بطاقة الأسرة والملخص المالي.</p></div>
                    <span class="olama-gateway__status">بيانات OLAMA Core</span>
                </section>

                <section class="olama-gateway__member-grid" aria-label="أفراد الأسرة">
                    <?php if (!$students) : ?>
                        <div class="olama-gateway__empty">لا يوجد طلاب مرتبطون بحساب الأسرة في OLAMA Core.</div>
                    <?php endif; ?>
                    <?php foreach ($students as $family_student) : ?>
                        <a class="olama-gateway__member" href="<?php echo esc_url($student_url($family_student['student_uid'])); ?>">
                            <span class="olama-gateway__avatar"><?php echo esc_html($initial($family_student['student_name'])); ?></span>
                            <span><strong><?php echo esc_html($family_student['student_name']); ?></strong><small><?php echo esc_html($student_grade($family_student)); ?></small></span>
                            <span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
                        </a>
                    <?php endforeach; ?>
                </section>

                <section class="olama-gateway__grid olama-gateway__grid--2">
                    <?php if (current_user_can('olama_student_gateway_family_view')) : ?>
                    <article class="olama-gateway__panel">
                        <header><h3>بطاقة الأسرة</h3><span class="olama-gateway__status olama-gateway__status--success">فعالة</span></header>
                        <dl class="olama-gateway__details">
                            <div><dt>ولي الأمر</dt><dd><?php echo esc_html($field($family, 'sponsor_full_name')); ?></dd></div>
                            <div><dt>اسم الأم</dt><dd><?php echo esc_html($field($family, 'mother_name')); ?></dd></div>
                            <div><dt>الهاتف المعتمد</dt><dd><?php echo esc_html($field($family, 'primary_mobile')); ?></dd></div>
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
                <section class="olama-gateway__heading">
                    <div><h2>مرحباً <?php echo esc_html($first_name); ?></h2><p>ملخص المعلومات المنشورة للطالب في السنة الدراسية الحالية.</p></div>
                    <a class="olama-gateway__button" href="<?php echo esc_url($make_url(array('og_view' => 'family'))); ?>">عرض أفراد الأسرة</a>
                </section>
                <?php
                $weekly = isset($data['weekly_plan']) ? $data['weekly_plan'] : array();
                $transport = isset($data['transportation']) ? $data['transportation'] : array();
                ?>
                <section class="olama-gateway__grid olama-gateway__grid--2">
                    <article class="olama-gateway__panel">
                        <header><h3>الخطة الأسبوعية</h3><a href="<?php echo esc_url($student_url($student['student_uid'], 'weekly_plan')); ?>">عرض التفاصيل</a></header>
                        <?php if (is_wp_error($weekly)) : ?><div class="olama-gateway__empty"><?php echo esc_html($weekly->get_error_message()); ?></div>
                        <?php elseif (empty($weekly['days'])) : ?><div class="olama-gateway__empty">لا توجد خطة أسبوعية متاحة.</div>
                        <?php else : ?>
                            <div class="olama-gateway__week-summary">
                                <?php foreach ($weekly['days'] as $day) : ?>
                                    <div><strong><?php echo esc_html($day['label']); ?></strong><span><?php echo esc_html(count($day['plans'])); ?> مواد</span></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                    <article class="olama-gateway__panel">
                        <header><h3>المواصلات</h3><a href="<?php echo esc_url($student_url($student['student_uid'], 'transportation')); ?>">عرض التفاصيل</a></header>
                        <?php if (is_wp_error($transport) || empty($transport['registration'])) : ?>
                            <div class="olama-gateway__empty">لا توجد معلومات مواصلات للطالب في السنة الحالية.</div>
                        <?php else : $registration = $transport['registration']; ?>
                            <dl class="olama-gateway__details">
                                <div><dt>حافلة الذهاب</dt><dd><?php echo esc_html($field($registration, 'departure_bus_name', $field($registration, 'departure_bus'))); ?></dd></div>
                                <div><dt>حافلة العودة</dt><dd><?php echo esc_html($field($registration, 'arrival_bus_name', $field($registration, 'arrival_bus'))); ?></dd></div>
                                <div class="is-wide"><dt>المسار</dt><dd><?php echo esc_html($field($registration, 'trans_route_name')); ?></dd></div>
                            </dl>
                        <?php endif; ?>
                    </article>
                </section>

            <?php elseif ('weekly_plan' === $active_view) : ?>
                <section class="olama-gateway__heading"><div><h2>الخطة الأسبوعية</h2><p>الدروس والواجبات المعتمدة والمنشورة فقط.</p></div></section>
                <?php if (is_wp_error($data)) : ?>
                    <div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
                <?php else : ?>
                    <div class="olama-gateway__week-nav">
                        <a href="<?php echo esc_url($make_url(array('og_student' => $student['student_uid'], 'og_view' => 'weekly_plan', 'og_week' => $data['previous_week']))); ?>">الأسبوع السابق</a>
                        <strong><?php echo esc_html($data['week_start']); ?> — <?php echo esc_html($data['week_end']); ?></strong>
                        <a href="<?php echo esc_url($make_url(array('og_student' => $student['student_uid'], 'og_view' => 'weekly_plan', 'og_week' => $data['next_week']))); ?>">الأسبوع التالي</a>
                    </div>
                    <section class="olama-gateway__week-board">
                        <?php foreach ($data['days'] as $day) : ?>
                            <article class="olama-gateway__day">
                                <header><strong><?php echo esc_html($day['label']); ?></strong><small><?php echo esc_html($day['date']); ?></small></header>
                                <?php if (!$day['plans']) : ?><div class="olama-gateway__day-empty">لا توجد مواد منشورة</div><?php endif; ?>
                                <?php foreach ($day['plans'] as $plan) : ?>
                                    <div class="olama-gateway__lesson">
                                        <strong><?php echo esc_html($plan['subject']); ?></strong>
                                        <span><?php echo esc_html($plan['lesson'] ?: $plan['topic']); ?></span>
                                        <?php foreach (array('homework_student_book', 'homework_exercise_book', 'homework_notebook', 'homework_worksheet') as $homework_key) : ?>
                                            <?php if (!empty($plan[$homework_key])) : ?><small><?php echo esc_html($plan[$homework_key]); ?></small><?php endif; ?>
                                        <?php endforeach; ?>
                                        <?php if (!empty($plan['teacher_notes'])) : ?><em><?php echo esc_html($plan['teacher_notes']); ?></em><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>

            <?php elseif ('exams' === $active_view) : ?>
                <section class="olama-gateway__heading"><div><h2>الامتحانات والنتائج</h2><p>جدول الامتحانات المعتمد، القاعة، الامتحانات الإلكترونية، والنتائج حسب مصدرها.</p></div><span class="olama-gateway__status">المصدر موضح لكل مجموعة</span></section>
                <?php if (is_wp_error($data)) : ?>
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

                    <section class="olama-gateway__panel">
                        <header><h3>جدول الامتحانات المعتمد</h3><span class="olama-gateway__source">OLAMA Exam Management</span></header>
                        <?php if (!$scheduled_exams) : ?><div class="olama-gateway__empty">لا توجد امتحانات معتمدة منشورة للطالب.</div>
                        <?php else : ?><div class="olama-gateway__table-wrap"><table><thead><tr><th>التاريخ</th><th>المادة</th><th>نوع التقييم</th><th>المادة المطلوبة</th><th>الغرفة</th></tr></thead><tbody>
                            <?php foreach ($scheduled_exams as $exam) : ?><tr><td><?php echo esc_html($field($exam, 'exam_date')); ?></td><td><strong><?php echo esc_html($field($exam, 'subject_name')); ?></strong></td><td><?php echo esc_html($field($exam, 'evaluation_type')); ?></td><td><?php echo esc_html($field($exam, 'student_book_material', $field($exam, 'description'))); ?></td><td><?php echo esc_html($field($exam, 'room_number', $field($exam, 'master_room'))); ?></td></tr><?php endforeach; ?>
                        </tbody></table></div><?php endif; ?>
                    </section>

                    <section class="olama-gateway__panel">
                        <header><h3>الامتحانات الإلكترونية</h3><span class="olama-gateway__source">OLAMA Exam Engine</span></header>
                        <?php if (!$online_exams) : ?><div class="olama-gateway__empty">لا توجد امتحانات إلكترونية منشورة حالياً.</div>
                        <?php else : ?><div class="olama-gateway__table-wrap"><table><thead><tr><th>الامتحان</th><th>المادة</th><th>البداية</th><th>النهاية</th><th>المدة</th><th>الحالة</th></tr></thead><tbody>
                            <?php foreach ($online_exams as $exam) : ?><tr><td><strong><?php echo esc_html($field($exam, 'title')); ?></strong></td><td><?php echo esc_html($field($exam, 'subject_name')); ?></td><td><?php echo esc_html($field($exam, 'start_time')); ?></td><td><?php echo esc_html($field($exam, 'end_time')); ?></td><td><?php echo esc_html($field($exam, 'duration_minutes')); ?> دقيقة</td><td><?php echo esc_html($field($exam, 'status')); ?></td></tr><?php endforeach; ?>
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
