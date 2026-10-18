import { expect, test } from '@playwright/test';

const evidence = 'artifacts/playwright/ui-foundations';

for (const locale of ['pl', 'en']) {
  test(`email: light responsive ${locale} HTML without JavaScript`, async ({
    browser,
  }, testInfo) => {
    test.skip(
      testInfo.project.name !== 'chromium',
      'Exact viewport matrix in Chromium.',
    );
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();
    for (const [name, width, height] of [
      ['desktop', 1440, 1000],
      ['tablet', 768, 1024],
      ['mobile', 390, 844],
    ] as const) {
      await page.setViewportSize({ width, height });
      const response = await page.goto(`/__review/email?locale=${locale}`);
      expect(response?.status()).toBe(200);
      await expect(page.locator('html')).toHaveAttribute('lang', locale);
      await expect(page.getByRole('heading', { level: 1 })).toHaveText(
        locale === 'pl' ? 'Raport jest gotowy' : 'Report is ready',
      );
      await expect(page.locator('.footer')).toContainText(
        locale === 'pl' ? 'Oparte na Runa' : 'Powered by Runa',
      );
      expect(
        await page
          .locator('img')
          .evaluate(
            (image: HTMLImageElement) =>
              image.complete && image.naturalWidth > 0,
          ),
      ).toBe(true);
      expect(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= window.innerWidth,
        ),
      ).toBe(true);
      await page.screenshot({
        path: `${evidence}/email-${name}-${locale}.png`,
        fullPage: true,
      });
    }
    await page.goto(`/__review/email?locale=${locale}&long=1&images=blocked`);
    await expect(page.getByRole('heading', { level: 2 })).toHaveCount(12);
    await expect(page.locator('.brand-name')).toHaveText('Runa');
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= window.innerWidth,
      ),
    ).toBe(true);
    await page.screenshot({
      path: `${evidence}/email-long-blocked-${locale}.png`,
      fullPage: true,
    });
    const mime = await context.request.get(
      `/__review/email?locale=${locale}&format=eml`,
    );
    await testInfo.attach(`email-${locale}.eml`, {
      body: await mime.body(),
      contentType: 'message/rfc822',
    });
    const text = await context.request.get(
      `/__review/email?locale=${locale}&format=text`,
    );
    await testInfo.attach(`email-${locale}.txt`, {
      body: await text.body(),
      contentType: 'text/plain',
    });
    await context.close();
  });
}

test('email: HTML and text are served without a review JavaScript build', async ({
  page,
  request,
}) => {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  await page.goto('/__review/email?locale=en');
  await expect(
    page.getByRole('link', { name: 'Open report', exact: true }),
  ).toHaveAttribute('href', 'https://example.invalid/reports/sample');
  expect(await page.locator('script').count()).toBe(0);
  const response = await request.get('/__review/email?locale=en&format=text');
  expect(response.ok()).toBe(true);
  expect(await response.text()).toContain('Powered by Runa');
  expect(errors).toEqual([]);
});
