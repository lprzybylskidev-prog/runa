import { testAliases } from './runa/Testing/frontend-runtime.mjs';
import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { playwright } from '@vitest/browser-playwright';

export default defineConfig({
  cacheDir: '../.runtime/frontend/vitest/application',
  plugins: [vue()],
  resolve: {
    alias: [
      ...testAliases,
      {
        find: '@/runa',
        replacement: fileURLToPath(
          new URL('./runa/resources/js/runa', import.meta.url),
        ),
      },
      {
        find: '@runa',
        replacement: fileURLToPath(
          new URL('./runa/resources/js', import.meta.url),
        ),
      },
      {
        find: '@',
        replacement: fileURLToPath(
          new URL('./app/resources/js', import.meta.url),
        ),
      },
    ],
  },
  server: { fs: { allow: [fileURLToPath(new URL('../', import.meta.url))] } },
  test: {
    include: ['../tests/frontend/app/unit/**/*.test.ts'],
    attachmentsDir: '../.runtime/artifacts/playwright/application/vitest',
    restoreMocks: true,
    browser: {
      enabled: true,
      headless: true,
      provider: playwright(),
      instances: [{ browser: 'chromium' }],
    },
  },
});
