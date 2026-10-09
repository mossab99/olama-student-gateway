<?php
if (!defined('ABSPATH')) { exit; }
?>
<section class="olama-gateway__heading"><div><h2>التقييمات</h2><p>رحلة التعلم، مهارةً بمهارة · التقييمات المعتمدة للفصل الحالي</p></div></section>
<?php if (is_wp_error($data)) : ?>
<div class="olama-gateway__empty"><?php echo esc_html($data->get_error_message()); ?></div>
<?php elseif (empty($data['evaluations'])) : ?>
<div class="olama-gateway__empty">لا توجد تقييمات معتمدة لهذا الطالب في الفصل الدراسي الحالي.</div>
<?php else : ?>
<div class="og-evaluations" data-evaluations>
    <nav class="og-evaluation-tabs" aria-label="اختيار التقييم">
        <?php foreach ($data['evaluations'] as $index => $evaluation) : ?>
        <a href="#og-evaluation-<?php echo absint($evaluation['id']); ?>" data-evaluation-tab aria-current="<?php echo 0 === $index ? 'true' : 'false'; ?>"><span class="dashicons dashicons-welcome-learn-more" aria-hidden="true"></span><span><?php echo esc_html($evaluation['title']); ?><small>تقييم معتمد</small></span></a>
        <?php endforeach; ?>
    </nav>
    <?php foreach ($data['evaluations'] as $index => $evaluation) :
        $counts = array_fill_keys(array_keys($evaluation['scale']), 0);
        $total = 0; $rated = 0;
        foreach ($evaluation['domains'] as $domain) {
            foreach ($domain['categories'] as $category) {
                foreach ($category['indicators'] as $indicator) {
                    $total++;
                    if (null !== $indicator['score'] && array_key_exists($indicator['score'], $counts)) {
                        $counts[$indicator['score']]++; $rated++;
                    }
                }
            }
        }
        $percent = $total ? round(100 * $rated / $total) : 0;
        $tones = array(); $position = 0;
        foreach ($counts as $score => $count) { $tones[$score] = min(4, $position++); }
    ?>
    <article class="og-evaluation-report" id="og-evaluation-<?php echo absint($evaluation['id']); ?>" data-evaluation-report>
        <header class="og-evaluation-banner">
            <div><span class="og-evaluation-eyebrow">كل خطوة صغيرة تستحق التشجيع</span><h3><?php echo esc_html($evaluation['title']); ?></h3><p><?php echo esc_html($student['student_name']); ?> · <?php echo esc_html($student_grade($student)); ?></p><small><?php echo esc_html($context['study_year']); ?> · آخر تحديث: <?php echo esc_html($evaluation['date']); ?></small></div>
            <button type="button" class="olama-gateway__button og-evaluation-print" data-evaluation-print><span class="dashicons dashicons-printer" aria-hidden="true"></span>طباعة التقرير</button>
        </header>
        <div class="og-evaluation-workspace">
            <div class="og-evaluation-domains">
                <?php foreach ($evaluation['domains'] as $domain) : ?>
                <details class="og-evaluation-domain" open>
                    <summary><span><span class="dashicons dashicons-book-alt" aria-hidden="true"></span><?php echo esc_html($domain['title']); ?></span><span class="og-evaluation-chevron" aria-hidden="true">⌄</span></summary>
                    <?php foreach ($domain['categories'] as $category) : ?>
                    <section class="og-evaluation-category"><h4><?php echo esc_html($category['title']); ?></h4>
                        <?php foreach ($category['indicators'] as $indicator) :
                            $tone = null !== $indicator['score'] && isset($tones[$indicator['score']]) ? 'og-evaluation-tone-' . $tones[$indicator['score']] : 'og-evaluation-pending';
                        ?>
                        <div class="og-evaluation-skill"><span><?php echo esc_html($indicator['text']); ?><?php if (!empty($indicator['notes'])) : ?><small class="og-evaluation-skill-note"><b>ملاحظة المعلم:</b> <?php echo esc_html($indicator['notes']); ?></small><?php endif; ?></span><strong class="og-evaluation-badge <?php echo esc_attr($tone); ?>"><?php echo esc_html($indicator['result']); ?></strong></div>
                        <?php endforeach; ?>
                    </section>
                    <?php endforeach; ?>
                </details>
                <?php endforeach; ?>
                <?php if (!$total) : ?><div class="olama-gateway__empty">لا توجد مهارات مسجلة في نموذج التقييم.</div><?php endif; ?>
            </div>
            <aside class="og-evaluation-summary" aria-label="ملخص التقييم">
                <h4>ملخص التقييم</h4>
                <div class="og-evaluation-ring" style="--evaluation-progress:<?php echo absint($percent); ?>%"><div><strong><?php echo absint($rated); ?> / <?php echo absint($total); ?></strong><small>مهارة مقيّمة</small></div></div>
                <p>توزيع نتائج المهارات</p>
                <?php foreach ($counts as $score => $count) : ?>
                <div class="og-evaluation-distribution"><span><?php echo esc_html($evaluation['scale'][$score]); ?></span><span class="og-evaluation-track"><i class="og-evaluation-tone-<?php echo absint($tones[$score]); ?>" style="width:<?php echo $rated ? absint(round(100 * $count / $rated)) : 0; ?>%"></i></span><b><?php echo absint($count); ?></b></div>
                <?php endforeach; ?>
                <?php if ($total > $rated) : ?><div class="og-evaluation-pending-note"><?php echo absint($total - $rated); ?> مهارة لم تُقيّم بعد</div><?php endif; ?>
                <small>المهارات غير المقيّمة لا تُحتسب كمستوى ضعيف. الملخص يعرض عدد المهارات، وليس علامة الطالب.</small>
            </aside>
        </div>
        <?php if (!empty($evaluation['comments'])) : ?>
        <section class="og-evaluation-comments"><h4><span class="dashicons dashicons-format-chat" aria-hidden="true"></span>ملاحظات المعلم والمشرف</h4><p><?php echo esc_html($evaluation['comments']); ?></p></section>
        <?php endif; ?>
        <?php include __DIR__ . '/evaluation-print.php'; ?>
    </article>
    <?php endforeach; ?>
</div>
<?php endif; ?>
