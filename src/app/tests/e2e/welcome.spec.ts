import { expect, test } from '@playwright/test';

test('application welcome page @critical', async ({ page }) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  const response = await page.goto('/');
  expect(response?.status()).toBe(200);
  await expect(page.locator('#app')).not.toBeEmpty();
  await expect(page.locator('body')).toBeVisible();
  expect(errors).toEqual([]);
});
