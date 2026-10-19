import { test, expect } from '@playwright/test';
test('downloads real bounded files, selects across pages and recovers partial operations', async ({
  page,
}) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (message.type() === 'error') errors.push(message.text());
  });
  await page.addInitScript(() => {
    localStorage.setItem('runa.locale', 'en');
  });
  await page.goto('/__review/ui?category=data');
  await expect(page.locator('table')).toHaveCount(2);
  for (const format of ['CSV', 'XLSX', 'PDF']) {
    const pending = page.waitForEvent('download');
    await page
      .getByRole('button', { name: format, exact: true })
      .first()
      .click();
    const download = await pending;
    const path = `artifacts/playwright/exports/projects.${format.toLowerCase()}`;
    await download.saveAs(path);
    expect(await download.failure()).toBeNull();
    expect(download.suggestedFilename()).toBe(
      `projekty.${format.toLowerCase()}`,
    );
  }
  await page
    .getByRole('button', { name: 'Select all matching', exact: true })
    .first()
    .click();
  await expect(
    page.getByRole('status').filter({ hasText: 'Selected: 43' }).first(),
  ).toBeVisible();
  await page
    .locator('table')
    .first()
    .locator('tbody')
    .getByRole('checkbox')
    .first()
    .uncheck();
  await expect(
    page.getByRole('status').filter({ hasText: 'Selected: 42' }).first(),
  ).toBeVisible();
  await page.getByRole('button', { name: 'Next', exact: true }).first().click();
  await expect(
    page
      .locator('table')
      .first()
      .locator('tbody')
      .getByRole('checkbox')
      .first(),
  ).toBeChecked();
  await page
    .getByRole('button', { name: 'Previous', exact: true })
    .first()
    .click();
  await expect(
    page
      .locator('table')
      .first()
      .locator('tbody')
      .getByRole('checkbox')
      .first(),
  ).not.toBeChecked();
  await page
    .getByRole('textbox', { name: 'Search', exact: true })
    .first()
    .fill('Gdańsk');
  await expect(
    page
      .getByRole('status')
      .filter({ hasText: 'All-matching selection cleared' }),
  ).toBeVisible();
  await page.getByRole('checkbox', { name: 'Partial export result' }).check();
  await page.getByRole('button', { name: 'CSV', exact: true }).nth(1).click();
  await expect(
    page.getByText('Partially completed', { exact: true }),
  ).toBeVisible();
  const pending = page.waitForEvent('download');
  await page.getByRole('button', { name: 'Retry', exact: true }).click();
  await pending;
  await page.getByRole('button', { name: 'PDF', exact: true }).nth(1).click();
  await page.getByRole('button', { name: 'Cancel', exact: true }).click();
  await expect(page.getByText('Cancelled', { exact: true })).toBeVisible();
  await page
    .locator('table')
    .first()
    .locator('thead')
    .getByRole('checkbox')
    .check();
  await page
    .getByRole('button', { name: 'Test operation', exact: true })
    .first()
    .click();
  await expect(page.getByText('Succeeded: 5', { exact: true })).toBeVisible();
  expect(errors).toEqual([]);
});
test('opens an isolated print document with the complete filtered result', async ({
  page,
}) => {
  await page.addInitScript(() => {
    localStorage.setItem('runa.locale', 'en');
    window.print = () => {
      document.documentElement.dataset.printed = 'yes';
    };
  });
  await page.goto('/__review/ui?category=data');
  await page
    .getByRole('button', { name: 'Print', exact: true })
    .first()
    .click();
  const frame = page.frameLocator('iframe.runa-print-frame');
  await expect(frame.locator('html')).toHaveAttribute('data-printed', 'yes');
  await expect(frame.locator('tbody tr')).toHaveCount(43);
  await expect(frame.locator('thead th')).toHaveCount(3);
});

for (const [device, width, height] of [
  ['desktop', 1440, 900],
  ['tablet', 1024, 768],
  ['mobile', 390, 844],
] as const) {
  for (const locale of ['pl', 'en'])
    for (const theme of ['light', 'dark']) {
      test(`operation states ${device} ${locale} ${theme}`, async ({
        page,
      }) => {
        await page.setViewportSize({ width, height });
        await page.addInitScript(
          ({ locale, theme }) => {
            localStorage.setItem('runa.locale', locale);
            localStorage.setItem('runa.theme', theme);
          },
          { locale, theme },
        );
        await page.goto('/__review/ui?category=data');
        await page
          .getByRole('checkbox', {
            name:
              locale === 'pl'
                ? 'Częściowy wynik eksportu'
                : 'Partial export result',
          })
          .check();
        await page
          .getByRole('button', { name: 'CSV', exact: true })
          .nth(1)
          .click();
        await expect(
          page.getByText(
            locale === 'pl' ? 'Częściowo zakończono' : 'Partially completed',
            { exact: true },
          ),
        ).toBeVisible();
        expect(
          await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
          ),
        ).toBe(true);
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.screenshot({
          path: `artifacts/playwright/exports/states-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
      });
    }
}
