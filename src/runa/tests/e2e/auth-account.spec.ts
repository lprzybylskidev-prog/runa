import { expect, test, type Page } from '@playwright/test';
const id = '01900000-0000-7000-8000-000000000001';
async function accountTransport(
  page: Page,
  welcome = true,
  presentation: {
    avatar?: boolean;
    profileReady?: Promise<void>;
    history?: boolean;
  } = {},
) {
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
      await presentation.profileReady;
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
        has_avatar: presentation.avatar ?? false,
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
          own_account_history: presentation.history ?? false,
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
      page.getByRole('heading', { name: 'Panel użytkownika', exact: true }),
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
      page.getByRole('link', { name: 'Panel użytkownika', exact: true }).last(),
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
      page.getByRole('heading', { name: 'Panel użytkownika', exact: true }),
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

for (const mode of ['composed', 'replacement']) {
  test(`Frontend customization: ${mode} keeps responsive content and reauthentication`, async ({
    page,
  }, info) => {
    await accountTransport(page);
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    for (const theme of ['light', 'dark'] as const) {
      await page.emulateMedia({ colorScheme: theme, reducedMotion: 'reduce' });
      await page.addInitScript((theme) => {
        localStorage.clear();
        localStorage.setItem('runa.theme', theme);
      }, theme);
      await page.goto('/app?presentation=' + mode);
      const content = page.locator(
        mode === 'composed'
          ? '[data-customer-content]'
          : '[data-customer-application]',
      );
      await expect(
        content.getByRole('heading', { name: 'Anna Kowalska' }),
      ).toBeVisible();
      expect(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth,
        ),
      ).toBe(true);
      await page.screenshot({
        path: info.outputPath(`customization-${mode}-${theme}.png`),
        fullPage: true,
      });
    }
    await page.route('**/api/runa/auth/session', (route) =>
      route.fulfill({
        status: 401,
        contentType: 'application/json',
        body: JSON.stringify({ error: { code: 'authentication_required' } }),
      }),
    );
    await page.getByRole('button', { name: 'Check access' }).click();
    await expect(
      page.locator('[data-customer-content], [data-customer-application]'),
    ).toHaveCount(0);
    await expect(
      page.getByRole('textbox', { name: 'Adres e-mail', exact: true }),
    ).toBeVisible();
    await expect(
      page.locator('[data-customer-brand], [data-customer-entry]').first(),
    ).toBeVisible();
    expect(errors).toEqual([]);
  });
}

for (const welcome of [true, false]) {
  for (const [path, width, theme] of [
    ['/app', 1440, 'dark'],
    ['/account', 390, 'light'],
  ] as const) {
    test(`avatar loading and breadcrumbs: ${path} welcome=${welcome}`, async ({
      page,
    }, info) => {
      test.skip(
        info.project.name !== 'chromium',
        'Focused shell presentation diagnostic.',
      );
      let releaseProfile!: () => void;
      let releaseImage!: () => void;
      const profileReady = new Promise<void>((resolve) => {
        releaseProfile = resolve;
      });
      const imageReady = new Promise<void>((resolve) => {
        releaseImage = resolve;
      });
      await page.setViewportSize({ width, height: 900 });
      await page.addInitScript((theme) => {
        localStorage.setItem('runa.locale', 'pl');
        localStorage.setItem('runa.theme', theme);
      }, theme);
      await accountTransport(page, welcome, { avatar: true, profileReady });
      await page.route('**/api/runa/auth/profile/avatar**', async (route) => {
        await imageReady;
        await route.fulfill({
          contentType: 'image/svg+xml',
          body: '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40"><rect width="40" height="40" fill="#9776b8"/><circle cx="20" cy="15" r="8" fill="#fff"/></svg>',
        });
      });
      await page.goto(path);
      const trigger = page.locator('.shell-avatar-button');
      await expect(trigger).toHaveAttribute('aria-label', /Anna Kowalska/);
      await expect(trigger.locator('.avatar-skeleton')).toBeVisible();
      await expect(trigger).not.toContainText('AK');
      await trigger.click();
      const menuAvatar = page.locator('.account-menu-identity .runa-avatar');
      await expect(menuAvatar.locator('.avatar-skeleton')).toBeVisible();
      releaseProfile();
      await expect(trigger.locator('img')).toHaveAttribute(
        'src',
        /profile\/avatar/,
      );
      await expect(trigger.locator('.avatar-skeleton')).toBeVisible();
      await expect(menuAvatar.locator('.avatar-skeleton')).toBeVisible();
      await page.screenshot({
        path: `../.runtime/artifacts/playwright/avatar-${path.slice(1)}-${welcome}-loading.png`,
        fullPage: true,
      });
      for (const avatar of [trigger.locator('.runa-avatar'), menuAvatar]) {
        await expect(avatar).toHaveCSS('background-color', 'rgba(0, 0, 0, 0)');
        const pulse = avatar.locator('.avatar-skeleton span');
        const bounds = await pulse.boundingBox();
        expect(bounds?.width).toBe(40);
        expect(bounds?.height).toBe(40);
        const opacity = await pulse.evaluate((el) => {
          const animation = el.getAnimations()[0]!;
          animation.pause();
          animation.currentTime = 0;
          const start = getComputedStyle(el).opacity;
          animation.currentTime = 800;
          const midpoint = getComputedStyle(el).opacity;
          animation.play();
          return [Number(start), Number(midpoint)];
        });
        expect(opacity[0]).toBe(1);
        expect(opacity[1]).toBeCloseTo(0.45);
      }
      await page.emulateMedia({ reducedMotion: 'reduce' });
      await expect(trigger.locator('.avatar-skeleton span')).toHaveCSS(
        'animation-name',
        'none',
      );
      releaseImage();
      await expect(trigger.locator('img')).toBeVisible();
      await expect(menuAvatar.locator('img')).toBeVisible();
      await expect(trigger.locator('.avatar-skeleton')).toHaveCount(0);
      await expect(menuAvatar.locator('.avatar-skeleton')).toHaveCount(0);
      await page.keyboard.press('Escape');
      await expect(menuAvatar).toHaveCount(0);
      await expect(page.locator('.shell-brand').first()).toHaveAttribute(
        'href',
        welcome ? '/' : '/app',
      );
      const breadcrumbs = page.getByRole('navigation', {
        name: 'Ścieżka nawigacji',
      });
      await expect(breadcrumbs.locator('[aria-current="page"]')).toHaveText(
        path === '/app' ? 'Aplikacja' : 'Panel użytkownika',
      );
      await expect(
        breadcrumbs.getByRole('link', { name: 'Strona główna', exact: true }),
      ).toHaveCount(welcome ? 1 : 0);
      if (path === '/account')
        await expect(
          breadcrumbs.getByRole('link', { name: 'Aplikacja' }),
        ).toHaveAttribute('href', '/app');
      if (width > 1024)
        await expect(page.locator('.shell-brand').first()).toHaveText('runa.');
      await page.screenshot({
        path: `../.runtime/artifacts/playwright/avatar-${path.slice(1)}-${welcome}-loaded.png`,
        fullPage: true,
      });
    });
  }
}

test('disabled welcome keeps public Auth branding inside application entry', async ({
  page,
}, info) => {
  test.skip(
    info.project.name !== 'chromium',
    'Focused public branding diagnostic.',
  );
  await accountTransport(page, false);
  await page.goto('/auth/login');
  await expect(page.locator('.brand')).toHaveAttribute('href', '/app');
});

for (const [width, theme, locale] of [
  [1440, 'dark', 'pl'],
  [390, 'light', 'en'],
] as const) {
  test(`account history detail modal: ${width} ${locale}`, async ({
    page,
  }, info) => {
    test.skip(
      info.project.name !== 'chromium',
      'Focused safe detail presentation.',
    );
    await page.setViewportSize({ width, height: 900 });
    await page.addInitScript(
      ({ theme, locale }) => {
        localStorage.setItem('runa.theme', theme);
        localStorage.setItem('runa.locale', locale);
      },
      { theme, locale },
    );
    await accountTransport(page, true, { history: true });
    await page.route('**/api/runa/auth/preferences', (route) =>
      route.fulfill({
        json: {
          data: {
            account: id,
            revision: 0,
            legacy_claimed: true,
            values: [
              {
                scope: 'runa:runa.auth:global:locale',
                value: locale,
                version: 1,
                stored: true,
              },
              {
                scope: 'runa:runa.auth:global:theme',
                value: theme,
                version: 1,
                stored: true,
              },
            ],
          },
        },
      }),
    );
    const event = '01900000-0000-7000-8000-000000000099';
    await page.route('**/api/runa/auth/profile/history?*', (route) =>
      route.fulfill({
        json: {
          data: {
            entries: [
              {
                public_id: event,
                occurred_at: '2026-10-25T12:00:00Z',
                action: 'delivery.preferences_changed',
                outcome: 'success',
              },
            ],
            next: null,
          },
        },
      }),
    );
    await page.route('**/api/runa/auth/profile/history/detail?*', (route) =>
      route.fulfill({
        json: {
          data: {
            public_id: event,
            occurred_at: '2026-10-25T12:00:00Z',
            action: 'delivery.preferences_changed',
            outcome: 'success',
            changes: [
              {
                field: 'category',
                before: 'runa.auth.new_browser',
                after: 'runa.auth.new_browser',
              },
              { field: 'enabled', before: true, after: false },
            ],
          },
        },
      }),
    );
    await page.goto('/account');
    await page
      .getByRole('button', {
        name: locale === 'pl' ? 'Historia konta' : 'Account history',
        exact: true,
      })
      .click();
    const details = page.getByRole('button', {
      name: locale === 'pl' ? 'Szczegóły' : 'Details',
      exact: true,
    });
    await details.focus();
    await page.keyboard.press('Enter');
    const dialog = page.getByRole('dialog', {
      name: locale === 'pl' ? 'Szczegóły zdarzenia' : 'Event details',
    });
    await expect(dialog).toBeVisible();
    await expect(dialog).toContainText(
      locale === 'pl' ? 'Nowa przeglądarka' : 'New browser',
    );
    await expect(dialog).toContainText(locale === 'pl' ? 'Przed' : 'Before');
    await expect(dialog).toContainText(locale === 'pl' ? 'Po' : 'After');
    expect(
      await dialog.evaluate((el) => el.scrollWidth <= el.clientWidth),
    ).toBe(true);
    await expect(page.locator('.dialog-backdrop')).toHaveCSS('opacity', '1');
    await page.screenshot({
      path: `../.runtime/artifacts/playwright/history-details-${width}-${locale}.png`,
      fullPage: true,
    });
    await page.keyboard.press('Escape');
    await expect(dialog).toHaveCount(0);
    await expect(details).toBeFocused();
    await page.route('**/api/runa/auth/profile/history/detail?*', (route) =>
      route.fulfill({
        json: {
          data: {
            public_id: event,
            occurred_at: '2026-10-25T12:00:00Z',
            action: 'profile.changed',
            outcome: 'success',
            changes: [],
          },
        },
      }),
    );
    await details.click();
    await expect(page.getByRole('dialog')).toContainText(
      locale === 'pl'
        ? 'nie zapisano szczegółów zmiany'
        : 'Change details were not recorded',
    );
  });
}
