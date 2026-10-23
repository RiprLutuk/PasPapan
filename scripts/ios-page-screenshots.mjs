import { execFileSync, spawn } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { chromium, devices, webkit } from '@playwright/test';
import { buildPageScreenshotCatalog } from './page-screenshot-catalog.mjs';

const appUrl = (process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const appPort = new URL(appUrl).port || '8000';
const outputDir = process.env.SCREENSHOT_DIR || 'screenshots/ios-user-pages';
const loginToken = process.env.E2E_LOGIN_TOKEN;

if (!loginToken) {
  throw new Error('E2E_LOGIN_TOKEN is required.');
}
const userEmail = process.env.APK_SCREENSHOT_USER_EMAIL || 'apk.demo.user@paspapan.test';
const adminEmail = process.env.APK_SCREENSHOT_ADMIN_EMAIL || 'apk.demo.superadmin@paspapan.test';
const password = process.env.APK_SCREENSHOT_PASSWORD || '12345678';
const settleMs = Number(process.env.IOS_PAGE_SCREENSHOT_SETTLE_MS || 1400);
const deviceName = process.env.IOS_PAGE_DEVICE || 'iPhone 14 Pro Max';
const deviceProfile = devices[deviceName] || devices['iPhone 14 Pro'] || {
  viewport: { width: 393, height: 852 },
  deviceScaleFactor: 3,
  isMobile: true,
  hasTouch: true,
  userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
};
const emulateNativeApp = process.env.IOS_PAGE_NATIVE_APP !== 'false';
const nativeViewport = {
  width: Number(process.env.IOS_PAGE_SCREENSHOT_WIDTH || 430),
  height: Number(process.env.IOS_PAGE_SCREENSHOT_HEIGHT || 932),
};
const screenshotProfile = emulateNativeApp
  ? {
      ...deviceProfile,
      viewport: nativeViewport,
      isMobile: true,
      hasTouch: true,
    }
  : deviceProfile;

let serverProcess = null;

function exec(command, args, options = {}) {
  return execFileSync(command, args, {
    stdio: options.stdio || 'pipe',
    encoding: options.encoding || 'utf8',
    env: {
      ...process.env,
      ...(options.env || {}),
    },
    maxBuffer: options.maxBuffer || 40 * 1024 * 1024,
  });
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function canReach(url) {
  try {
    const response = await fetch(url, { redirect: 'manual' });

    return response.status < 500;
  } catch {
    return false;
  }
}

async function ensureLaravelServer() {
  if (await canReach(`${appUrl}/login`)) {
    return;
  }

  serverProcess = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${appPort}`], {
    stdio: ['ignore', 'ignore', 'pipe'],
  });

  for (let attempt = 0; attempt < 40; attempt += 1) {
    if (await canReach(`${appUrl}/login`)) {
      return;
    }

    await delay(500);
  }

  throw new Error(`Laravel app is not reachable at ${appUrl}.`);
}

function cleanup() {
  if (serverProcess) {
    serverProcess.kill();
  }
}

function prepareOutputDir() {
  fs.mkdirSync(outputDir, { recursive: true });

  for (const entry of fs.readdirSync(outputDir)) {
    if (entry.endsWith('.png') || entry === 'manifest.json') {
      fs.rmSync(path.join(outputDir, entry));
    }
  }
}

function prepareDemoData() {
  exec('php', ['scripts/prepare-apk-screenshots-demo.php'], {
    env: {
      APK_SCREENSHOT_USER_EMAIL: userEmail,
      APK_SCREENSHOT_ADMIN_EMAIL: adminEmail,
      APK_SCREENSHOT_PASSWORD: password,
    },
    stdio: 'ignore',
  });
}

function preparePageState(page) {
  if (! page.prepareState) {
    return;
  }

  exec('php', ['scripts/prepare-page-screenshot-state.php', page.prepareState], {
    env: {
      APK_SCREENSHOT_USER_EMAIL: userEmail,
      APK_SCREENSHOT_ADMIN_EMAIL: adminEmail,
    },
    stdio: 'ignore',
  });
}

function userPages() {
  const only = (process.env.IOS_SCREENSHOT_ONLY || '')
    .split(',')
    .map((value) => value.trim())
    .filter(Boolean);

  const pages = buildPageScreenshotCatalog({
    adminEmail,
    appUrl,
    loginToken,
    userEmail,
  }).filter((page) => page.slug === 'login' || page.slug.startsWith('user-'));

  return only.length > 0 ? pages.filter((page) => only.includes(page.slug)) : pages;
}

async function launchBrowser() {
  try {
    return {
      browser: await webkit.launch(),
      engine: 'webkit',
    };
  } catch (error) {
    console.warn(`WebKit launch failed, falling back to Chromium: ${error.message}`);

    return {
      browser: await chromium.launch(),
      engine: 'chromium-fallback',
    };
  }
}

async function waitForPageReady(page) {
  await page.waitForLoadState('domcontentloaded');
  await page.waitForLoadState('networkidle').catch(() => {});
  await page.locator('body').waitFor({ state: 'visible' });
  if (emulateNativeApp) {
    await page.evaluate(() => {
      document.body?.classList.add('is-native-platform', 'platform-ios');
      document.documentElement?.classList.add('is-native-platform', 'platform-ios');
      document.documentElement?.style.setProperty('--safe-area-inset-top', '47px');
      document.documentElement?.style.setProperty('--safe-area-inset-bottom', '34px');
    });
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await delay(settleMs);
}

async function capturePage(page, entry, index, engine) {
  preparePageState(entry);

  if (entry.clearCookies) {
    await page.context().clearCookies();
  }

  await page.goto(entry.url, { waitUntil: 'domcontentloaded' });

  if (entry.expectedPath) {
    await page.waitForURL((url) => url.pathname === entry.expectedPath, { timeout: 30000 });
  }

  await waitForPageReady(page);

  if (entry.readyExpression) {
    try {
      await page.waitForFunction(entry.readyExpression, null, { timeout: 15000 });
      await delay(settleMs);
    } catch (error) {
      console.warn(`Readiness check timed out for ${entry.slug}; capturing current rendered state. ${error.message}`);
    }
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
      mode: engine === 'webkit' ? 'ios-webkit-viewport' : 'ios-native-emulated-viewport',
      device: deviceName,
      nativeAppEmulated: emulateNativeApp,
      viewport: screenshotProfile.viewport,
      deviceScaleFactor: screenshotProfile.deviceScaleFactor,
    },
  };
}

process.on('exit', cleanup);
process.on('SIGINT', () => {
  cleanup();
  process.exit(130);
});

await ensureLaravelServer();
prepareOutputDir();
prepareDemoData();

const { browser, engine } = await launchBrowser();
const context = await browser.newContext({
  ...screenshotProfile,
  locale: 'id-ID',
  timezoneId: 'Asia/Jakarta',
  permissions: engine === 'webkit' ? ['geolocation'] : ['camera', 'geolocation'],
  geolocation: { latitude: -6.2, longitude: 106.816666 },
  colorScheme: process.env.IOS_PAGE_COLOR_SCHEME || 'light',
});
if (emulateNativeApp) {
  await context.addInitScript(() => {
    window.CapacitorCustomPlatform = { name: 'ios' };
    window.webkit = window.webkit || { messageHandlers: {} };
    window.webkit.messageHandlers = window.webkit.messageHandlers || {};
    window.webkit.messageHandlers.bridge = window.webkit.messageHandlers.bridge || { postMessage() {} };
  });
}
const page = await context.newPage();
const captured = [];

for (const [index, entry] of userPages().entries()) {
  const result = await capturePage(page, entry, index, engine);
  captured.push(result);
  console.log(JSON.stringify(result));
}

const manifestPath = path.join(outputDir, 'manifest.json');
fs.writeFileSync(manifestPath, `${JSON.stringify({
  generated_at: new Date().toISOString(),
  app_url: appUrl,
  browser_engine: engine,
  device: deviceName,
  native_app_emulated: emulateNativeApp,
  viewport: screenshotProfile.viewport,
  screenshots: captured,
}, null, 2)}\n`);

await browser.close();
cleanup();

console.log(JSON.stringify({
  ok: true,
  count: captured.length,
  output_dir: outputDir,
  manifest: manifestPath,
}));

process.exit(0);
