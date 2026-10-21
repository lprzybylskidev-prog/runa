import { defineConfig, devices } from '@playwright/test';

// The testing Compose profile serves the real application through Nginx/PHP-FPM.
// Start it on the host before running from an attached development container.
const baseURL = process.env.RUNA_E2E_BASE_URL ?? 'http://nginx-test:8080';
if (!['http:', 'https:'].includes(new URL(baseURL).protocol)) {
  throw new Error(
    'RUNA_E2E_BASE_URL must be an HTTP(S) application test endpoint.',
  );
}

export default defineConfig({
  testDir: './app/tests/e2e',
  outputDir: '../artifacts/playwright/application/results',
  reporter: [
    ['list'],
    [
      'html',
      {
        outputFolder: '../artifacts/playwright/application/report',
        open: 'never',
      },
    ],
  ],
  fullyParallel: false,
  workers: 1,
  forbidOnly: true,
  retries: 0,
  use: { baseURL, trace: 'retain-on-failure', screenshot: 'only-on-failure' },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    { name: 'firefox', use: { ...devices['Desktop Firefox'] } },
    { name: 'mobile-chromium', use: { ...devices['Pixel 7'] } },
  ],
});
