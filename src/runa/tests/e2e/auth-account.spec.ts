import { expect, test, type Page } from '@playwright/test';
const id = '01900000-0000-7000-8000-000000000001';
async function accountTransport(page: Page, welcome = true) {
  let signedIn = true;
  let updates = 0;
  await page.route('**/api/runa/auth/**', async (route) => {
    const path = new URL(route.request().url()).pathname;
    let status = 200;
    let data: unknown = { ready: true };
    if (path.endsWith('/logout')) {
      signedIn = false;
      data = { accepted: true };
    } else if (path.endsWith('/login')) {
      signedIn = true;
      data = { public_id: id, display_name: 'Anna Kowalska', restrictions: [] };
    } else if (path.endsWith('/entry-options'))
      data = {
        public_welcome_enabled: welcome,
        public_registration: false,
        identifier_mode: 'email',
        remember_enabled: false,
        password: {
          min_length: 12,
          max_bytes: 72,
          mixed_case: false,
          numbers: false,
          symbols: false,
        },
      };
    else if (!signedIn && !path.endsWith('/csrf')) status = 401;
    else if (path.endsWith('/session'))
      data = {
        public_id: id,
        display_name: 'Anna Kowalska',
        restrictions: [],
        session: {
          public_id: id,
          activity_at: new Date().toISOString(),
          expires_at: new Date(Date.now() + 3600000).toISOString(),
        },
        activity: {
          idle_minutes: 120,
          heartbeat_seconds: 30,
          warning_seconds: 120,
          extension: true,
        },
      };
    else if (path.endsWith('/security'))
      data = {
        email_verified: true,
        required: 'optional',
        enrolled: [],
        totp_enabled: true,
        email_otp_enabled: true,
        recovery_codes_enabled: true,
        password_change_factor: false,
        trusted_devices_enabled: false,
      };
    else if (path.endsWith('/session/activity'))
      data = {
        public_id: id,
        activity_at: new Date().toISOString(),
        expires_at: new Date(Date.now() + 3600000).toISOString(),
      };
    else if (path.endsWith('/profile')) {
      if (route.request().method() === 'PATCH') updates++;
      data = {
        public_id: id,
        version: 1 + updates,
        display_name: 'Anna Kowalska',
        initials: 'AK',
        name: 'Anna Kowalska',
        first_name: null,
        surname: null,
        login: null,
        email: 'anna@example.test',
        email_verified: true,
        has_avatar: false,
        secondary_emails: [],
        capabilities: {
          own_name_editable: true,
          own_login_editable: true,
          own_email_editable: true,
          secondary_emails_enabled: false,
          secondary_email_limit: 5,
          avatar_enabled: true,
          avatar_editor: 'both',
          avatar_max_kib: 2048,
          own_login_history: false,
          own_account_history: false,
          self_deactivate: false,
          self_delete: false,
        },
      };
    } else if (path.endsWith('/preferences'))
      data = { account: id, revision: 0, legacy_claimed: true, values: [] };
    await route.fulfill({
      status,
      contentType: 'application/json',
      body: JSON.stringify(
        status === 401
          ? { error: { code: 'authentication_required' } }
          : { data },
      ),
    });
  });
  // Exercise document navigation with isolated owner fixtures, never add preview routes to delivery.
  await page.route('**/*', async (route) => {
    const url = new URL(route.request().url());
    if (
      !route.request().isNavigationRequest() ||
      !['/', '/app', '/account', '/auth/login'].includes(url.pathname)
    )
      return route.fallback();
    const response = await route.fetch({
      url: url.origin + '/__fixtures/ui?preview=auth-account',
    });
    await route.fulfill({ response });
  });
}

test('Auth account render diagnostic: profile and sign-out share the accessible dropdown', async ({
  page,
}, info) => {
  await accountTransport(page);
  const errors: string[] = [];
  page.on('pageerror', (e) => errors.push(e.message));
  for (const [width, height, theme] of [
    [1440, 1000, 'dark'],
    [1440, 1000, 'light'],
    [820, 1180, 'dark'],
    [820, 1180, 'light'],
    [390, 844, 'dark'],
    [390, 844, 'light'],
  ] as const) {
    await page.setViewportSize({ width, height });
    await page.emulateMedia({ colorScheme: theme });
    await page.addInitScript((theme) => {
      localStorage.clear();
      localStorage.setItem('runa.theme', theme);
    }, theme);
    await page.goto('/account');
    await expect(
      page.getByRole('heading', { name: 'Moje konto', exact: true }),
    ).toBeVisible();
    await expect(
      page.getByRole('textbox', { name: 'Nazwa wyświetlana', exact: true }),
    ).toHaveValue('Anna Kowalska');
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    const menu = page.getByRole('button', {
      name: 'Menu użytkownika: Anna Kowalska',
      exact: true,
    });
    await menu.focus();
    await page.keyboard.press('Enter');
    await expect(
      page.getByRole('button', { name: 'Wyloguj się', exact: true }),
    ).toBeVisible();
    await expect(
      page.getByRole('link', { name: 'Moje konto', exact: true }).last(),
    ).toBeVisible();
    await expect(page.getByRole('alert')).toHaveCount(0);
    await expect(page.locator('.app-popover-panel')).toHaveCSS('opacity', '1');
    await page.evaluate(() => document.fonts.ready);
    await page.screenshot({
      path: info.outputPath(`account-${theme}-${width}.png`),
      fullPage: true,
    });
    await page.keyboard.press('Escape');
    await expect(menu).toBeFocused();
  }
  expect(errors).toEqual([]);
});

for (const welcome of [true, false]) {
  test(`Auth application navigation: sign-in, profile and sign-out with welcome=${welcome}`, async ({
    page,
  }) => {
    await accountTransport(page, welcome);
    await page.goto('/app');
    await expect(
      page.getByRole('heading', { name: 'Aplikacja', exact: true }),
    ).toBeVisible();
    await page
      .getByRole('button', { name: 'Menu użytkownika: Anna Kowalska' })
      .click();
    await page
      .getByRole('button', { name: 'Wyloguj się', exact: true })
      .click();
    if (welcome) {
      await expect(page).toHaveURL('/');
      await page
        .getByRole('link', { name: 'Zaloguj się', exact: true })
        .click();
    }
    await expect(page).toHaveURL('/auth/login');
    await page
      .getByRole('textbox', { name: 'Adres e-mail', exact: true })
      .fill('anna@example.test');
    await page
      .getByLabel('Hasło', { exact: true })
      .fill('a-long-test-password');
    await page
      .getByRole('button', { name: 'Zaloguj się', exact: true })
      .click();
    await expect(page).toHaveURL('/app');
    await page.goto('/account');
    await expect(
      page.getByRole('heading', { name: 'Moje konto', exact: true }),
    ).toBeVisible();
    await page
      .getByRole('button', { name: 'Menu użytkownika: Anna Kowalska' })
      .click();
    await page
      .getByRole('button', { name: 'Wyloguj się', exact: true })
      .click();
    await page.goto('/app');
    await expect(
      page.getByRole('heading', { name: 'Zaloguj się', exact: true }),
    ).toBeVisible();
    await page.reload();
    await expect(
      page.getByRole('heading', { name: 'Zaloguj się', exact: true }),
    ).toBeVisible();
  });
}
