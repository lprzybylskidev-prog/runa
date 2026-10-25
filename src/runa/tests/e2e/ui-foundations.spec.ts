import { expect, test } from '@playwright/test';
for (const [device, width, height] of [
  ['desktop', 1440, 900],
  ['tablet', 1024, 768],
  ['mobile', 390, 844],
] as const) {
  for (const locale of ['pl', 'en'] as const) {
    for (const theme of ['light', 'dark'] as const) {
      test(`root baseline: ${device} ${locale} ${theme}`, async ({
        page,
      }, testInfo) => {
        test.skip(
          testInfo.project.name !== 'chromium',
          'One exact viewport matrix; preference flow runs in every browser project.',
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
        await page.goto('/');
        await expect(page.locator('html')).toHaveAttribute('lang', locale);
        await expect(page.locator('html')).toHaveAttribute('data-theme', theme);
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
          '.runa',
        );
        await expect(page.getByRole('main')).toContainText(
          locale === 'pl'
            ? 'Modularny monolit na bazie Laravela. Dostarcza w pełni konfigurowalne moduły do budowy aplikacji biznesowych.'
            : 'A modular monolith built on Laravel. Fully configurable modules for building business applications.',
        );
        const modules = page.getByRole('list', {
          name: locale === 'pl' ? 'Moduły Runa' : 'Runa modules',
        });
        await expect(modules.getByRole('img')).toHaveCount(3);
        const audit = modules.getByRole('img', {
          name: locale === 'pl' ? 'Audyt' : 'Audit',
          exact: true,
        });
        await audit.hover();
        await expect(page.getByRole('tooltip')).toHaveText(
          locale === 'pl'
            ? 'Audyt — Niezmienna historia operacji biznesowych i bezpieczeństwa.'
            : 'Audit — Append-only business and security history.',
        );
        await audit.focus();
        await page.keyboard.press('Tab');
        await expect(page.getByRole('tooltip')).toContainText(
          locale === 'pl' ? 'Uwierzytelnianie' : 'Authentication',
        );
        await page.keyboard.press('Escape');
        await expect(page.getByRole('tooltip')).toHaveCount(0);
        await page.mouse.move(0, 0);
        await expect(page.getByRole('contentinfo')).toContainText(
          /Runa v\d+\.\d+\.\d+ \| (?:Powered by|Oparte na) Runa v\d+\.\d+\.\d+/,
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
            () => document.documentElement.scrollWidth <= window.innerWidth,
          ),
        ).toBe(true);
        await page.screenshot({
          path: `../.runtime/artifacts/playwright/ui-foundations/root-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        expect(errors).toEqual([]);
      });
    }
  }
}

for (const reducedMotion of ['no-preference', 'reduce'] as const) {
  test(`preference timing baseline: ${reducedMotion}`, async ({
    page,
  }, testInfo) => {
    await page.emulateMedia({ reducedMotion });
    await page.addInitScript(() => {
      localStorage.setItem('runa.locale', 'pl');
      localStorage.setItem('runa.theme', 'light');
    });
    await page.goto('/');
    await page.evaluate(() => {
      const started = performance.now();
      let changed = 0;
      let leaving = 0;
      let opacity = '';
      let inertAtChange = false;
      const observer = new MutationObserver(() => {
        const cover = document.querySelector('.loading-overlay');
        if (!changed && document.documentElement.lang === 'en') {
          changed = performance.now();
          opacity = cover ? getComputedStyle(cover).opacity : 'absent';
          inertAtChange =
            document.querySelector('.minimal-page')?.hasAttribute('inert') ??
            false;
        }
        if (
          changed &&
          !leaving &&
          cover?.classList.contains('screen-cover-leave-active')
        )
          leaving = performance.now();
        if (changed && !cover) {
          document.documentElement.dataset.preferenceTiming = JSON.stringify({
            enter: changed - started,
            hold: (leaving || performance.now()) - changed,
            opacity,
            inertAtChange,
          });
          observer.disconnect();
        }
      });
      observer.observe(document.documentElement, {
        attributes: true,
        childList: true,
        subtree: true,
      });
    });
    await page.getByRole('button', { name: /^Język:/ }).click();
    await expect(
      page.getByRole('button', { name: /^Language:/ }),
    ).toBeFocused();
    await expect(page.locator('.loading-overlay')).toHaveCount(0);
    const timing = await page.evaluate(() => {
      const value: unknown = JSON.parse(
        document.documentElement.dataset.preferenceTiming ?? 'null',
      );
      if (
        typeof value !== 'object' ||
        value === null ||
        !('enter' in value) ||
        typeof value.enter !== 'number' ||
        !('hold' in value) ||
        typeof value.hold !== 'number' ||
        !('opacity' in value) ||
        !('inertAtChange' in value)
      )
        throw new Error('Missing preference timing evidence.');
      return {
        enter: value.enter,
        hold: value.hold,
        opacity: value.opacity,
        inertAtChange: value.inertAtChange,
      };
    });
    expect(timing.opacity).toBe('1');
    expect(timing.inertAtChange).toBe(true);
    expect(timing.hold).toBeGreaterThanOrEqual(490);
    if (reducedMotion === 'no-preference')
      expect(timing.enter).toBeGreaterThanOrEqual(300);
    await testInfo.attach(`preference-timing-${reducedMotion}`, {
      body: JSON.stringify(timing),
      contentType: 'application/json',
    });
  });
}

test('isolated component controls work and unknown application pages remain HTTP errors', async ({
  page,
  request,
}) => {
  await page.goto('/__fixtures/ui');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText(
    'Component test fixture',
  );
  await page
    .getByRole('button', { name: 'primary', exact: true })
    .first()
    .click();
  await expect(page.locator('output').first()).toHaveText('Kliknięcia: 1');
  for (const path of [
    '/__fixtures/not-found',
    '/__review/ui',
    '/__review/email',
    '/__review/application/overview',
    '/ui-review/index.html',
  ]) {
    expect((await request.get(path)).status()).toBe(404);
  }
});
