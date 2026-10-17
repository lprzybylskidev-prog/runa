import { expect, test } from '@playwright/test';

test('critical: API readiness and compact language/theme preferences', async ({
  page,
  request,
}) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (message.type() === 'error' || message.type() === 'warning')
      errors.push(message.text());
  });
  expect((await request.get('/api/health')).ok()).toBe(true);
  await page.goto('/');
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  const preferences = page.getByRole('group', {
    name: 'Ustawienia wyświetlania',
  });
  await expect(preferences.getByRole('button')).toHaveCount(2);
  await preferences.getByRole('button', { name: /^Język:/ }).click();
  await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  const theme = page.getByRole('button', { name: /^Theme:/ });
  await expect(theme).toHaveAccessibleName('Theme: System. Switch to: Light');
  await theme.click();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
  await expect(theme).toHaveAccessibleName('Theme: Light. Switch to: Dark');
  await theme.click();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  const restored = page.getByRole('button', { name: /^Theme:/ });
  await restored.focus();
  await expect(
    page.getByRole('tooltip').filter({ hasText: 'Theme:' }),
  ).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(
    page.getByRole('tooltip').filter({ hasText: 'Theme:' }),
  ).toBeHidden();
  await page.keyboard.press('Enter');
  await expect(restored).toHaveAccessibleName(
    'Theme: System. Switch to: Light',
  );
  await page.getByRole('button', { name: /^Language:/ }).click();
  await expect(page.locator('html')).toHaveAttribute('lang', 'pl');
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= window.innerWidth,
    ),
  ).toBe(true);
  expect(errors).toEqual([]);
});
