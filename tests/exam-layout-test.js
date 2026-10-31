/* Run with Node and Playwright installed (or available through NODE_PATH).
 * Uses the sibling Exam Engine's real template, CSS and question navigation.
 * All exam requests are mocked: no student records or attempts are changed. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const engine = path.resolve(root, '../olama-exam-engine');
const read = file => fs.readFileSync(file, 'utf8');
const source = read(path.join(engine, 'includes/class-exam-shortcodes.php')).replace(/\r\n/g, '\n');
const start = source.indexOf('<div class="oe-container" dir="rtl" id="oe-exam-container"');
const end = source.indexOf('<?php\n        return ob_get_clean();', start);
assert.ok(start > 0 && end > start, 'Locate the real exam-taking template');
const markup = source.slice(start, end).replace(/<\?php[\s\S]*?\?>/g, '');
const gatewayCSS = read(path.join(root, 'assets/css/gateway.css'));
const examCSS = read(path.join(engine, 'assets/css/exam-student.css')).replace(/@import[^\r\n]+/g, '');
const examJS = read(path.join(engine, 'assets/js/exam-engine.js'));
const icon = '<img class="emoji" alt="✅" width="128" height="128" style="width:128px;height:128px" src="data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27%3E%3Ctext y=%2714%27%3E✅%3C/text%3E%3C/svg%3E">';

(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH });
    try {
        for (const [width, height, admin, reversed] of [
            [1366, 768, false, false], [1366, 768, true, true],
            [820, 1180, true, false], [390, 844, false, true],
            [360, 640, true, false], [844, 390, false, false],
        ]) {
            const page = await browser.newPage({ viewport: { width, height } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.setContent(`<!doctype html><html lang="ar" dir="rtl"><head>
                <style>body{margin:0}img{max-width:100%}</style>
                <style>${reversed ? gatewayCSS : examCSS}</style>
                <style>${reversed ? examCSS : gatewayCSS}</style></head>
                <body class="${admin ? 'admin-bar' : ''}">
                <div style="height:100px"></div><div class="olama-gateway">
                <aside class="olama-gateway__sidebar"></aside><main class="olama-gateway__main">
                <div class="olama-gateway__topbar">بوابة الطالب</div>
                <div class="olama-gateway__content"><section class="olama-gateway__panel olama-gateway__exam-runner">
                ${markup}</section></div></main></div><div style="height:100px"></div></body></html>`);
            await page.evaluate(() => {
                window.fetch = async () => ({ ok: true, json: async () => ({ success: true, data: {
                    attempt_id: 1, exam_title: 'اختبار الأردن في صدر الإسلام — عنوان طويل لاختبار العرض',
                    student_name: 'طالب تجريبي', total_questions: 30, duration_minutes: 60,
                    answers: {}, questions: Array.from({ length: 30 }, (_, i) => ({
                        question_id: i + 1, type: 'short', question_text: 'سؤال تجريبي للتنقل بين الأسئلة '.repeat(3),
                    })),
                } }) });
            });
            await page.addScriptTag({ content: examJS });
            await page.waitForSelector('#nav-q-30', { timeout: 5000 }).catch(async error => {
                console.error(await page.locator('#oe-loading').innerText(), errors);
                throw error;
            });
            await page.locator('#oe-submit-btn').evaluate((button, image) => { button.innerHTML = image + ' تسليم الاختبار'; }, icon);
            await page.locator('#q-1 .oe-q-text').evaluate(el => { el.innerHTML += '<img class="oe-q-image" width="200" height="100" alt="question illustration">'; });
            await page.evaluate(() => window.scrollTo(0, 2000));
            await page.waitForTimeout(150);
            const geometry = await page.evaluate(() => {
                const rect = selector => document.querySelector(selector).getBoundingClientRect().toJSON();
                return { nav: rect('#oe-navigation'), header: rect('#oe-header'),
                    icon: rect('#oe-submit-btn img'), picture: rect('.oe-q-image'),
                    overflow: getComputedStyle(document.querySelector('.olama-gateway')).overflow,
                    scrollWidth: document.documentElement.scrollWidth, width: innerWidth };
            });
            assert.equal(geometry.overflow, 'clip');
            assert.ok(geometry.nav.top >= geometry.header.bottom, 'Navigation clears timer');
            assert.ok(geometry.nav.bottom <= height, 'Navigation stays inside viewport');
            assert.ok(geometry.nav.height > 30, 'Navigation remains usable');
            assert.ok(geometry.icon.width <= 24 && geometry.icon.height <= 24, 'Emoji stays text-sized');
            assert.ok(geometry.picture.width > 24, 'Question illustrations retain their size');
            assert.ok(geometry.scrollWidth <= geometry.width, 'No horizontal page overflow');
            await page.locator('#nav-q-20').click();
            await page.waitForTimeout(1800);
            const target = await page.locator('#q-20').boundingBox();
            const navigation = await page.locator('#oe-navigation').boundingBox();
            const header = await page.locator('#oe-header').boundingBox();
            assert.ok(target.y >= (width <= 900 ? navigation.y + navigation.height : header.y + header.height) - 2,
                'Navigation click exposes the question below sticky controls');
            assert.ok(target.y < height, 'Selected question is visible');
            if (process.env.EXAM_LAYOUT_SCREENSHOTS) {
                fs.mkdirSync(process.env.EXAM_LAYOUT_SCREENSHOTS, { recursive: true });
                await page.screenshot({ path: path.join(process.env.EXAM_LAYOUT_SCREENSHOTS, `${width}x${height}.png`) });
            }
            await page.locator('#nav-q-2').click();
            await page.waitForTimeout(1800);
            const previous = await page.locator('#q-2').boundingBox();
            const previousNav = await page.locator('#oe-navigation').boundingBox();
            const previousHeader = await page.locator('#oe-header').boundingBox();
            assert.ok(previous.y >= (width <= 900 ? previousNav.y + previousNav.height : previousHeader.y + previousHeader.height) - 2,
                `Returning to an earlier question clears sticky controls: ${JSON.stringify({ previous, previousNav, previousHeader, width })}`);
            assert.ok(previous.y < height, 'Earlier question is visible');
            assert.deepEqual(errors, [], 'Exam initializes without browser errors');
            console.log(`PASS ${width}x${height}, admin=${admin}, reversed CSS=${reversed}`);
            await page.close();
        }
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; });
