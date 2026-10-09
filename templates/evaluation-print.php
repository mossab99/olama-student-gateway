<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="og-print-report" data-evaluation-print-layout dir="rtl">
    <header class="og-print-header"><div class="og-print-mark" aria-hidden="true">ع</div><div><strong>أكاديمية علماء المستقبل</strong><small>Future Scientists Academy</small></div><span class="og-print-status">تقييم معتمد</span></header>
    <section class="og-print-intro"><span>رحلة التعلم، مهارةً بمهارة</span><h1><?php echo esc_html($evaluation['title']); ?></h1><p>نشارككم ما اكتسبه الطالب من مهارات، ونواصل دعمه وتشجيعه في كل خطوة.</p></section>
    <dl class="og-print-meta"><div><dt>اسم الطالب</dt><dd><?php echo esc_html($student['student_name']); ?></dd></div><div><dt>الصف والشعبة</dt><dd><?php echo esc_html($student_grade($student)); ?></dd></div><div><dt>العام الدراسي</dt><dd><?php echo esc_html($context['study_year']); ?></dd></div><div><dt>آخر تحديث للتقييم</dt><dd><?php echo esc_html($evaluation['date']); ?></dd></div></dl>
    <section class="og-print-overview"><strong><?php echo absint($rated); ?> من <?php echo absint($total); ?> مهارة مقيّمة</strong><div class="og-print-legend"><?php foreach ($counts as $score => $count) : ?><span class="og-print-badge og-print-tone-<?php echo absint($tones[$score]); ?>"><?php echo esc_html($evaluation['scale'][$score]); ?> · <?php echo absint($count); ?></span><?php endforeach; ?></div><small>المهارات التي لم تُقيّم بعد لا تُحتسب كمستوى ضعيف.</small></section>
    <?php foreach ($evaluation['domains'] as $domain) : ?>
    <table class="og-print-skills"><thead><tr><th colspan="3" class="og-print-domain"><?php echo esc_html($domain['title']); ?></th></tr><tr><th>المهارة</th><th>التقييم</th><th>ملاحظة المعلم</th></tr></thead><tbody>
        <?php foreach ($domain['categories'] as $category) : ?>
        <tr class="og-print-category"><th colspan="3"><?php echo esc_html($category['title']); ?></th></tr>
        <?php foreach ($category['indicators'] as $indicator) : $tone = null !== $indicator['score'] && isset($tones[$indicator['score']]) ? 'og-print-tone-' . $tones[$indicator['score']] : 'og-print-pending'; ?>
        <tr><td><?php echo esc_html($indicator['text']); ?></td><td><span class="og-print-badge <?php echo esc_attr($tone); ?>"><?php echo esc_html($indicator['result']); ?></span></td><td class="og-print-note"><?php echo !empty($indicator['notes']) ? esc_html($indicator['notes']) : '—'; ?></td></tr>
        <?php endforeach; endforeach; ?>
    </tbody></table>
    <?php endforeach; ?>
    <?php if (!empty($evaluation['comments'])) : ?><section class="og-print-comments"><h2>ملاحظات المعلم والمشرف</h2><p><?php echo esc_html($evaluation['comments']); ?></p></section><?php endif; ?>
    <footer class="og-print-footer"><strong>معًا ندعم التعلم ونحتفل بالتقدم</strong><span>أكاديمية علماء المستقبل · تقرير التقييم المعتمد</span></footer>
</div>
