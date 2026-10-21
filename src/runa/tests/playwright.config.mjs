import { defineConfig, devices } from '@playwright/test';
import { fileURLToPath } from 'node:url';

export default defineConfig({
  testDir: './e2e',
  outputDir: '../../../artifacts/playwright/runa/results',
  fullyParallel: false,
  workers: 1,
  forbidOnly: true,
  retries: 0,
  webServer: {
    cwd: fileURLToPath(new URL('../../', import.meta.url)),
    command: 'node runa/tools/testing/browser-harness.mjs',
    url: 'http://127.0.0.1:4178/__fixtures/ready',
    reuseExistingServer: false,
    timeout: 120000,
  },
  use: { baseURL: 'http://127.0.0.1:4178', trace: 'retain-on-failure' },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'mobile-chromium', use: { ...devices['Pixel 7'] } },
  ],
});
