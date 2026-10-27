import { expect, test, type Page } from '@runa/testing/playwright';

async function entryTransport(page: Page) {
  await page.route('**/api/runa/auth/**', async (route) => {
    const path = new URL(route.request().url()).pathname;
    const data = path.endsWith('/entry-options')
      ? {
          public_welcome_enabled: true,
          public_registration: true,
          identifier_mode: 'email',
          remember_enabled: true,
          password: {
            min_length: 12,
            max_bytes: 72,
            mixed_case: false,
            numbers: false,
            symbols: false,
          },
        }
      : { ready: true };
    await route.fulfill({
      status: path.endsWith('/session') ? 401 : 200,
      contentType: 'application/json',
      body: JSON.stringify(
        path.endsWith('/session')
          ? { error: { code: 'authentication_required' } }
          : { data },
      ),
    });
  });
}
test('Auth entry render diagnostic: keyboard errors and responsive theme states', async ({
  page,
}, info) => {
  await entryTransport(page);
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  for (const [width, height, theme] of [
    [1440, 1000, 'dark'],
    [1440, 1000, 'light'],
    [820, 1180, 'dark'],
    [820, 1180, 'light'],
    [390, 844, 'dark'],
    [390, 844, 'light'],
  ] as const) {
    await page.setViewportSize({ width, height });
    await page.addInitScript((theme) => {
      localStorage.clear();
      localStorage.setItem('runa.theme', theme);
    }, theme);
    await page.goto('/__fixtures/ui?preview=auth&flow=login');
    await expect(
      page.getByRole('heading', { name: 'Zaloguj się', exact: true }),
    ).toBeVisible();
    await expect(
      page.getByRole('textbox', { name: 'Adres e-mail', exact: true }),
    ).toBeVisible();
    await page
      .getByRole('button', { name: 'Zaloguj się', exact: true })
      .click();
    await expect(
      page.getByRole('textbox', { name: 'Adres e-mail', exact: true }),
    ).toBeFocused();
    await expect(
      page.getByText('Wypełnij to pole.', { exact: true }),
    ).toHaveCount(2);
    await page.evaluate(() => document.fonts.ready);
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    await expect(page.locator('[id$="-error"]').first()).toHaveCSS(
      'opacity',
      '1',
    );
    await page.screenshot({
      path: info.outputPath(`login-${width}-${theme}.png`),
      fullPage: true,
    });
  }
  expect(errors).toEqual([]);
});
for (const flow of [
  'register',
  'forgot-password',
  'invitation',
  'reset-password',
  'verify-email',
  'confirm-address',
] as const) {
  for (const locale of ['pl', 'en'] as const) {
    test(`Auth entry ${flow}: ${locale}, reduced motion and private fragment`, async ({
      page,
    }) => {
      await entryTransport(page);
      await page.emulateMedia({ reducedMotion: 'reduce' });
      await page.addInitScript((locale) => {
        localStorage.clear();
        localStorage.setItem('runa.locale', locale);
      }, locale);
      const writes: string[] = [];
      page.on('request', (request) => {
        if (request.method() === 'POST') writes.push(request.url());
      });
      await page.goto(
        `/__fixtures/ui?preview=auth&flow=${flow}#token=${'a'.repeat(64)}`,
      );
      await expect(page.locator('form')).toBeVisible();
      expect(new URL(page.url()).hash).toBe('');
      expect(writes).toEqual([]);
      expect(await page.evaluate(() => document.documentElement.lang)).toBe(
        locale,
      );
      expect(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth,
        ),
      ).toBe(true);
    });
  }
}
