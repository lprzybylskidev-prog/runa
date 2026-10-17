import { defineConfig } from 'vitest/config';
import vue from '@vitejs/plugin-vue';
import { playwright } from '@vitest/browser-playwright';

export default defineConfig({
  plugins: [vue()],
  test: {
    include: ['tests/frontend/**/*.test.ts'],
    attachmentsDir: 'artifacts/playwright/vitest',
    restoreMocks: true,
    browser: {
      enabled: true,
      headless: true,
      provider: playwright(),
      instances: [{ browser: 'chromium' }],
    },
  },
});
