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
        await page.goto('/__fixtures/ui?category=data');
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
          path: `../artifacts/playwright/tables-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        expect(errors).toEqual([]);
        await info.attach('table preview', {
          path: `../artifacts/playwright/tables-${device}-${locale}-${theme}.png`,
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
  await page.goto('/__fixtures/ui?category=data');
  await expect(page.locator('table')).toHaveCount(2);
  await page
    .getByRole('button', { name: 'Columns and views', exact: true })
    .first()
    .click();
  let dialog = page.getByRole('dialog');
  await expect(dialog.getByRole('slider')).toHaveCount(0);
  await dialog.getByRole('checkbox', { name: 'City', exact: true }).uncheck();
  await dialog
    .getByRole('button', { name: 'Move up: Documents', exact: true })
    .click();
  await expect(dialog.getByLabel('Width: Name', { exact: true })).toHaveValue(
    /^\d+$/,
  );
  await dialog.getByLabel('Width: Name', { exact: true }).fill('');
  await dialog
    .getByLabel('Width: Name', { exact: true })
    .pressSequentially('240');
  await expect(dialog.getByLabel('Width: Name', { exact: true })).toHaveValue(
    '240',
  );
  // Retyping passes through an invalid width; let its exiting feedback leave before measuring the drag path.
  await expect(
    dialog.getByText('Enter 80–800 px.', { exact: true }),
  ).toHaveCount(0);
  const source = await dialog.locator('[draggable=true]').nth(2).boundingBox();
  const target = await dialog.locator('li').first().boundingBox();
  if (!source || !target)
    throw new Error('Column drag controls must be visible');
  // A real pointer path supplies dragover after entering the drop target.
  await page.mouse.move(
    source.x + source.width / 2,
    source.y + source.height / 2,
  );
  await page.mouse.down();
  await page.mouse.move(
    source.x + source.width / 2,
    source.y + source.height / 2 - 12,
    { steps: 5 },
  );
  await page.mouse.move(target.x + 20, target.y + 20, { steps: 10 });
  await page.mouse.move(target.x + 22, target.y + 22, { steps: 3 });
  await page.mouse.up();
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
    page.getByRole('button', { name: 'Available views', exact: true }).first(),
  ).toContainText('Default view');
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

test('uses compact controls, searches across columns and lists the default first', async ({
  page,
}) => {
  await page.addInitScript(() => {
    localStorage.setItem('runa.locale', 'en');
  });
  await page.goto('/__fixtures/ui?category=data');
  await expect(page.locator('table')).toHaveCount(2);
  const views = page
    .getByRole('button', { name: 'Available views', exact: true })
    .first();
  expect(await views.evaluate((e) => e.getBoundingClientRect().height)).toBe(
    36,
  );
  const search = page
    .getByRole('textbox', { name: 'Search', exact: true })
    .first();
  expect(await search.evaluate((e) => e.getBoundingClientRect().height)).toBe(
    36,
  );
  await views.click();
  await expect(page.getByRole('option').first()).toContainText('Default view');
  await page.keyboard.press('Escape');
  await search.fill('Gdańsk');
  await expect(page.locator('table').first().locator('tbody')).toContainText(
    'Gdańsk',
  );
  await expect(
    page.locator('table').first().locator('tbody'),
  ).not.toContainText('Warszawa');
  await search.fill('not-present-at-all');
  await expect(page.getByText('No results', { exact: true })).toBeVisible();
  await search.fill('Orion 3');
  await expect(page.locator('table').first().locator('tbody')).toContainText(
    'Orion 3',
  );
});
