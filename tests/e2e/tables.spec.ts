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

test('personalizes columns and manages a persisted default view', async ({
  page,
}) => {
  await page.addInitScript(() => {
    localStorage.setItem('runa.locale', 'en');
  });
  await page.goto('/__review/ui?category=data');
  await expect(page.locator('table')).toHaveCount(2);
  await page
    .getByRole('button', { name: 'Columns and views', exact: true })
    .first()
    .click();
  let dialog = page.getByRole('dialog');
  await dialog.getByRole('checkbox', { name: 'City', exact: true }).uncheck();
  await dialog
    .getByRole('button', { name: 'Move up: Documents', exact: true })
    .click();
  await dialog.getByLabel('Width: Name', { exact: true }).fill('240');
  const slider = dialog.getByRole('slider', {
    name: 'Resize: Name',
    exact: true,
  });
  await slider.focus();
  await page.keyboard.press('ArrowRight');
  await expect(slider).toHaveValue('241');
  await dialog
    .locator('[draggable=true]')
    .nth(2)
    .dragTo(dialog.locator('li').first());
  await expect(dialog.locator('li').first()).toContainText('City');
  await dialog.getByLabel('View name', { exact: true }).fill('Compact');
  await dialog
    .getByRole('button', { name: 'Save as new', exact: true })
    .click();
  await dialog
    .getByRole('button', { name: 'Set default', exact: true })
    .click();
  await dialog
    .getByRole('button', { name: 'Close dialog', exact: true })
    .click();
  await expect(page.locator('table').first().locator('th')).toHaveCount(4);
  await page.reload();
  await expect(
    page.getByRole('button', { name: 'Saved view', exact: true }).first(),
  ).toContainText('Compact');
  await expect(
    page.locator('table').first().locator('thead'),
  ).not.toContainText('City');
  await page
    .getByRole('button', { name: 'Columns and views', exact: true })
    .first()
    .click();
  dialog = page.getByRole('dialog');
  await dialog.getByLabel('View name', { exact: true }).fill('Renamed');
  await dialog.getByRole('button', { name: 'Rename', exact: true }).click();
  await expect(dialog).toContainText('Renamed');
  await dialog
    .getByRole('button', { name: 'Reset columns', exact: true })
    .click();
  await dialog
    .getByRole('button', { name: 'Save changes', exact: true })
    .click();
  await dialog
    .getByRole('button', { name: 'Delete view', exact: true })
    .click();
  await expect(
    dialog.getByRole('button', { name: 'Clear default', exact: true }),
  ).toBeDisabled();
  await dialog
    .getByRole('button', { name: 'Reset current view', exact: true })
    .click();
  await dialog
    .getByRole('button', { name: 'Close dialog', exact: true })
    .click();
  await expect(page.locator('table').first().locator('thead')).toContainText(
    'City',
  );
});
