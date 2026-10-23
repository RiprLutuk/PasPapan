import crypto from 'node:crypto';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import http from 'node:http';
import net from 'node:net';
import path from 'node:path';
import { buildPageScreenshotCatalog } from './page-screenshot-catalog.mjs';

const appUrl = process.env.APP_URL || 'http://127.0.0.1:8000';
const cdpPort = Number(process.env.CDP_PORT || 9222);
const outputDir = process.env.SCREENSHOT_DIR || 'screenshots/apk-pages';
const loginToken = process.env.E2E_LOGIN_TOKEN;

if (!loginToken) {
  throw new Error('E2E_LOGIN_TOKEN is required.');
}
const userEmail = process.env.APK_SCREENSHOT_USER_EMAIL || 'apk.demo.user@paspapan.test';
const adminEmail = process.env.APK_SCREENSHOT_ADMIN_EMAIL || 'apk.demo.superadmin@paspapan.test';
const settleMs = Number(process.env.APK_SCREENSHOT_SETTLE_MS || 1800);

class CdpSocket {
  constructor(webSocketUrl) {
    this.url = new URL(webSocketUrl);
    this.nextId = 1;
    this.pending = new Map();
    this.buffer = Buffer.alloc(0);
  }

  connect() {
    return new Promise((resolve, reject) => {
      const key = crypto.randomBytes(16).toString('base64');
      this.socket = net.connect(Number(this.url.port), this.url.hostname, () => {
        this.socket.write([
          `GET ${this.url.pathname}${this.url.search} HTTP/1.1`,
          `Host: ${this.url.host}`,
          'Upgrade: websocket',
          'Connection: Upgrade',
          `Sec-WebSocket-Key: ${key}`,
          'Sec-WebSocket-Version: 13',
          '',
          '',
        ].join('\r\n'));
      });

      let handshake = Buffer.alloc(0);
      let upgraded = false;

      this.socket.on('data', (chunk) => {
        if (!upgraded) {
          handshake = Buffer.concat([handshake, chunk]);
          const marker = handshake.indexOf('\r\n\r\n');

          if (marker === -1) {
            return;
          }

          const header = handshake.subarray(0, marker).toString('utf8');

          if (!header.includes('101')) {
            reject(new Error(`WebSocket handshake failed: ${header}`));
            return;
          }

          upgraded = true;
          const rest = handshake.subarray(marker + 4);

          if (rest.length > 0) {
            this.readFrames(rest);
          }

          resolve();
          return;
        }

        this.readFrames(chunk);
      });

      this.socket.on('error', reject);
    });
  }

  send(method, params = {}) {
    const id = this.nextId++;
    const payload = JSON.stringify({ id, method, params });

    this.socket.write(this.encodeFrame(payload));

    return new Promise((resolve, reject) => {
      this.pending.set(id, { resolve, reject });
    });
  }

  async evaluate(expression) {
    const result = await this.send('Runtime.evaluate', {
      expression,
      awaitPromise: true,
      returnByValue: true,
    });

    if (result.exceptionDetails) {
      throw new Error(result.exceptionDetails.text || 'Runtime evaluation failed.');
    }

    return result.result?.value;
  }

  async waitFor(expression, description, timeoutMs = 30000) {
    const deadline = Date.now() + timeoutMs;
    let lastValue = null;

    while (Date.now() < deadline) {
      lastValue = await this.evaluate(`(() => {
        try {
          return Boolean(${expression});
        } catch (error) {
          return false;
        }
      })()`);

      if (lastValue) {
        return;
      }

      await delay(500);
    }

    throw new Error(`Timed out waiting for ${description}. Last value: ${String(lastValue)}`);
  }

  readFrames(chunk) {
    this.buffer = Buffer.concat([this.buffer, chunk]);

    while (this.buffer.length >= 2) {
      const first = this.buffer[0];
      const second = this.buffer[1];
      const opcode = first & 0x0f;
      const masked = (second & 0x80) !== 0;
      let length = second & 0x7f;
      let offset = 2;

      if (length === 126) {
        if (this.buffer.length < offset + 2) return;
        length = this.buffer.readUInt16BE(offset);
        offset += 2;
      } else if (length === 127) {
        if (this.buffer.length < offset + 8) return;
        length = Number(this.buffer.readBigUInt64BE(offset));
        offset += 8;
      }

      const maskLength = masked ? 4 : 0;
      if (this.buffer.length < offset + maskLength + length) return;

      let payload = this.buffer.subarray(offset + maskLength, offset + maskLength + length);

      if (masked) {
        const mask = this.buffer.subarray(offset, offset + 4);
        payload = Buffer.from(payload.map((byte, index) => byte ^ mask[index % 4]));
      }

      this.buffer = this.buffer.subarray(offset + maskLength + length);

      if (opcode === 1) {
        this.handleMessage(payload.toString('utf8'));
      } else if (opcode === 8) {
        this.socket.end();
      }
    }
  }

  handleMessage(message) {
    const data = JSON.parse(message);

    if (!data.id || !this.pending.has(data.id)) {
      return;
    }

    const pending = this.pending.get(data.id);
    this.pending.delete(data.id);

    if (data.error) {
      pending.reject(new Error(data.error.message || JSON.stringify(data.error)));
      return;
    }

    pending.resolve(data.result);
  }

  encodeFrame(text) {
    const payload = Buffer.from(text);
    const mask = crypto.randomBytes(4);
    const length = payload.length;
    let header;

    if (length < 126) {
      header = Buffer.from([0x81, 0x80 | length]);
    } else if (length < 65536) {
      header = Buffer.alloc(4);
      header[0] = 0x81;
      header[1] = 0x80 | 126;
      header.writeUInt16BE(length, 2);
    } else {
      header = Buffer.alloc(10);
      header[0] = 0x81;
      header[1] = 0x80 | 127;
      header.writeBigUInt64BE(BigInt(length), 2);
    }

    const masked = Buffer.from(payload.map((byte, index) => byte ^ mask[index % 4]));

    return Buffer.concat([header, mask, masked]);
  }
}

function getJson(requestPath) {
  return new Promise((resolve, reject) => {
    http.get({ host: '127.0.0.1', port: cdpPort, path: requestPath }, (response) => {
      let body = '';
      response.setEncoding('utf8');
      response.on('data', (chunk) => {
        body += chunk;
      });
      response.on('end', () => {
        try {
          resolve(JSON.parse(body));
        } catch (error) {
          reject(error);
        }
      });
    }).on('error', reject);
  });
}

function delay(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

function captureDeviceScreenshot(targetPath) {
  fs.mkdirSync(path.dirname(targetPath), { recursive: true });
  execFileSync('adb', ['shell', 'input', 'keyevent', '224'], { stdio: 'ignore' });
  execFileSync('adb', ['shell', 'wm', 'dismiss-keyguard'], { stdio: 'ignore' });
  const image = execFileSync('adb', ['exec-out', 'screencap', '-p'], {
    encoding: 'buffer',
    maxBuffer: 20 * 1024 * 1024,
  });

  fs.writeFileSync(targetPath, image);

  return { mode: 'device-viewport' };
}

async function waitForPageReady(cdp) {
  await cdp.waitFor(
    "document.readyState === 'complete' || document.readyState === 'interactive'",
    'document readiness',
  );
  await cdp.waitFor('document.body && document.body.innerText.length > 0', 'non-empty body');
  await cdp.evaluate('window.scrollTo(0, 0)');
  await delay(settleMs);
}

async function capturePage(cdp, page, index) {
  if (page.prepareState) {
    execFileSync('php', ['scripts/prepare-page-screenshot-state.php', page.prepareState], {
      env: {
        ...process.env,
        APK_SCREENSHOT_USER_EMAIL: userEmail,
        APK_SCREENSHOT_ADMIN_EMAIL: adminEmail,
      },
      stdio: ['ignore', 'pipe', 'inherit'],
    });
  }

  if (page.clearCookies) {
    await cdp.send('Network.clearBrowserCookies');
    await delay(500);
  }

  await cdp.send('Page.navigate', { url: page.url });

  if (page.expectedPath) {
    await cdp.waitFor(`location.pathname === ${JSON.stringify(page.expectedPath)}`, page.label);
  }

  await waitForPageReady(cdp);

  if (page.readyExpression) {
    try {
      await cdp.waitFor(page.readyExpression, `${page.label} ready state`);
      await delay(settleMs);
    } catch (error) {
      console.warn(`Readiness check timed out for ${page.slug}; capturing current rendered state. ${error.message}`);
    }
  }

  if (page.afterNavigate) {
    await cdp.evaluate(page.afterNavigate);
    await delay(settleMs);
  }

  const filename = `${String(index + 1).padStart(2, '0')}-${page.slug}.png`;
  const targetPath = path.join(outputDir, filename);
  const screenshot = captureDeviceScreenshot(targetPath);

  const currentUrl = await cdp.evaluate('location.href');
  const title = await cdp.evaluate('document.title || ""');

  return {
    label: page.label,
    slug: page.slug,
    path: targetPath,
    url: currentUrl,
    title,
    screenshot,
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

const targets = await getJson('/json');
const target = targets.find((item) => item.webSocketDebuggerUrl && item.type === 'page')
  || targets.find((item) => item.webSocketDebuggerUrl);

if (!target) {
  throw new Error('No debuggable WebView page target found.');
}

const cdp = new CdpSocket(target.webSocketDebuggerUrl);
await cdp.connect();
await cdp.send('Page.enable');
await cdp.send('Network.enable');
await cdp.send('Runtime.enable');
await cdp.send('DOM.enable');

const captured = [];

for (const [index, page] of pages.entries()) {
  const result = await capturePage(cdp, page, index);
  captured.push(result);
  console.log(JSON.stringify(result));
}

const manifestPath = path.join(outputDir, 'manifest.json');
fs.writeFileSync(manifestPath, `${JSON.stringify({
  generated_at: new Date().toISOString(),
  app_url: appUrl,
  screenshots: captured,
}, null, 2)}\n`);

console.log(JSON.stringify({
  ok: true,
  count: captured.length,
  output_dir: outputDir,
  manifest: manifestPath,
}));

cdp.socket.end();
process.exit(0);
