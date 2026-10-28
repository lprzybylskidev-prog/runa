import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { readFile } from 'node:fs/promises';
import { build } from '../../../../src/runa/Testing/frontend-runtime.mjs';
import { fileURLToPath } from 'node:url';
process.chdir(fileURLToPath(new URL('../../../../src', import.meta.url)));

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
assert(
  !modules.some((id) => /\/resources\/review\/|\/tests\/|\/tools\//.test(id)),
);
assert(!existsSync('resources/review'));
assert(!existsSync('vite.review.config.mjs'));
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
      new URL('../unit/fixtures/public-consumer.ts', import.meta.url),
    ),
    formats: ['es'],
    fileName: 'consumer',
  },
  rolldownOptions: { external: ['vue'] },
});
const consumerModules = consumer
  .filter((chunk) => chunk.type === 'chunk')
  .flatMap((chunk) =>
    Object.entries(chunk.modules)
      .filter(([, module]) => module.renderedLength > 0)
      .map(([id]) => id),
  );
assert(consumerModules.some((id) => id.endsWith('/AppButton.vue')));
assert(consumerModules.some((id) => id.endsWith('/AppTable.vue')));
assert(consumerModules.some((id) => id.endsWith('/AppConfirmDialog.vue')));
// Generic control composables (feedback, fields, tables, layers) are allowed.
// Shell/router/preference initialization remains forbidden.
assert(
  !consumerModules.some((id) =>
    /\/layouts\/|\/review\/|\/App\.vue|\/main\.ts|\/composables\/(?:useLocale|useTheme|usePreference|usePageResolver)/.test(
      id,
    ),
  ),
);
// Rolldown lists parsed, fully tree-shaken modules too. Check emitted code as well.
assert(
  !consumer.some(
    (chunk) =>
      chunk.type === 'chunk' &&
      /minimal-page|applyingPreferences|localStorage/.test(chunk.code),
  ),
);
const dockerfile = await readFile('../docker/php/Dockerfile', 'utf8');
assert(!dockerfile.includes('/build/public/ui-review/'));
const dockerignore = await readFile('../.dockerignore', 'utf8');
for (const path of [
  'tests/backend/runa',
  'tools',
  '.runtime',
  'src/public/ui-review',
])
  assert(dockerignore.split('\n').includes(path));
console.info(
  'PASS: production graph excludes retired review and test harnesses; public consumer bundles controls without layouts, routes or preference initialization; Docker excludes test sources/tools/output and legacy review assets.',
);
