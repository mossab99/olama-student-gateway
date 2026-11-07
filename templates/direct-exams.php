<?php
if (!defined('ABSPATH')) { exit; }
$catalog = $data['direct_catalog'] ?? array();
$selected_subject = isset($_GET['og_direct_subject']) ? absint($_GET['og_direct_subject']) : 0;
$subject_ids = array_map(static function ($entry) { return (int) $entry['subject']->id; }, $catalog);
if (!in_array($selected_subject, $subject_ids, true)) { $selected_subject = $subject_ids[0] ?? 0; }
$by_lesson = array();
foreach (($data['online_exams'] ?? array()) as $exam) {
    $by_lesson[(int) $exam->direct_lesson_id][] = $exam;
}
?>
<section class="olama-gateway__panel og-direct" dir="rtl">
    <div class="og-direct-subjects" aria-label="المواد الدراسية">
        <?php foreach ($catalog as $entry): $subject = $entry['subject']; ?>
            <a class="og-direct-subject <?php echo $selected_subject === (int) $subject->id ? 'is-active' : ''; ?>" <?php if ($selected_subject === (int) $subject->id): ?>aria-current="page"<?php endif; ?> href="<?php echo esc_url($make_url(array('og_view' => 'exams', 'og_exam_section' => 'direct', 'og_student' => $student['student_uid'], 'og_direct_subject' => (int) $subject->id))); ?>"><?php echo esc_html($subject->subject_name); ?></a>
        <?php endforeach; ?>
    </div>
    <?php if (!$catalog): ?><div class="olama-gateway__empty">لا توجد مواد دراسية متاحة لهذا الصف.</div><?php endif; ?>
    <?php foreach ($catalog as $entry):
        if ((int) $entry['subject']->id !== $selected_subject) { continue; }
        $lesson_count = 0; $exam_count = 0;
        foreach ($entry['units'] as $group) {
            $lesson_count += count($group['lessons']);
            foreach ($group['lessons'] as $lesson) { $exam_count += count($by_lesson[(int) $lesson->id] ?? array()); }
        }
    ?>
        <div class="og-direct-stats"><div><strong><?php echo count($entry['units']); ?></strong><span>وحدات دراسية</span></div><div><strong><?php echo (int) $lesson_count; ?></strong><span>دروس المادة</span></div><div><strong><?php echo (int) $exam_count; ?></strong><span>امتحانات دروس مباشرة متاحة</span></div></div>
        <p class="og-direct-info">تظهر جميع دروس المادة هنا. يتاح التقدم للاختبار بعد تفعيله من المعلم، دون حد زمني وبمحاولات غير محدودة.</p>
        <?php if (!$entry['units']): ?><div class="olama-gateway__empty">لا توجد وحدات لهذه المادة في الفصل الدراسي الحالي.</div><?php endif; ?>
        <?php foreach ($entry['units'] as $group): $unit = $group['unit']; ?>
            <details class="og-direct-unit" open>
                <summary><span class="og-direct-number"><?php echo esc_html($unit->unit_number); ?></span><strong><?php echo esc_html($unit->unit_name); ?></strong><small><?php echo count($group['lessons']); ?> دروس</small><span class="og-direct-toggle">عرض / إخفاء الدروس</span></summary>
                <div class="og-direct-lessons">
                    <?php foreach ($group['lessons'] as $lesson): $lesson_exams = $by_lesson[(int) $lesson->id] ?? array(); ?>
                        <div class="og-direct-lesson">
                            <div class="og-direct-lesson-title"><span><?php echo esc_html($lesson->lesson_number); ?></span><strong><?php echo esc_html($lesson->lesson_title); ?></strong></div>
                            <?php if (!$lesson_exams): ?><small class="og-direct-muted">لا يوجد امتحان متاح حالياً</small><?php endif; ?>
                            <?php foreach ($lesson_exams as $exam):
                                $launch_url = $make_url(array('og_view' => 'exams', 'og_exam_section' => 'direct', 'og_student' => $student['student_uid']));
                                $action = Olama_Student_Gateway_Exam_Launcher::action($exam, $student['student_uid'], $launch_url);
                            ?>
                                <div class="og-direct-exam"><span><?php echo esc_html($exam->title); ?></span><span class="og-direct-badge">بلا حد زمني · محاولات غير محدودة</span><?php if ($action['url']): ?><a class="olama-gateway__button olama-gateway__button--compact" href="<?php echo esc_url($action['url']); ?>"><?php echo esc_html($action['label']); ?></a><?php endif; ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (!$group['lessons']): ?><p class="og-direct-muted">لا توجد دروس في هذه الوحدة.</p><?php endif; ?>
                </div>
            </details>
        <?php endforeach; ?>
    <?php endforeach; ?>
</section>
<style>
.og-direct-subjects{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px}.og-direct-subject{padding:10px 18px;border:1px solid #dfe5f1;border-radius:9px;background:#f8fafc;color:#334155;text-decoration:none}.og-direct-subject.is-active{background:#4f46e5;color:#fff;border-color:#4f46e5}.og-direct-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;margin-bottom:16px}.og-direct-stats>div{padding:18px;border:1px solid #dfe5f1;border-radius:10px;background:#fff}.og-direct-stats strong{display:block;font-size:24px;color:#4338ca}.og-direct-stats span,.og-direct-muted{font-size:12px;color:#64748b}.og-direct-info{background:#f5f7ff;border:1px solid #e0e7ff;border-radius:9px;padding:14px;color:#475569}.og-direct-unit{border:1px solid #dfe5f1;border-radius:11px;margin:12px 0;overflow:hidden;background:#fff}.og-direct-unit summary{display:flex;align-items:center;gap:12px;padding:18px;cursor:pointer;list-style:none}.og-direct-unit summary::-webkit-details-marker{display:none}.og-direct-number{display:grid;place-items:center;width:36px;height:36px;border-radius:10px;background:#7770ff;color:#fff}.og-direct-unit summary small{color:#64748b}.og-direct-toggle{margin-inline-start:auto;font-size:12px;color:#4f46e5}.og-direct-lessons{padding:0 18px 18px}.og-direct-lesson{padding:14px;border:1px solid #dfe5f1;border-radius:8px;background:#f8fafc;margin-top:10px}.og-direct-lesson-title{display:flex;align-items:center;gap:10px;margin-bottom:8px}.og-direct-lesson-title>span{padding:2px 7px;background:#fff;border:1px solid #dfe5f1;border-radius:5px}.og-direct-exam{display:flex;flex-wrap:wrap;align-items:center;gap:10px;border:1px solid #e2e8f0;border-radius:8px;background:#fff;padding:12px;margin-top:8px}.og-direct-exam>a{margin-inline-start:auto}.og-direct-badge{background:#dcfce7;color:#15803d;padding:4px 9px;border-radius:20px;font-size:11px}@media(max-width:600px){.og-direct-stats{gap:6px}.og-direct-stats>div{padding:10px}.og-direct-unit summary{padding:12px;flex-wrap:wrap}.og-direct-toggle{width:100%}.og-direct-lessons{padding:0 10px 10px}.og-direct-exam{flex-direction:column;align-items:stretch}.og-direct-exam>a{margin:0;text-align:center}}
</style>
