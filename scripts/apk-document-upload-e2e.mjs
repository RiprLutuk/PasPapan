import crypto from 'node:crypto';
import http from 'node:http';
import net from 'node:net';

const appUrl = process.env.APP_URL || 'http://127.0.0.1:8000';
const cdpPort = Number(process.env.CDP_PORT || 9222);
const email = process.env.E2E_EMAIL || 'apk.e2e.user@paspapan.test';
const password = process.env.E2E_PASSWORD || '12345678';
const loginToken = process.env.E2E_LOGIN_TOKEN || 'local-apk-e2e';
const filePath = process.env.E2E_FILE_PATH || '/tmp/paspapan-apk-document-upload-e2e.pdf';
const requestId = process.env.E2E_REQUEST_ID;

if (!requestId) {
  throw new Error('E2E_REQUEST_ID is required.');
}

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

      await new Promise((resolve) => setTimeout(resolve, 500));
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

function getJson(path) {
  return new Promise((resolve, reject) => {
    http.get({ host: '127.0.0.1', port: cdpPort, path }, (response) => {
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

function jsString(value) {
  return JSON.stringify(value);
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
await cdp.send('Runtime.enable');
await cdp.send('DOM.enable');

const e2eLoginUrl = `${appUrl}/__e2e-login?token=${encodeURIComponent(loginToken)}&email=${encodeURIComponent(email)}&to=${encodeURIComponent('/document-requests')}`;
await cdp.send('Page.navigate', { url: e2eLoginUrl });
await cdp.waitFor("location.pathname === '/document-requests' && document.body.innerText.length > 0", 'post-login document requests navigation');
await cdp.send('Page.navigate', { url: `${appUrl}/document-requests` });
const uploadActionSelector = `[data-e2e='document-upload-open'][data-request-id='${requestId}']`;
await cdp.waitFor(`document.querySelector(${jsString(uploadActionSelector)})`, 'document upload action');

await cdp.evaluate(`(() => {
  document.querySelector(${jsString(uploadActionSelector)}).click();
})()`);

await cdp.waitFor("document.querySelector('#document-upload-file') && document.querySelector('[data-e2e=\"document-upload-submit\"]')", 'document upload modal');

const uploadResult = await cdp.evaluate(`(async () => {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const body = new FormData();
  const file = new File(
    ['%PDF-1.4\\n% PasPapan APK document upload E2E fixture\\n1 0 obj <<>> endobj\\ntrailer <<>>\\n%%EOF\\n'],
    'paspapan-apk-document-upload-e2e.pdf',
    { type: 'application/pdf' },
  );

  body.append('token', ${jsString(loginToken)});
  body.append('request_id', ${jsString(requestId)});
  body.append('attachment', file);

  const response = await fetch('/__e2e-document-upload', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'X-CSRF-TOKEN': csrf,
    },
    body,
  });

  const payload = await response.json().catch(async () => ({ text: await response.text() }));

  return {
    ok: response.ok,
    status: response.status,
    payload,
  };
})()`);

if (!uploadResult?.ok) {
  throw new Error(`Document upload failed: ${JSON.stringify(uploadResult)}`);
}

await cdp.send('Page.navigate', { url: `${appUrl}/document-requests` });

await cdp.waitFor(
  "document.body.innerText.includes('Processing Upload') || document.body.innerText.includes('Uploaded') || document.body.innerText.includes('Memproses Upload') || document.body.innerText.includes('Terupload')",
  'document upload status update',
  60000,
);

console.log(JSON.stringify({
  ok: true,
  request_id: requestId,
  url: await cdp.evaluate('location.href'),
}));

cdp.socket.end();
process.exit(0);
