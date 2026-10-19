import { test, expect } from '@playwright/test';
for (const [device, width, height] of [
  ['desktop', 1440, 900],
  ['tablet', 1024, 768],
  ['mobile', 390, 844],
] as const) {
  for (const locale of ['pl', 'en'])
    for (const theme of ['light', 'dark']) {
      test(`tables ${device} ${locale} ${theme}`, async ({ page }, info) => {
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
          if (['warning', 'error'].includes(message.type()))
            errors.push(message.text());
        });
        await page.setViewportSize({ width, height });
        await page.addInitScript(
          ({ locale, theme }) => {
            localStorage.setItem('runa.locale', locale);
            localStorage.setItem('runa.theme', theme);
          },
          { locale, theme },
        );
        await page.goto('/__review/ui?category=data');
        await expect(page.locator('table')).toHaveCount(2);
        const local = page.locator('table').first();
        await expect(local.locator('tbody tr')).toHaveCount(5);
        await local.getByRole('button', { name: /Nazwa|Name/ }).click();
        await expect(local.locator('th[aria-sort="ascending"]')).toHaveCount(1);
        await local.getByRole('checkbox').first().check();
        await expect(local.getByRole('checkbox').first()).toBeChecked();
        expect(
          await page.evaluate(
            () => document.documentElement.scrollWidth <= window.innerWidth,
          ),
        ).toBe(true);
        await page.screenshot({
          path: `artifacts/playwright/tables-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        expect(errors).toEqual([]);
        await info.attach('table preview', {
          path: `artifacts/playwright/tables-${device}-${locale}-${theme}.png`,
          contentType: 'image/png',
        });
      });
    }
}
