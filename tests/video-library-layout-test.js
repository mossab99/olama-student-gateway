const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { execFileSync } = require('node:child_process');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');
const root = path.resolve(__dirname, '..');
const html = execFileSync('php', [path.join(__dirname, 'video-library-render-test.php'), '--html'], { encoding: 'utf8' });
(async () => {
    const browser = await chromium.launch({ headless: true, executablePath: process.env.PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH });
    try {
        for (const width of [1366, 390]) {
            const page = await browser.newPage({ viewport: { width, height: 850 } });
            const errors = [];
            page.on('pageerror', error => errors.push(error.message));
            await page.route('https://drive.google.com/**', route => route.fulfill({ contentType: 'text/html', body: '<p>Embedded player fixture</p>' }));
            await page.route('https://school.test/**', route => route.fulfill({ contentType: 'video/mp4', body: '' }));
            await page.setContent(html);
            await page.addStyleTag({ content: fs.readFileSync(path.join(root, 'assets/css/gateway.css'), 'utf8') });
            await page.addScriptTag({ content: fs.readFileSync(path.join(root, 'assets/js/gateway.js'), 'utf8') });
            assert.equal(await page.locator('[data-video-card]:visible').count(), 2);
            assert.equal(await page.locator('[data-video-subject]:visible').count(), 0);
            await page.locator('[data-video-search]').fill('Science');
            assert.equal(await page.locator('[data-video-card]:visible').count(), 1);
            await page.locator('[data-video-card]:visible').click();
            assert.equal(await page.locator('[data-video-subject]:visible').count(), 1);
            assert.equal(await page.locator('[data-video-cards]').isVisible(), false);
            await page.locator('[data-video-subject]:visible [data-video-play]').first().click();
            assert.equal(await page.locator('dialog').evaluate(el => el.open), true);
            assert.equal(await page.locator('dialog iframe').getAttribute('src'), 'https://drive.google.com/file/d/file_123/preview');
            assert.equal(page.url(), 'about:blank');
            await page.keyboard.press('Escape');
            await page.waitForFunction(() => document.querySelector('[data-video-screen]').children.length === 0);
            assert.equal(await page.locator('[data-video-subject]:visible [data-video-play]').first().evaluate(el => el === document.activeElement), true);
            await page.locator('[data-video-subject]:visible [data-video-play]').nth(1).click();
            assert.equal(await page.locator('dialog video[controls]').count(), 1);
            await page.locator('[data-video-close]').click();
            await page.locator('[data-video-back]:visible').click();
            await page.locator('[data-video-search]').fill('missing');
            assert.equal(await page.locator('[data-video-empty]').isVisible(), true);
            await page.locator('[data-video-search]').fill('');
            await page.locator('[data-video-card]').nth(1).click();
            assert.equal(await page.locator('[data-video-subject]:visible h3').textContent(), 'Arabic');
            const trigger = page.locator('[data-video-subject]:visible [data-video-play]').first();
            await trigger.evaluate(el => el.setAttribute('data-video-url', 'javascript:alert(1)'));
            await trigger.click();
            assert.equal(await page.locator('[data-video-error]').isVisible(), true);
            assert.equal(await page.locator('dialog iframe').count(), 0);
            await page.locator('[data-video-close]').click();
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true, 'Page should fit viewport');
            assert.deepEqual(errors, []);
            await page.close();
        }
        console.log('Video subject selection, embedded playback, keyboard close, unsafe-link handling, and mobile layout passed.');
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exit(1); });
