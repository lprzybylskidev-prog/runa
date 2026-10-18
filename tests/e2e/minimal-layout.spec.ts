import { expect, test } from '@playwright/test';
for (const [device, width, height] of [
  ['desktop', 1440, 900],
  ['tablet', 1024, 768],
  ['mobile', 390, 844],
] as const) {
  for (const locale of ['pl', 'en'] as const) {
    for (const theme of ['light', 'dark'] as const) {
      test(`minimal: ${device} ${locale} ${theme}`, async ({
        page,
      }, testInfo) => {
        test.skip(
          testInfo.project.name !== 'chromium',
          'Exact viewport matrix in Chromium. Root preference behavior runs across browser projects.',
        );
        await page.setViewportSize({ width, height });
        await page.addInitScript(
          ({ locale, theme }) => {
            localStorage.setItem('runa.locale', locale);
            localStorage.setItem('runa.theme', theme);
          },
          { locale, theme },
        );
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
          if (['warning', 'error'].includes(message.type()))
            errors.push(message.text());
        });
        page.on('requestfailed', (request) => errors.push(request.url()));
        page.on('response', (response) => {
          if (response.status() >= 400)
            errors.push(`${response.status()} ${response.url()}`);
        });
        await page.goto('/__review/ui?preview=minimal');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
          locale === 'pl' ? 'Układ minimalny' : 'Minimal layout',
        );
        await expect(page.getByRole('contentinfo')).toContainText(
          /Atlas v2\.4\.1 \| (?:Powered by|Oparte na) Runa v\d+\.\d+\.\d+/,
        );
        await expect(page.getByRole('contentinfo')).toContainText(
          locale === 'pl' ? 'Oparte na Runa' : 'Powered by Runa',
        );
        await expect(page.locator('.art-coordinate')).toHaveText('RUNA');
        if (width < 1024) {
          const art = await page.locator('.runa-art').boundingBox();
          const content = await page
            .getByRole('heading', { level: 1 })
            .boundingBox();
          expect(art!.height).toBe(240);
          expect(art!.y + art!.height).toBeLessThanOrEqual(content!.y);
        }
        await page.evaluate(() => document.fonts.ready);
        expect(
          await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
          ),
        ).toBe(true);
        const short = await page.getByRole('contentinfo').boundingBox();
        expect(short).not.toBeNull();
        expect(short!.y + short!.height).toBeGreaterThanOrEqual(height - 1);
        expect(short!.y + short!.height).toBeLessThanOrEqual(
          (await page.evaluate(() => document.documentElement.scrollHeight)) +
            1,
        );
        await page.screenshot({
          path: `artifacts/playwright/ui-foundations/minimal-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        await page
          .getByRole('button', {
            name: locale === 'pl' ? 'Ilustracja' : 'Illustration',
            exact: true,
          })
          .click();
        await expect(page.locator('.runa-art')).toHaveCount(0);
        await page
          .getByRole('button', {
            name: locale === 'pl' ? 'Ilustracja' : 'Illustration',
            exact: true,
          })
          .click();
        await expect(page.locator('.runa-art')).toBeVisible();
        await page.screenshot({
          path: `artifacts/playwright/ui-foundations/minimal-illustration-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        await page
          .getByRole('button', {
            name:
              locale === 'pl' ? 'Krótka / długa treść' : 'Short / long content',
          })
          .click();
        expect(
          await page.evaluate(
            () => document.documentElement.scrollHeight > innerHeight,
          ),
        ).toBe(true);
        const flow = await page.evaluate(() => {
          const main = document.querySelector('main')!.getBoundingClientRect();
          const footer = document
            .querySelector('footer')!
            .getBoundingClientRect();
          return (
            footer.top >= main.bottom &&
            document.documentElement.scrollWidth <= innerWidth
          );
        });
        expect(flow).toBe(true);
        await page.screenshot({
          path: `artifacts/playwright/ui-foundations/minimal-long-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        await page
          .getByRole('button', {
            name: locale === 'pl' ? 'Stopka' : 'Footer',
            exact: true,
          })
          .click();
        await expect(page.getByRole('contentinfo')).toHaveCount(0);
        expect(errors).toEqual([]);
      });
    }
  }
}
