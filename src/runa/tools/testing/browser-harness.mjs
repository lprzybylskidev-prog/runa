// Isolated test process. No application routes, public output, catalogue or dev-server API.
import { Buffer } from 'node:buffer';
import { build, mergeConfig } from 'vite';
import { createServer } from 'node:http';
import { readFile, mkdir, access } from 'node:fs/promises';
import { resolve, extname, sep } from 'node:path';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import config from '../../../vite.config.mjs';

const output = resolve('../.runtime/browser-harness');
const upstream = process.env.RUNA_E2E_UPSTREAM ?? 'http://nginx-test:8080';
const port = 4178;
const root = resolve('runa/tests/frontend/fixtures/browser');
await access('public/runa-error/pages/503-pl-system.html'); // Build native error documents before browser tests.
await mkdir(output, { recursive: true });
for (const standalone of [false, true]) {
  await build(
    mergeConfig(config, {
      configFile: false,
      root,
      base: standalone ? '/__fixtures/standalone/' : '/__fixtures/',
      build: {
        outDir: standalone ? `${output}/standalone` : output,
        emptyOutDir: true,
        rolldownOptions: {
          input: resolve(root, standalone ? 'standalone.html' : 'index.html'),
        },
      },
    }),
  );
}
const execute = promisify(execFile);
const types = {
  '.html': 'text/html; charset=utf-8',
  '.css': 'text/css',
  '.js': 'text/javascript',
  '.woff2': 'font/woff2',
  '.png': 'image/png',
  '.txt': 'text/plain',
  '.ico': 'image/x-icon',
};
const server = createServer(async (request, response) => {
  try {
    if (!['GET', 'HEAD'].includes(request.method)) {
      response.writeHead(405).end();
      return;
    }
    const origin = `http://127.0.0.1:${port}`;
    const url = new URL(request.url, origin);
    if (url.origin !== origin) {
      response.writeHead(400).end();
      return;
    }
    if (url.pathname === '/__fixtures/ready') {
      response.end('ready');
      return;
    }
    if (
      url.pathname === '/__fixtures/email' ||
      url.pathname.startsWith('/__fixtures/errors/')
    ) {
      const { stdout } = await execute(
        'php',
        [
          'runa/tests/Fixtures/browser-document.php',
          url.href,
          request.headers.accept ?? 'text/html',
        ],
        { maxBuffer: 8 * 1024 * 1024 },
      );
      const document = JSON.parse(stdout);
      response.writeHead(document.status, document.headers);
      response.end(Buffer.from(document.body, 'base64'));
      return;
    }
    if (
      url.pathname.startsWith('/__fixtures/') &&
      url.pathname !== '/__fixtures/not-found'
    ) {
      let name = decodeURIComponent(url.pathname.slice('/__fixtures/'.length));
      if (
        name === 'ui' ||
        /^application\/(overview|projects|activity|archive|team)$/.test(name)
      )
        name = 'index.html';
      const file = resolve(output, name);
      if (!file.startsWith(output + sep)) {
        response.writeHead(404).end();
        return;
      }
      const content = await readFile(file);
      response.writeHead(200, {
        'Content-Type': types[extname(file)] ?? 'application/octet-stream',
        'Cache-Control': 'no-store',
      });
      response.end(content);
      return;
    }
    // Real welcome/API/native-404/assets continue through Nginx -> PHP-FPM.
    const target = new URL(upstream);
    target.pathname = url.pathname;
    target.search = url.search;
    const result = await globalThis.fetch(target, {
      headers: { accept: request.headers.accept ?? '*/*' },
      redirect: 'manual',
    });
    const headers = Object.fromEntries(result.headers);
    delete headers['content-encoding'];
    delete headers['content-length'];
    delete headers['transfer-encoding'];
    response.writeHead(result.status, headers);
    response.end(Buffer.from(await result.arrayBuffer()));
  } catch (error) {
    if (error.code === 'ENOENT') {
      response.writeHead(404).end();
      return;
    }
    console.error(error);
    response.writeHead(500).end('Browser fixture failed');
  }
});
server.listen(port, '127.0.0.1');
for (const signal of ['SIGTERM', 'SIGINT'])
  process.on(signal, () => server.close(() => process.exit(0)));
