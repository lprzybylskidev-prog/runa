import { recordBrowserProblems } from '../../../../src/runa/Testing/browserProblems';
import { test, expect } from '@runa/testing/playwright';
test('downloads real bounded files, selects across pages and recovers partial operations', async ({
  page,
}) => {
  const errors = recordBrowserProblems(page, { anonymousSession: true });
  await page.addInitScript(() => {
    localStorage.setItem('runa.locale', 'en');
  });
  await page.goto('/__fixtures/ui?category=data');
  await expect(page.locator('table')).toHaveCount(2);
  for (const format of ['CSV', 'XLSX', 'PDF']) {
    const pending = page.waitForEvent('download');
    await page
      .getByRole('button', { name: format, exact: true })
      .first()
      .click();
    const download = await pending;
    const path = `../.runtime/artifacts/playwright/exports/projects.${format.toLowerCase()}`;
    await download.saveAs(path);
    expect(await download.failure()).toBeNull();
    expect(download.suggestedFilename()).toBe(
      `projekty.${format.toLowerCase()}`,
    );
  }
  await page
    .locator('table')
    .first()
    .locator('thead')
    .getByRole('checkbox')
    .check();
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
  browserName,
}) => {
  await page.addInitScript(() => {
    localStorage.setItem('runa.locale', 'en');
    window.print = () => {
      document.documentElement.dataset.printed = 'yes';
    };
  });
  await page.goto('/__fixtures/ui?category=data');
  await page
    .getByRole('button', { name: 'Print', exact: true })
    .first()
    .click();
  const frame = page.frameLocator('iframe.runa-print-frame');
  await expect(frame.locator('html')).toHaveAttribute('data-printed', 'yes');
  await expect(frame.locator('tbody tr')).toHaveCount(43);
  await expect(frame.locator('thead th')).toHaveCount(3);
  const html = await frame
    .locator('html')
    .evaluate((element) => element.outerHTML);
  const printPage = await page.context().newPage();
  await printPage.goto('/');
  await printPage.setViewportSize({ width: 794, height: 1123 });
  await printPage.setContent(html);
  await printPage.emulateMedia({ media: 'print' });
  await expect(printPage.locator('table')).toHaveCSS(
    'border-collapse',
    'separate',
  );
  const borders = await printPage.locator('th, td').evaluateAll((cells) =>
    cells.map((cell) => ({
      width: parseFloat(getComputedStyle(cell).borderBottomWidth),
      style: getComputedStyle(cell).borderBottomStyle,
      color: getComputedStyle(cell).borderBottomColor,
    })),
  );
  expect(borders).toHaveLength(132);
  for (const border of borders) {
    expect(border.width).toBeGreaterThanOrEqual(1);
    expect(border.style).toBe('solid');
    expect(border.color).toBe('rgb(119, 119, 119)');
  }
  await printPage.screenshot({
    path: '../.runtime/artifacts/playwright/exports/print-uniform-lines.png',
    fullPage: true,
  });
  // Playwright PDF generation is available only in headless Chromium.
  if (browserName === 'chromium') {
    const pdf = await printPage.pdf({
      path: '../.runtime/artifacts/playwright/exports/print-uniform-lines.pdf',
      format: 'A4',
      printBackground: true,
    });
    expect(pdf.subarray(0, 5).toString()).toBe('%PDF-');
  }
  await printPage.close();
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
        await page.goto('/__fixtures/ui?category=data');
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
          path: `../.runtime/artifacts/playwright/exports/states-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
      });
    }
}

test('keeps the toolbar compact and opens accessible export settings', async ({
  page,
}) => {
  await page.addInitScript(() => localStorage.setItem('runa.locale', 'en'));
  await page.goto('/__fixtures/ui?category=data');
  await expect(
    page.getByRole('button', { name: 'Select all matching', exact: true }),
  ).toHaveCount(0);
  await expect(page.getByText('Export scope', { exact: true })).toHaveCount(0);
  const group = page
    .getByRole('group', { name: 'Export and print', exact: true })
    .first();
  await expect(group.getByRole('button')).toHaveCount(5);
  for (const name of ['CSV', 'XLSX', 'PDF', 'Print', 'Export settings'])
    await expect(
      group.getByRole('button', { name, exact: true }).locator('svg'),
    ).toBeVisible();
  const trigger = group.getByRole('button', {
    name: 'Export settings',
    exact: true,
  });
  await trigger.click();
  const dialog = page.getByRole('dialog', {
    name: 'Export settings',
    exact: true,
  });
  await expect(dialog).toBeVisible();
  await dialog.getByLabel('Export scope', { exact: true }).click();
  await page.getByRole('option', { name: 'All rows', exact: true }).click();
  await dialog.getByLabel('PDF orientation', { exact: true }).click();
  await page.getByRole('option', { name: 'Landscape', exact: true }).click();
  await page.keyboard.press('Escape');
  await expect(dialog).not.toBeVisible();
  await expect(trigger).toBeFocused();
  await expect(group.locator('..')).toContainText('All rows');
});
