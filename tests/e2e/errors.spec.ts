import { expect, test } from '@playwright/test';

for (const status of [404, 503]) {
  for (const [device, width, height] of [
    ['desktop', 1440, 900],
    ['tablet', 1024, 768],
    ['mobile', 390, 844],
  ] as const) {
    for (const locale of ['pl', 'en']) {
      for (const theme of ['light', 'dark'] as const) {
        test(`errors: ${status} ${device} ${locale} ${theme} without JavaScript`, async ({
          browser,
        }, info) => {
          test.skip(
            info.project.name !== 'chromium',
            'Exact viewport matrix in Chromium.',
          );
          const context = await browser.newContext({
            javaScriptEnabled: false,
            viewport: { width, height },
            colorScheme: theme,
          });
          const page = await context.newPage();
          const failed: string[] = [];
          page.on('response', (response) => {
            if (response.url().includes('/runa-error/') && !response.ok())
              failed.push(response.url());
          });
          const path =
            status === 404 ? '/__review/not-found' : '/__review/errors/503';
          const response = await page.goto(
            `${path}?lang=${locale}&theme=${theme}`,
          );
          expect(response?.status()).toBe(status);
          await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
          await expect(page.locator('html')).toHaveAttribute('lang', locale);
          expect(
            await page.evaluate(
              () => document.documentElement.scrollWidth <= window.innerWidth,
            ),
          ).toBe(true);
          expect(await page.locator('script').count()).toBe(0);
          await expect(page.locator('.minimal-page')).toHaveCount(1);
          await expect(page.locator('.minimal-panel .runa-art')).toHaveCount(1);
          await expect(
            page.locator('.app-backdrop .app-constellation'),
          ).toHaveCount(3);
          await expect(page.locator('.error-card')).toHaveCount(0);
          const artwork = await page.locator('.runa-art').boundingBox();
          const heading = await page
            .getByRole('heading', { level: 1 })
            .boundingBox();
          if (width < 1024) {
            expect(artwork!.height).toBe(240);
            expect(artwork!.y).toBeLessThan(heading!.y);
          } else {
            expect(artwork!.x).toBeGreaterThan(heading!.x);
          }

          await page.screenshot({
            path: `artifacts/playwright/ui-foundations/error-${status}-${device}-${locale}-${theme}.png`,
            fullPage: true,
          });
          expect(failed).toEqual([]);
          await context.close();
        });
      }
    }
  }
}

for (const status of [403, 404, 419, 429, 500, 503]) {
  test(`errors: actual status ${status} and accessible navigation`, async ({
    page,
    request,
  }) => {
    const response = await page.goto(`/__review/errors/${status}?lang=pl`);
    expect(response?.status()).toBe(status);
    await expect(page.locator('.error-code')).toContainText(`HTTP ${status}`);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
    expect(await page.content()).not.toContain(
      'Private review exception details.',
    );
    if ([429, 503].includes(status))
      expect(response?.headers()['retry-after']).toBe('120');
    const json = await request.get(`/__review/errors/${status}`, {
      headers: { Accept: 'application/json' },
    });
    expect(json.status()).toBe(status);
    expect(json.headers()['content-type']).toContain('application/json');
    await page.getByRole('button', { name: /^Język:/ }).click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(
      page.getByRole('button', { name: 'Home page', exact: true }),
    ).toBeVisible();
  });
}

test('errors: standalone emergency document uses independent assets', async ({
  page,
}) => {
  const response = await page.goto('/__review/errors/emergency?lang=pl');
  expect(response?.status()).toBe(503);
  expect(response?.headers()['retry-after']).toBe('120');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(
    'Przerwa techniczna',
  );
  await page.screenshot({
    path: 'artifacts/playwright/ui-foundations/error-emergency.png',
    fullPage: true,
  });
});

test('errors: shared minimal preference controls navigate without JavaScript', async ({
  browser,
}) => {
  const context = await browser.newContext({ javaScriptEnabled: false });
  const page = await context.newPage();
  await page.goto('/__review/not-found?lang=pl&theme=light');
  await page.getByRole('button', { name: /^Język:/ }).click();
  await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'light');
  await page.getByRole('button', { name: /^Theme:/ }).click();
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  await context.close();
});
