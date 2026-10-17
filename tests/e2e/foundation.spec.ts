import { expect, test } from '@playwright/test';

test('critical: API readiness, language/theme preferences and error recovery', async ({
  page,
}) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (message.type() === 'error' || message.type() === 'warning')
      errors.push(message.text());
  });
  await page.goto('/');
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  await expect(page.locator('html')).toHaveAttribute('lang', 'pl');
  await expect(page.getByRole('status')).toContainText('Połączenie działa');
  await page.getByLabel('Język').selectOption('en');
  await page.getByLabel('Theme').selectOption('dark');
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBe(true);
  await page.route('**/api/health', (route) =>
    route.fulfill({
      status: 503,
      contentType: 'application/json',
      body: '{"message":"Unavailable"}',
    }),
  );
  await page.reload();
  await expect(
    page.getByText('Unable to connect', { exact: true }),
  ).toBeVisible();
  await page.unroute('**/api/health');
  await page.getByRole('button', { name: 'Try again' }).click();
  await expect(page.getByRole('status')).toContainText('Connection is working');
  // The deliberate 503 is reported by the browser; all other errors remain failures.
  expect(errors.filter((message) => !message.includes('503'))).toEqual([]);
});
