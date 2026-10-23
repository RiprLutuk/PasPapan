import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { chromium } from '@playwright/test';
import { buildPageScreenshotCatalog } from './page-screenshot-catalog.mjs';

const appUrl = (process.env.APP_URL || process.env.E2E_BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const outputDir = process.env.SCREENSHOT_DIR || 'screenshots/desktop-pages';
const loginToken = process.env.E2E_LOGIN_TOKEN;

if (!loginToken) {
  throw new Error('E2E_LOGIN_TOKEN is required.');
}
const userEmail = process.env.APK_SCREENSHOT_USER_EMAIL || 'apk.demo.user@paspapan.test';
const adminEmail = process.env.APK_SCREENSHOT_ADMIN_EMAIL || 'apk.demo.superadmin@paspapan.test';
const password = process.env.APK_SCREENSHOT_PASSWORD || '12345678';
const settleMs = Number(process.env.DESKTOP_SCREENSHOT_SETTLE_MS || 1200);
const viewport = {
  width: Number(process.env.DESKTOP_SCREENSHOT_WIDTH || 1440),
  height: Number(process.env.DESKTOP_SCREENSHOT_HEIGHT || 1000),
};

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function waitForPageReady(page) {
  await page.waitForLoadState('domcontentloaded');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.locator('body').waitFor({ state: 'visible' });
  await page.evaluate(() => window.scrollTo(0, 0));
  await delay(settleMs);
}

async function capturePage(page, entry, index) {
  if (entry.prepareState) {
    execFileSync('php', ['scripts/prepare-page-screenshot-state.php', entry.prepareState], {
      env: {
        ...process.env,
        APK_SCREENSHOT_USER_EMAIL: userEmail,
        APK_SCREENSHOT_ADMIN_EMAIL: adminEmail,
      },
      stdio: ['ignore', 'pipe', 'inherit'],
    });
  }

  if (entry.clearCookies) {
    await page.context().clearCookies();
  }

  await page.goto(entry.url, { waitUntil: 'domcontentloaded' });

  if (entry.expectedPath) {
    await page.waitForURL((url) => url.pathname === entry.expectedPath, { timeout: 30000 });
  }

  await waitForPageReady(page);

  if (entry.readyExpression) {
    await page.waitForFunction(entry.readyExpression, null, { timeout: 30000 });
    await delay(settleMs);
  }

  if (entry.afterNavigate) {
    await page.evaluate(entry.afterNavigate);
    await delay(settleMs);
  }

  const filename = `${String(index + 1).padStart(2, '0')}-${entry.slug}.png`;
  const targetPath = path.join(outputDir, filename);
  await page.screenshot({ path: targetPath, fullPage: false });

  return {
    label: entry.label,
    slug: entry.slug,
    path: targetPath,
    url: page.url(),
    title: await page.title(),
    screenshot: {
      mode: 'desktop-viewport',
      viewport,
    },
  };
}

const pages = buildPageScreenshotCatalog({
  adminEmail,
  appUrl,
  loginToken,
  userEmail,
});

fs.mkdirSync(outputDir, { recursive: true });
for (const entry of fs.readdirSync(outputDir)) {
  if (entry.endsWith('.png') || entry === 'manifest.json') {
    fs.rmSync(path.join(outputDir, entry));
  }
}

execFileSync('php', ['scripts/prepare-apk-screenshots-demo.php'], {
  env: {
    ...process.env,
    APK_SCREENSHOT_USER_EMAIL: userEmail,
    APK_SCREENSHOT_ADMIN_EMAIL: adminEmail,
    APK_SCREENSHOT_PASSWORD: password,
  },
  stdio: ['ignore', 'pipe', 'inherit'],
});

const browser = await chromium.launch();
const context = await browser.newContext({ viewport });
const page = await context.newPage();
const captured = [];

for (const [index, entry] of pages.entries()) {
  const result = await capturePage(page, entry, index);
  captured.push(result);
  console.log(JSON.stringify(result));
}

const manifestPath = path.join(outputDir, 'manifest.json');
fs.writeFileSync(manifestPath, `${JSON.stringify({
  generated_at: new Date().toISOString(),
  app_url: appUrl,
  viewport,
  screenshots: captured,
}, null, 2)}\n`);

await browser.close();

console.log(JSON.stringify({
  ok: true,
  count: captured.length,
  output_dir: outputDir,
  manifest: manifestPath,
}));
