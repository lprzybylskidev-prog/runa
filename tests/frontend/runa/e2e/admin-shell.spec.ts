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
    if (path === '/api/admin/activity' && proof) {
      await route.fulfill({
        json: { data: { remaining_seconds: 300, heartbeat_seconds: 30 } },
      });
      return;
    }
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
                  activity: { remaining_seconds: 300, heartbeat_seconds: 30 },
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
        page.getByRole('heading', { name: /^(Modules|Moduły)$/, exact: true }),
      ),
    ).toBeVisible();
    if (await password.count()) {
      await password.fill('Example test password');
      await page
        .getByRole('button', { name: 'Confirm password', exact: true })
        .click();
    }
    await expect(
      page.getByRole('heading', { name: /^(Modules|Moduły)$/, exact: true }),
    ).toBeVisible();
    await expect(
      page.getByText('No dashboard widgets are available.'),
    ).toHaveCount(0);
    await page
      .getByRole('button', { name: 'User menu: Admin operator', exact: true })
      .click();
    const accountMenu = page.locator('.account-menu');
    await expect(
      accountMenu.getByRole('link', { name: 'User panel', exact: true }),
    ).toBeVisible();
    await expect(
      accountMenu.getByRole('link', {
        name: 'Administrator panel',
        exact: true,
      }),
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
    else if (path === '/api/admin/activity')
      data = { remaining_seconds: 300, heartbeat_seconds: 30 };
    else if (path === '/api/admin')
      data = {
        schema_version: 1,
        activity: { remaining_seconds: 300, heartbeat_seconds: 30 },
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

test('Auth administration: owned Users presentation selects accounts and protects dirty drafts in both themes', async ({
  page,
}, info) => {
  const artifact = (await (
    await page.request.get('/build/runa-components.json')
  ).json()) as {
    owners: Record<
      string,
      { build_id: string; manifest_hash: string; components: string[] }
    >;
  };
  const build = artifact.owners['runa.auth']!;
  expect(build.components).toContain('runa.auth.admin_users');
  await transport(page);
  const other = '01900000-0000-7000-8000-000000000002';
  const failures: string[] = [];
  page.on('pageerror', (error) => failures.push(error.message));
  const field = (key: string) => ({
    key,
    type: 'text',
    label: key === 'name' ? 'Display name' : key,
    readonly: false,
    sensitive: false,
    filterable: false,
    sortable: false,
    lookup: false,
    required: false,
    max_length: 160,
    choices: [],
    multiple: false,
    component: null,
  });
  await page.route('**/api/admin/runa.auth/users**', async (route) => {
    const url = new URL(route.request().url());
    let data: unknown;
    if (url.pathname.endsWith('/directory'))
      data = {
        items: [
          {
            public_id: other,
            revision: '1',
            values: { display_name: 'Other account', state: 'current' },
          },
        ],
        next: null,
        total: null,
      };
    else if (url.pathname.endsWith('/profile')) {
      const target = url.searchParams.get('public_id') ?? id;
      data = {
        public_id: target,
        revision: '1',
        values: {
          name: target === other ? 'Selected account' : 'Current account',
        },
      };
    } else
      data = {
        schema_version: 1,
        activity: { remaining_seconds: 300, heartbeat_seconds: 30 },
        owner: 'runa.auth',
        key: 'users',
        title: 'Users',
        generation: 1,
        revision: 'auth-users-1',
        component: {
          owner: 'runa.auth',
          key: 'runa.auth.admin_users',
          build_id: build.build_id,
          manifest_hash: build.manifest_hash,
        },
        sections: [
          {
            key: 'directory',
            title: 'User directory',
            view: 'table',
            readonly: true,
            required: false,
            component: null,
            record: null,
            diagnostic: null,
            fields: [field('display_name'), field('state')],
            commands: [],
          },
          {
            key: 'profile',
            title: 'Profile',
            view: 'record',
            readonly: false,
            required: true,
            component: null,
            record: null,
            diagnostic: null,
            fields: [field('name')],
            commands: [],
          },
        ],
      };
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    });
  });
  for (const [width, theme] of [
    [1440, 'dark'],
    [390, 'light'],
  ] as const) {
    await page.setViewportSize({ width, height: 1000 });
    await page.addInitScript((theme) => {
      localStorage.setItem('runa.theme', theme);
      localStorage.setItem('runa.locale', 'pl');
    }, theme);
    await page.goto('/admin/runa.auth/users');
    const password = page.getByLabel('Current password', { exact: true });
    if (await password.count()) {
      await password.fill('Example test password');
      await page
        .getByRole('button', { name: 'Confirm password', exact: true })
        .click();
    }
    const name = page.getByLabel('Display name', { exact: true });
    await expect(name).toHaveValue('Current account');
    await page
      .getByRole('button', { name: 'Open account', exact: true })
      .click();
    await expect(name).toHaveValue('Selected account');
    await name.fill('Unsaved account draft');
    await page
      .getByRole('button', { name: 'Open account', exact: true })
      .click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByRole('button', { name: 'Cancel', exact: true }).click();
    await expect(name).toHaveValue('Unsaved account draft');
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    await page.screenshot({
      path: info.outputPath(`auth-administration-${width}-${theme}.png`),
      fullPage: true,
    });
    await page
      .getByRole('button', { name: 'Discard changes', exact: true })
      .click();
  }
  expect(failures).toEqual([]);
});

test('Permissions and Audit administration: built definition and immutable history defaults on responsive light and dark screens', async ({
  page,
}, info) => {
  const artifact = (await (
    await page.request.get('/build/runa-components.json')
  ).json()) as {
    owners: Record<
      string,
      { build_id: string; manifest_hash: string; components: string[] }
    >;
  };
  await transport(page);
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  const field = (key: string, type = 'text') => ({
    key,
    label: key,
    type,
    readonly: true,
    sensitive: false,
    filterable: false,
    sortable: false,
    lookup: false,
    required: false,
    max_length: null,
    choices: [],
    multiple: false,
    component: null,
  });
  for (const [owner, key, pageComponent, detailComponent] of [
    ['runa.permissions', 'roles', 'admin_definitions', 'admin_definition'],
    ['runa.audit', 'history', 'admin_history', 'admin_detail'],
  ] as const) {
    const build = artifact.owners[owner]!;
    expect(build.components).toContain(owner + '.' + pageComponent);
    const identity = (component: string) => ({
      owner,
      key: owner + '.' + component,
      build_id: build.build_id,
      manifest_hash: build.manifest_hash,
    });
    const detailKey = owner === 'runa.audit' ? 'detail' : 'editor';
    await page.route(`**/api/admin/${owner}/${key}**`, async (route) => {
      const url = new URL(route.request().url());
      const values =
        owner === 'runa.audit'
          ? {
              action: 'example.changed',
              changes: 'Immutable safe detail',
              entities: JSON.stringify([
                { module: 'runa.auth', type: 'user', public_id: id },
              ]),
            }
          : {
              code: 'runa.auth.reader',
              name_en: 'Source reader',
              module: 'runa.auth',
              system: false,
            };
      let data: unknown;
      if (url.pathname.endsWith('/catalogue'))
        data = {
          items: [
            {
              public_id: id,
              revision: '1',
              values: {
                [owner === 'runa.audit' ? 'action' : 'code']:
                  owner === 'runa.audit'
                    ? 'example.changed'
                    : 'runa.auth.reader',
              },
            },
          ],
          next: null,
          total: null,
        };
      else if (url.pathname.endsWith('/' + detailKey))
        data = url.searchParams.has('public_id')
          ? { public_id: id, revision: '1', values }
          : { public_id: null, revision: '0', values: {} };
      else
        data = {
          schema_version: 1,
          activity: { remaining_seconds: 300, heartbeat_seconds: 30 },
          owner,
          key,
          title: owner === 'runa.audit' ? 'Audit history' : 'Roles',
          generation: 1,
          revision: 'page-1',
          component: identity(pageComponent),
          sections: [
            {
              key: 'catalogue',
              title: 'Records',
              view: 'table',
              readonly: true,
              required: false,
              component: null,
              record: null,
              diagnostic: null,
              fields: [field(owner === 'runa.audit' ? 'action' : 'code')],
              commands: [],
            },
            {
              key: detailKey,
              title: 'Details',
              view: 'record',
              readonly: true,
              required: false,
              component: identity(detailComponent),
              record: null,
              diagnostic: null,
              fields: Object.keys(values).map((key) =>
                field(key, key === 'system' ? 'boolean' : 'text'),
              ),
              commands: [],
            },
          ],
        };
      await route.fulfill({
        status: 200,
        contentType: 'application/json',
        body: JSON.stringify({ data }),
      });
    });
    for (const [width, height, theme] of [
      [1440, 1000, 'dark'],
      [820, 1180, 'light'],
      [390, 844, 'dark'],
      [390, 844, 'light'],
    ] as const) {
      await page.setViewportSize({ width, height });
      await page.addInitScript((theme) => {
        localStorage.setItem('runa.theme', theme);
        localStorage.setItem('runa.locale', 'pl');
      }, theme);
      await page.goto(`/admin/${owner}/${key}`);
      const password = page.getByLabel('Current password', { exact: true });
      if (await password.count()) {
        await password.fill('Example test password');
        await page
          .getByRole('button', { name: 'Confirm password', exact: true })
          .click();
      }
      await page
        .getByRole('button', { name: 'Open record', exact: true })
        .click();
      if (owner === 'runa.audit') {
        await expect(
          page.getByText('Immutable safe detail', { exact: true }),
        ).toBeVisible();
        await expect(
          page.getByRole('link', { name: /History: runa.auth/ }),
        ).toBeVisible();
      } else
        await expect(
          page.getByRole('dialog').getByText('runa.auth.reader', {
            exact: true,
          }),
        ).toBeVisible();
      expect(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth,
        ),
      ).toBe(true);
      await page.screenshot({
        path: info.outputPath(`${owner}-${width}-${theme}.png`),
        fullPage: true,
      });
    }
  }
  expect(errors).toEqual([]);
});

test('Admin operations: persisted health, explicit cooperative cancellation and responsive themes', async ({
  page,
}, info) => {
  await transport(page);
  let cancellation = false;
  await page.route('**/api/admin/operations/**', async (route) => {
    const operation = new URL(route.request().url()).pathname.split('/').at(-1);
    let data: unknown;
    const work = {
      workId: id,
      moduleId: 'app.safe',
      operation: 'process',
      status: 'pending',
      progress: 'queued',
      cancellable: true,
      cancellationRequested: cancellation,
      limitations: 'safe_checkpoints_only',
    };
    if (operation === 'capabilities')
      data = { capabilities: ['diagnostics.read', 'work.read', 'work.cancel'] };
    else if (operation === 'diagnostics')
      data = {
        generation: 1,
        platform_version: '0.7.0',
        application_version: '1.0.0',
        runtime: { php: '8.4' },
        modules: [
          {
            id: 'app.safe',
            kind: 'optional',
            enabled: true,
            available: false,
            health: 'unknown',
            registered: true,
            source_version: '1.0.0',
            installed_version: '1.0.0',
            required: {},
            optional: {},
            reasons: ['health_expired'],
            observations: [
              {
                passed: true,
                observed_at: '2026-10-30T00:00:00Z',
                expires_at: '2026-10-30T00:01:00Z',
              },
            ],
          },
        ],
      };
    else if (operation === 'work') data = { work: [work], truncated: false };
    else if (operation === 'work.cancel') {
      expect(route.request().postDataJSON()).toEqual({ id });
      cancellation = true;
      data = { work: { ...work, cancellationRequested: true } };
    } else data = { work };
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    });
  });
  for (const [width, height, theme] of [
    [1440, 1000, 'dark'],
    [820, 1180, 'light'],
    [390, 844, 'dark'],
    [390, 844, 'light'],
  ] as const) {
    cancellation = false;
    await page.setViewportSize({ width, height });
    await page.addInitScript((theme) => {
      localStorage.setItem('runa.theme', theme);
      localStorage.setItem('runa.locale', 'pl');
    }, theme);
    await page.goto('/admin');
    const password = page.getByLabel('Current password', { exact: true });
    await expect(
      password.or(
        page.getByRole('heading', { name: /^(Modules|Moduły)$/, exact: true }),
      ),
    ).toBeVisible();
    if (await password.count()) {
      await password.fill('Example test password');
      await page
        .getByRole('button', { name: 'Confirm password', exact: true })
        .click();
    }
    await expect(
      page.getByText('Health observations have expired.', { exact: true }),
    ).toBeVisible();
    await page.getByRole('button', { name: 'app.safe', exact: true }).click();
    await page
      .getByRole('button', { name: 'Active work', exact: true })
      .click();
    await page
      .getByRole('button', { name: 'Request cancellation', exact: true })
      .click();
    await expect(
      page.getByRole('dialog').filter({
        has: page.getByRole('button', { name: 'Confirm', exact: true }),
      }),
    ).toBeVisible();
    await page
      .getByRole('dialog')
      .filter({
        has: page.getByRole('button', { name: 'Confirm', exact: true }),
      })
      .getByRole('button', { name: 'Confirm', exact: true })
      .click();
    await expect(
      page.getByText('Requested; completion is not confirmed.', {
        exact: true,
      }),
    ).toBeVisible();
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    await page.screenshot({
      path: info.outputPath(`admin-operations-${width}-${theme}.png`),
      fullPage: true,
    });
  }
});

test('application global settings compose responsive light and dark defaults with explicit source reset', async ({
  page,
}, info) => {
  await transport(page);
  let name: string | null = null,
    revision = 1;
  await page.route('**/api/admin/operations/**', async (route) => {
    const operation = new URL(route.request().url()).pathname.split('/').at(-1);
    let data: unknown = {
      capabilities: ['application.read', 'application.manage'],
    };
    if (operation === 'application.update') {
      const input = route.request().postDataJSON();
      expect(input.revision).toBe(revision);
      name = input.patch['presentation.name'];
      revision++;
    }
    if (operation === 'application' || operation === 'application.update')
      data = {
        revision,
        values: { 'presentation.name': name },
        definitions: {
          'presentation.name': {
            title: 'Displayed application name',
            type: 'string',
            max_length: 160,
            choices: [],
            writable: true,
          },
        },
      };
    await route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({ data }),
    });
  });
  await page.route('**/api/presentation', async (route) =>
    route.fulfill({
      status: 200,
      contentType: 'application/json',
      body: JSON.stringify({
        data: { name: name ?? 'Runa', logo: null, revision },
      }),
    }),
  );
  for (const [width, height, theme] of [
    [1440, 1000, 'light'],
    [820, 1180, 'dark'],
    [390, 844, 'light'],
    [390, 844, 'dark'],
  ] as const) {
    await page.setViewportSize({ width, height });
    await page.addInitScript((theme) => {
      localStorage.setItem('runa.theme', theme);
    }, theme);
    await page.goto('/admin');
    const password = page.getByLabel('Current password', { exact: true });
    await expect(
      password.or(
        page.getByRole('heading', { name: /^(Modules|Moduły)$/, exact: true }),
      ),
    ).toBeVisible();
    if (await password.count()) {
      await password.fill('Example test password');
      await page
        .getByRole('button', { name: 'Confirm password', exact: true })
        .click();
    }
    await page
      .getByRole('button', { name: 'Application settings', exact: true })
      .click();
    await page
      .getByLabel('Displayed application name', { exact: true })
      .fill('Runtime Atlas');
    await page
      .getByRole('button', { name: 'Save application settings', exact: true })
      .click();
    await expect(
      page.getByText('Application settings saved.', { exact: true }),
    ).toBeVisible();
    await expect(
      page.getByLabel('Displayed application name', { exact: true }),
    ).toHaveValue('Runtime Atlas');
    expect(
      await page.evaluate(
        () => document.documentElement.scrollWidth <= innerWidth,
      ),
    ).toBe(true);
    await page.screenshot({
      path: info.outputPath(`application-settings-${width}-${theme}.png`),
      fullPage: true,
    });
    await page
      .getByRole('button', {
        name: 'Restore defaults: Displayed application name',
        exact: true,
      })
      .click();
    await page
      .getByRole('button', { name: 'Save application settings', exact: true })
      .click();
    await expect(
      page.getByText('Application settings saved.', { exact: true }),
    ).toBeVisible();
    expect(name).toBeNull();
  }
});
