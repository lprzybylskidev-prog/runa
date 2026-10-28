import { testAliases } from '../../../src/runa/Testing/frontend-runtime.mjs';
import { createRequire } from 'node:module';
const require = createRequire(
  new URL('../../../src/package.json', import.meta.url),
);
const { defineConfig } = await import(require.resolve('vitest/config'));
const { default: vue } = await import(require.resolve('@vitejs/plugin-vue'));
import { moduleComponentsPlugin } from '../../../src/runa/frontend/module-components.mjs';
import { fileURLToPath } from 'node:url';
const { playwright } = await import(
  require.resolve('@vitest/browser-playwright')
);

export default defineConfig({
  cacheDir: '../.runtime/frontend/vitest/runa',
  plugins: [
    vue(),
    moduleComponentsPlugin(
      fileURLToPath(new URL('../../../src', import.meta.url)),
    ),
  ],
  resolve: {
    alias: [
      ...testAliases,
      {
        find: '@/runa',
        replacement: fileURLToPath(
          new URL('../../../src/runa/resources/js/runa', import.meta.url),
        ),
      },
      {
        find: '@runa',
        replacement: fileURLToPath(
          new URL('../../../src/runa/resources/js', import.meta.url),
        ),
      },
      {
        find: '@',
        replacement: fileURLToPath(
          new URL('../../../src/app/resources/js', import.meta.url),
        ),
      },
    ],
  },
  server: {
    fs: { allow: [fileURLToPath(new URL('../../..', import.meta.url))] },
  },
  test: {
    include: ['../tests/frontend/runa/unit/**/*.test.ts'],
    attachmentsDir: '../.runtime/artifacts/playwright/runa/vitest',
    restoreMocks: true,
    browser: {
      enabled: true,
      headless: true,
      provider: playwright(),
      instances: [{ browser: 'chromium' }],
    },
  },
});
