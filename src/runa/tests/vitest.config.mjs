import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { playwright } from '@vitest/browser-playwright';

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@/runa': fileURLToPath(
        new URL('../../runa/resources/js/runa', import.meta.url),
      ),
      '@runa': fileURLToPath(
        new URL('../../runa/resources/js', import.meta.url),
      ),
      '@': fileURLToPath(new URL('../../app/resources/js', import.meta.url)),
    },
  },
  server: {
    fs: { allow: [fileURLToPath(new URL('../../../', import.meta.url))] },
  },
  test: {
    include: ['runa/tests/frontend/**/*.test.ts'],
    attachmentsDir: '../artifacts/playwright/runa/vitest',
    restoreMocks: true,
    browser: {
      enabled: true,
      headless: true,
      provider: playwright(),
      instances: [{ browser: 'chromium' }],
    },
  },
});
