import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { build } from 'vite';
import { fileURLToPath } from 'node:url';

async function bundle(options = {}) {
  const result = await build({
    configFile: 'vite.config.mjs',
    logLevel: 'error',
    build: { write: false, ...options },
  });
  const results = Array.isArray(result) ? result : [result];
  return results.flatMap((item) => {
    assert('output' in item);
    return item.output;
  });
}

const production = await bundle();
const modules = production
  .filter((chunk) => chunk.type === 'chunk')
  .flatMap((chunk) => Object.keys(chunk.modules));
assert(!modules.some((id) => id.includes('/resources/review/')));
assert(
  !production.some(
    (chunk) =>
      chunk.type === 'chunk' &&
      /Catalog skeleton|__review|ReviewCatalog/.test(chunk.code),
  ),
);
const consumer = await bundle({
  lib: {
    entry: fileURLToPath(
      new URL(
        '../../tests/frontend/fixtures/public-consumer.ts',
        import.meta.url,
      ),
    ),
    formats: ['es'],
    fileName: 'consumer',
  },
  rolldownOptions: { external: ['vue'] },
});
const consumerModules = consumer
  .filter((chunk) => chunk.type === 'chunk')
  .flatMap((chunk) => Object.keys(chunk.modules));
assert(consumerModules.some((id) => id.endsWith('/AppButton.vue')));
assert(
  !consumerModules.some((id) =>
    /\/layouts\/|\/review\/|\/App\.vue|\/main\.ts|\/composables\//.test(id),
  ),
);
const dockerfile = await readFile('docker/php/Dockerfile', 'utf8');
assert(!dockerfile.includes('/build/public/ui-review/'));
console.info(
  'PASS: production graph excludes review; public consumer bundles controls without layouts, routes or preference initialization; Docker copies only public/build.',
);
