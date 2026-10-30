import { expect, test, type Page } from '@runa/testing/playwright';
const id = '01900000-0000-7000-8000-000000000001';
async function transport(page: Page) {
  let proof = false;
  await page.route('**/api/runa/auth/**', async (route) => {
    const path = new URL(route.request().url()).pathname;
    let data: unknown = { ready: true };
    if (path.endsWith('/session'))
      data = {
        public_id: id,
        display_name: 'Admin operator',
        restrictions: [],
        session: {
          public_id: id,
          activity_at: new Date().toISOString(),
          expires_at: new Date(Date.now() + 3600000).toISOString(),
        },
        activity: {
          idle_minutes: 120,
          heartbeat_seconds: 60,
          warning_seconds: 120,
          extension: false,
        },
      };
    if (path.endsWith('/entry-options'))
      data = {
        public_welcome_enabled: true,
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
    if (path.endsWith('/step-up')) {
      expect(route.request().postDataJSON()).toEqual({
        action: 'runa.admin.enter',
        requirement: 'password',
        current_password: 'Example test password',
      });
      proof = true;
      data = { confirmed: true };
    }
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    });
  });
  await page.route('**/api/admin**', async (route) => {
    const path = new URL(route.request().url()).pathname;
    const entry = path === '/api/admin/entry';
    await route.fulfill({
      status: entry || proof ? 200 : 403,
      contentType: 'application/json',
      body: JSON.stringify(
        entry
          ? { data: { available: true } }
          : !proof
            ? { reason: 'step_up_required' }
            : {
                data: {
                  schema_version: 1,
                  generation: 1,
                  revision: 'bootstrap-1',
                  owners: [],
                  dashboard: { widgets: [] },
                  presentation: { name: 'Runa', logo: null, revision: 0 },
                },
              },
      ),
    });
  });
}
test('Admin shell: English proof, account-only entry and responsive light/dark navigation', async ({
  page,
}, info) => {
  await transport(page);
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  for (const [width, height, theme] of [
    [1440, 1000, 'dark'],
    [820, 1180, 'light'],
    [390, 844, 'dark'],
    [390, 844, 'light'],
  ] as const) {
    await page.setViewportSize({ width, height });
    await page.addInitScript((theme) => {
      localStorage.clear();
      localStorage.setItem('runa.locale', 'pl');
      localStorage.setItem('runa.theme', theme);
    }, theme);
    await page.goto('/admin');
    const password = page.getByLabel('Current password', { exact: true });
    await expect(
      password.or(
        page.getByRole('heading', { name: 'Administration', exact: true }),
      ),
    ).toBeVisible();
    if (await password.count()) {
      await password.fill('Example test password');
      await page
        .getByRole('button', { name: 'Confirm password', exact: true })
        .click();
    }
    await expect(
      page.getByRole('heading', { name: 'Administration', exact: true }),
    ).toBeVisible();
    await expect(
      page.getByText('No dashboard widgets are available.'),
    ).toBeVisible();
    await page
      .getByRole('button', { name: 'User menu: Admin operator', exact: true })
      .click();
    const accountMenu = page.locator('.account-menu');
    await expect(
      accountMenu.getByRole('link', { name: 'User panel', exact: true }),
    ).toBeVisible();
    await expect(
      accountMenu.getByRole('link', { name: 'Administration', exact: true }),
    ).toBeVisible();
    await page.keyboard.press('Escape');
    await expect(page.locator('nav a[href="/account"]')).toHaveCount(0);
    expect(await page.evaluate(() => localStorage.getItem('runa.locale'))).toBe(
      'pl',
    );
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    await page.screenshot({
      path: info.outputPath(`admin-${width}-${theme}.png`),
      fullPage: true,
    });
  }
  expect(errors).toEqual([]);
});

test('Admin generated forms: draft reconciliation, safe validation and guarded navigation across responsive themes', async ({
  page,
}, info) => {
  await transport(page);
  let name = 'Before',
    revision = 'r1',
    writes = 0,
    stale = false;
  await page.route('**/api/admin**', async (route) => {
    const url = new URL(route.request().url()),
      path = url.pathname;
    let data: unknown;
    if (path === '/api/admin/entry') data = { available: true };
    else if (path === '/api/admin')
      data = {
        schema_version: 1,
        generation: 1,
        revision: 'bootstrap',
        owners: [
          {
            id: 'app',
            title: 'App administration',
            default_page: 'users',
            pages: [
              {
                key: 'users',
                title: 'Users',
                href: '/admin/app/users',
                group: null,
                order: 0,
              },
            ],
          },
        ],
        dashboard: { widgets: [] },
        presentation: { name: 'Runa', logo: null, revision: 0 },
      };
    else if (path === '/api/admin/app/users')
      data = {
        schema_version: 1,
        owner: 'app',
        key: 'users',
        title: 'Users',
        generation: 1,
        revision: 'page',
        component: null,
        sections: [
          {
            key: 'profile',
            title: 'Profile',
            view: 'record',
            readonly: false,
            required: true,
            component: null,
            record: null,
            diagnostic: null,
            commands: [
              {
                key: 'rename',
                title: 'Save profile',
                mode: 'independent',
                destructive: false,
                requirement: 'none',
                preview_required: false,
                inputs: ['name'],
                bulk: false,
                action: null,
                available: true,
                intent: 'save',
              },
            ],
            fields: [
              {
                key: 'name',
                type: 'text',
                label: 'Display name',
                readonly: false,
                sensitive: false,
                filterable: false,
                sortable: false,
                lookup: false,
                required: true,
                max_length: 40,
                choices: [],
                multiple: false,
                component: null,
              },
            ],
          },
        ],
      };
    else if (route.request().method() === 'POST') {
      writes++;
      if (stale) {
        stale = false;
        name = 'Remote edit';
        revision = 'r2';
        await route.fulfill({
          status: 409,
          contentType: 'application/json',
          body: JSON.stringify({
            reason: 'stale_revision',
            scope: 'operation',
          }),
        });
        return;
      }
      const input = route.request().postDataJSON();
      expect(input.values).toEqual({ name: 'Local edit' });
      name = input.values.name;
      revision = 'r3';
      data = {
        status: 'committed',
        operation_id: null,
        record: { public_id: id, revision, values: { name } },
      };
    } else data = { public_id: id, revision, values: { name } };
    await route.fulfill({
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    });
  });
  const failures: string[] = [];
  page.on('pageerror', (error) => failures.push(error.message));
  for (const [width, height, theme] of [
    [1440, 1000, 'dark'],
    [820, 1180, 'light'],
    [390, 844, 'dark'],
    [390, 844, 'light'],
  ] as const) {
    name = 'Before';
    revision = 'r1';
    writes = 0;
    await page.setViewportSize({ width, height });
    await page.addInitScript((theme) => {
      localStorage.clear();
      localStorage.setItem('runa.theme', theme);
      localStorage.setItem('runa.locale', 'pl');
    }, theme);
    await page.goto('/admin/app/users');
    const field = page.getByLabel('Display name', { exact: true });
    await expect(field).toHaveValue('Before');
    await field.fill('');
    await page
      .getByRole('button', { name: 'Save profile', exact: true })
      .click();
    await expect(field).toHaveAttribute('aria-invalid', 'true');
    expect(writes).toBe(0);
    await field.fill('Local edit');
    stale = true;
    await page
      .getByRole('button', { name: 'Save profile', exact: true })
      .click();
    await expect(
      page.getByText('These values changed while you were editing'),
    ).toBeVisible();
    await expect(field).toHaveValue('Local edit');
    expect(writes).toBe(1);
    await page
      .getByRole('button', { name: 'Keep my edits', exact: true })
      .click();
    expect(writes).toBe(1);
    await page
      .getByRole('button', { name: 'Save profile', exact: true })
      .click();
    await expect(
      page.getByText('Changes saved.', { exact: true }),
    ).toBeVisible();
    expect(writes).toBe(2);
    await field.fill('Unsaved draft');
    await page.screenshot({
      path: info.outputPath(`admin-editor-${width}-${theme}.png`),
      fullPage: true,
    });
    // Trigger browser-owned document unload protection through a known document navigation.
    page.once('dialog', (dialog) => dialog.dismiss());
    await page.evaluate(() => {
      window.location.assign('/admin');
    });
    await expect(field).toHaveValue('Unsaved draft');
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    await page
      .getByRole('button', { name: 'Discard changes', exact: true })
      .click();
  }
  expect(failures).toEqual([]);
});
