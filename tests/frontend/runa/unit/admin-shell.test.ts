/* eslint-disable vue/one-component-per-file -- Independent mounted controller and presentation fixtures. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import type { Component, PropType } from 'vue';
import {
  AdminApplication,
  AdminForm,
  AdminPresentationProvider,
  resolveAdminComponent,
  useAdmin,
  useAdminForm,
  useAdminPage,
  useAdminResource,
  useAdminTable,
  AdminResourceTable,
  useAdminPresentation,
} from '@/runa/admin';
import { ApplicationSessionShell } from '@/runa/auth';
import type {
  ActivitySession,
  ApplicationSessionController,
  Profile,
} from '@/runa/auth';
import type {
  AdminBootstrap,
  AdminController,
  AdminLocation,
  AdminPageController,
  AdminPageData,
  AdminResourceController,
  AdminTableController,
  AdminSectionPresentationProps,
} from '@/runa/admin';
import { accountPreferences } from '../../../../src/runa/modules/auth/frontend/preferences/session';

import CustomerAdmin from './fixtures/customization/CustomerAdmin.vue';
import CustomerAdminTable from './fixtures/customization/CustomerAdminTable.vue';
import { builds } from 'virtual:runa-module-components';
enableAutoUnmount(afterEach);
const identity = '01900000-0000-7000-8000-000000000001';
function sessionFixture(): ApplicationSessionController {
  return {
    session: shallowRef<ActivitySession>({
      public_id: identity,
      display_name: 'Owner',
      restrictions: [],
      session: {
        public_id: identity,
        activity_at: new Date().toISOString(),
        expires_at: new Date(Date.now() + 600000).toISOString(),
      },
      activity: {
        heartbeat_seconds: 60,
        idle_minutes: 10,
        warning_seconds: 30,
        extension: true,
      },
    }),
    authorityRevision: ref(1),
    profile: shallowRef<Profile>(),
    profileLoading: ref(false),
    welcomeEnabled: ref(false),
    paused: ref(false),
    loading: ref(false),
    failure: ref(''),
    pending: ref(false),
    warning: computed(() => false),
    remaining: computed(() => 600000),
    refresh: vi.fn(async () => {}),
    logout: vi.fn(async () => {}),
    authenticated: vi.fn(async () => {}),
    activity: vi.fn(async () => {}),
  };
}
function bootstrap(): AdminBootstrap {
  return {
    schema_version: 1,
    generation: 7,
    revision: 'bootstrap-1',
    owners: [
      {
        id: 'app',
        title: 'Application settings',
        default_page: 'settings',
        pages: [
          {
            key: 'settings',
            title: 'Settings',
            href: '/admin/app/settings',
            group: null,
            order: 0,
          },
        ],
      },
    ],
    dashboard: { widgets: [] },
    presentation: { name: 'Example', logo: null, revision: 0 },
  };
}
function respond(data: unknown, status = 200): Response {
  return new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
}
beforeEach(() => {
  window.history.replaceState({}, '', '/admin');
  accountPreferences.disconnect();
  localStorage.clear();
  localStorage.setItem('runa.locale', 'pl');
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const path = String(input);
    if (path === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (path === '/api/admin') return respond({ data: bootstrap() });
    return respond({ reason: 'not_found' }, 404);
  });
});
afterEach(() => {
  window.history.replaceState({}, '', '/');
  accountPreferences.disconnect();
});

it('Admin session diagnostic fences bootstrap and page responses after an account switch or suspension', async () => {
  const session = sessionFixture();
  let finish!: (value: Response) => void;
  vi.mocked(window.fetch).mockImplementation(
    () =>
      new Promise((resolve) => {
        finish = resolve;
      }),
  );
  let admin!: AdminController, page!: AdminPageController;
  const location = ref<AdminLocation>({ owner: 'app', page: 'settings' });
  const wrapper = mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate: vi.fn() });
        page = useAdminPage(admin, location);
        return () => h('div');
      },
    }),
  );
  session.paused.value = true;
  finish(respond({ data: bootstrap() }));
  await flushPromises();
  expect(admin.bootstrap.value).toBeUndefined();
  expect(page.page.value).toBeUndefined();
  expect(admin.state.value).toBe('loading');
  session.paused.value = false;
  await flushPromises();
  finish(respond({ data: bootstrap() }));
  await vi.waitFor(() => expect(admin.state.value).toBe('ready'));
  const oldPage = finish;
  session.session.value = {
    ...session.session.value!,
    public_id: '01900000-0000-7000-8000-000000000002',
  };
  oldPage(
    respond({
      data: {
        schema_version: 1,
        owner: 'app',
        key: 'settings',
        title: 'Old account values',
        generation: 7,
        revision: 'page-1',
        component: null,
        sections: [],
      },
    }),
  );
  await flushPromises();
  expect(page.page.value).toBeUndefined();
  expect(admin.bootstrap.value).toBeUndefined();
  wrapper.unmount();
});

it('uses one supplied session, scopes English controls and places Admin entry only in the account menu', async () => {
  const session = sessionFixture();
  const w = mount(AdminApplication, {
    props: { session },
    attachTo: document.body,
  });
  await vi.waitFor(() => expect(w.text()).toContain('No dashboard widgets'));
  const shell = w.getComponent(ApplicationSessionShell);
  expect(shell.props('session')).toBe(session);
  expect(shell.props('navigation')?.map((n) => n.label)).toEqual([
    'Administration',
    'Application settings',
  ]);
  const button = w
    .findAll('button')
    .find((b) => b.attributes('aria-label')?.startsWith('User menu:'))!;
  expect(button).toBeTruthy();
  await button.trigger('click');
  await flushPromises();
  const menu = document.querySelector('.account-menu')!;
  expect(menu.querySelector('a[href="/admin"]')?.textContent).toContain(
    'Administration',
  );
  expect(menu.querySelector('a[href="/account"]')?.textContent).toContain(
    'User panel',
  );
  expect(w.find('nav a[href="/account"]').exists()).toBe(false);
  expect(w.find('[aria-label*="Change language"]').exists()).toBe(false);
  expect(localStorage.getItem('runa.locale')).toBe('pl');
  expect(
    vi
      .mocked(window.fetch)
      .mock.calls.some(([input]) => String(input).includes('/auth/session')),
  ).toBe(false);
  session.paused.value = true;
  await flushPromises();
  expect(w.text()).not.toContain('No dashboard widgets');
  w.unmount();
});

it('inherits partial presentation providers and refuses missing or mismatched component builds before a loader', () => {
  const custom = defineComponent({ render: () => h('p', 'Custom') });
  let resolved: ReturnType<typeof useAdminPresentation> | undefined;
  const consumer = defineComponent({
    setup() {
      resolved = useAdminPresentation();
      return () => h('p');
    },
  });
  mount(AdminPresentationProvider, {
    props: { components: { layout: custom } },
    slots: {
      default: () =>
        h(
          AdminPresentationProvider,
          { components: { page: custom } },
          { default: () => h(consumer) },
        ),
    },
  });
  expect(resolved!.value.layout).toBe(custom);
  expect(resolved!.value.page).toBe(custom);
  Object.assign(builds.owners, {
    app: {
      manifest_hash: null,
      build_id: 'a'.repeat(64),
      components: ['app.example'],
      sources: {},
    },
  });
  const component = {
    key: 'app.example',
    owner: 'app',
    manifest_hash: null,
    build_id: 'a'.repeat(64),
  };
  expect(
    resolveAdminComponent(component, {
      'app.example': { buildId: 'a'.repeat(64), component: custom },
    }).available,
  ).toBe(true);
  expect(
    resolveAdminComponent({ ...component, build_id: null }).available,
  ).toBe(false);
  expect(
    resolveAdminComponent({ ...component, build_id: 'b'.repeat(64) }).available,
  ).toBe(false);
  expect(
    resolveAdminComponent(component, {
      'app.example': { buildId: 'b'.repeat(64), component: custom },
    }).available,
  ).toBe(false);
  expect(resolveAdminComponent(null)).toEqual({
    component: undefined,
    available: true,
  });
});

it('Admin entry proof diagnostic submits the fixed native action once and clears transient credentials', async () => {
  const session = sessionFixture();
  const bodies: string[] = [];
  let confirmed = false;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (String(input) === '/api/runa/auth/step-up') {
      bodies.push(String(init?.body));
      confirmed = true;
      return respond({ data: { confirmed: true } });
    }
    return confirmed
      ? respond({ data: bootstrap() })
      : respond({ reason: 'step_up_required' }, 403);
  });
  let admin!: AdminController;
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate: vi.fn() });
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(admin.state.value).toBe('proof_required'));
  admin.entryProof.password.value = 'Transient password';
  await Promise.all([admin.confirmEntry(), admin.confirmEntry()]);
  expect(bodies).toEqual([
    JSON.stringify({
      action: 'runa.admin.enter',
      requirement: 'password',
      current_password: 'Transient password',
    }),
  ]);
  expect(admin.entryProof.password.value).toBe('');
  expect(admin.state.value).toBe('ready');
  session.paused.value = true;
  expect(admin.bootstrap.value).toBeUndefined();
});

it('uses authorized locations and passes declared module pages to top navigation without an invented Overview', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  const session = sessionFixture(),
    navigate = vi.fn();
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin/app/settings')
      return respond({
        data: {
          schema_version: 1,
          owner: 'app',
          key: 'settings',
          title: 'Application settings',
          generation: 7,
          revision: 'page-1',
          component: null,
          sections: [],
        },
      });
    return respond({
      data: String(input) === '/api/admin' ? bootstrap() : { available: true },
    });
  });
  const w = mount(AdminApplication, { props: { session, navigate } });
  await vi.waitFor(() =>
    expect(w.text()).toContain('No sections are available'),
  );
  const shell = w.getComponent(ApplicationSessionShell);
  expect(shell.props('topNavigation')?.map((n) => n.label)).toEqual([
    'Settings',
  ]);
  expect(w.text()).not.toContain('Overview');
  let admin!: AdminController;
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate });
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(admin.state.value).toBe('ready'));
  expect(await admin.navigate({ owner: 'app', page: 'missing' })).toBe(false);
  expect(await admin.navigate({ owner: 'app', page: 'settings' })).toBe(true);
  expect(navigate).toHaveBeenCalledExactlyOnceWith('/admin/app/settings');
});

it('keeps full customer Admin content behind the public session and entry controllers', async () => {
  const session = sessionFixture();
  const wrapper = mount(CustomerAdmin, { props: { session } });
  await vi.waitFor(() =>
    expect(wrapper.find('[data-customer-admin]').exists()).toBe(true),
  );
  expect(wrapper.text()).toContain('Application settings');
  session.pending.value = true;
  await flushPromises();
  expect(wrapper.find('[data-customer-admin]').exists()).toBe(false);
  expect(session.logout).not.toHaveBeenCalled();
});

function resourcePage(): AdminPageData {
  return {
    schema_version: 1,
    owner: 'app',
    key: 'settings',
    title: 'Settings',
    generation: 7,
    revision: 'page-1',
    component: null,
    sections: [
      {
        key: 'profile',
        view: 'record',
        title: 'Profile',
        readonly: true,
        required: true,
        component: null,
        diagnostic: null,
        commands: [],
        record: null,
        fields: [
          {
            key: 'name',
            type: 'text',
            label: 'Name',
            readonly: true,
            sensitive: false,
            filterable: true,
            sortable: true,
            lookup: false,
            required: false,
            max_length: 80,
            choices: [],
            multiple: false,
            component: null,
          },
          {
            key: 'reference',
            type: 'uuid',
            label: 'Reference',
            readonly: true,
            sensitive: false,
            filterable: false,
            sortable: false,
            lookup: true,
            required: false,
            max_length: null,
            choices: [],
            multiple: false,
            component: null,
          },
          {
            key: 'token',
            type: 'text',
            label: 'Token',
            readonly: true,
            sensitive: true,
            filterable: false,
            sortable: false,
            lookup: false,
            required: false,
            max_length: null,
            choices: [],
            multiple: false,
            component: null,
          },
        ],
      },
    ],
  };
}

function resourceHarness() {
  const session = sessionFixture();
  let resource!: AdminResourceController, page!: AdminPageController;
  const location = ref<AdminLocation>({ owner: 'app', page: 'settings' });
  const wrapper = mount(
    defineComponent({
      setup() {
        const admin = useAdmin({ session, navigate: vi.fn() });
        page = useAdminPage(admin, location);
        resource = useAdminResource(page, 'profile');
        return () => h('div');
      },
    }),
  );
  return { session, location, wrapper, resource, page };
}

function tableHarness(presentation: Component = AdminResourceTable) {
  const session = sessionFixture();
  let table!: AdminTableController, page!: AdminPageController;
  const wrapper = mount(
    defineComponent({
      setup() {
        const admin = useAdmin({ session, navigate: vi.fn() });
        page = useAdminPage(admin, ref({ owner: 'app', page: 'settings' }));
        table = useAdminTable(page, 'profile');
        return () => h(presentation, { controller: table });
      },
    }),
  );
  return { table, page, session, wrapper };
}

it('Admin cursor tables preserve owner pagination sorting and secret state without local dataset processing', async () => {
  const queries: URLSearchParams[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    queries.push(url.searchParams);
    return respond({
      data: {
        items: [
          {
            public_id: identity,
            revision: 'r1',
            values: {
              name: url.searchParams.has('cursor') ? 'Second' : 'First',
              token: { set: true },
            },
          },
        ],
        next: url.searchParams.has('cursor') ? null : 'opaque+/next',
        total: 900,
      },
    });
  });
  const { table, wrapper } = tableHarness();
  await vi.waitFor(() => expect(wrapper.text()).toContain('First'));
  expect(wrapper.text()).toContain('Set');
  expect(wrapper.text()).not.toContain('[object Object]');
  expect(wrapper.find('input[type="search"]').exists()).toBe(false);
  expect(queries).toHaveLength(1);
  const signal = new AbortController().signal;
  const initial = await table.provider.value(table.query.value, signal);
  expect(initial).toMatchObject({
    counts: 'unknown',
    total: 900,
    hasNext: true,
  });
  const next = wrapper
    .findAll('button')
    .find((button) => button.text() === 'Next')!;
  await next.trigger('click');
  await vi.waitFor(() => expect(wrapper.text()).toContain('Second'));
  expect(queries[1]!.get('cursor')).toBe('opaque+/next');
  table.query.value = {
    ...table.query.value,
    sort: { key: 'name', direction: 'desc' },
  };
  await vi.waitFor(() => expect(queries).toHaveLength(3));
  expect(table.query.value.page).toBe(1);
  expect(queries[2]!.has('cursor')).toBe(false);
  expect(queries[2]!.get('sort[0][key]')).toBe('name');
  expect(queries[2]!.get('sort[0][direction]')).toBe('desc');
  table.query.value = { ...table.query.value, page: 400 };
  await flushPromises();
  expect(table.query.value.page).toBe(1);
  expect(queries.every((q) => !q.has('page'))).toBe(true);
});

it('Admin cursor tables type filters refuse invalid input and reset cursors on authority changes', async () => {
  const queries: URLSearchParams[] = [];
  const data = resourcePage();
  data.sections[0]!.fields.push({
    ...data.sections[0]!.fields[0]!,
    key: 'age',
    label: 'Age',
    type: 'integer',
  });
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') return respond({ data });
    queries.push(url.searchParams);
    return respond({
      data: {
        items: [
          { public_id: identity, revision: 'r1', values: { name: 'Visible' } },
        ],
        next: 'next',
        total: null,
      },
    });
  });
  const { table, session, wrapper } = tableHarness();
  await vi.waitFor(() => expect(wrapper.text()).toContain('Visible'));
  table.query.value = { ...table.query.value, filters: { age: '18' } };
  await vi.waitFor(() => expect(queries).toHaveLength(2));
  expect(JSON.parse(queries[1]!.get('filters')!)).toEqual({ age: 18 });
  table.query.value = { ...table.query.value, filters: { age: 'invalid' } };
  await vi.waitFor(() =>
    expect(table.error.value?.reason).toBe('admin_input_invalid'),
  );
  expect(queries).toHaveLength(2);
  expect(wrapper.text()).toContain('Enter a valid integer filter.');
  table.query.value = {
    ...table.query.value,
    filters: { name: 'Sensitive filter' },
  };
  await vi.waitFor(() => expect(queries).toHaveLength(3));
  session.authorityRevision.value++;
  await vi.waitFor(() => expect(table.query.value.filters).toEqual({}));
  await vi.waitFor(() => expect(queries.length).toBeGreaterThan(3));
  expect(queries.at(-1)!.has('cursor')).toBe(false);
  expect(JSON.parse(queries.at(-1)!.get('filters')!)).toEqual({});
});

it('Admin cursor tables reject missing and duplicate public row identities', async () => {
  let ids: (string | null)[] = [null];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    return respond({
      data: {
        items: ids.map((id) => ({
          public_id: id,
          revision: 'r1',
          values: { name: 'Hidden invalid row' },
        })),
        next: null,
        total: null,
      },
    });
  });
  const { table, wrapper } = tableHarness();
  await vi.waitFor(() => expect(table.resource.result.value).toBeDefined());
  await expect(
    table.provider.value(table.query.value, new AbortController().signal),
  ).rejects.toThrow('Invalid Admin collection identity');
  expect(wrapper.text()).not.toContain('Hidden invalid row');
  ids = [identity, identity];
  await table.refresh();
  await expect(
    table.provider.value(table.query.value, new AbortController().signal),
  ).rejects.toThrow('Invalid Admin collection identity');
  expect(wrapper.text()).not.toContain('Hidden invalid row');
});

it('Admin cursor tables compose public customer filter cell and row slots', async () => {
  const queries: URLSearchParams[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    queries.push(url.searchParams);
    return respond({
      data: {
        items: [
          { public_id: identity, revision: 'r1', values: { name: 'Visible' } },
        ],
        next: null,
        total: null,
      },
    });
  });
  const { wrapper } = tableHarness(CustomerAdminTable);
  await vi.waitFor(() =>
    expect(wrapper.find('strong').text()).toBe('Customer: Visible'),
  );
  expect(wrapper.find('[data-public-id]').attributes('data-public-id')).toBe(
    identity,
  );
  await wrapper.find('input').setValue('Another');
  await vi.waitFor(() => expect(queries).toHaveLength(2));
  expect(JSON.parse(queries[1]!.get('filters')!)).toEqual({ name: 'Another' });
  expect(queries[1]!.has('cursor')).toBe(false);
});

it('Admin resource reads preserve typed GET filters and bounded field lookup identity', async () => {
  const queries: URLSearchParams[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    queries.push(url.searchParams);
    return respond({
      data:
        url.searchParams.get('kind') === 'options'
          ? {
              field: 'reference',
              items: [{ value: identity, label: 'Owner' }],
              next: 'opaque+cursor',
            }
          : {
              items: [
                {
                  public_id: identity,
                  revision: 'record-1',
                  values: { name: 'Visible', token: { set: true } },
                },
              ],
              next: null,
              total: null,
            },
    });
  });
  const { resource, page } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  await resource.read({
    kind: 'page',
    limit: 7,
    filters: { name: null, flag: false, count: 12, empty: '' },
    sort: [{ key: 'name', direction: 'asc' }],
  });
  expect(resource.result.value).toMatchObject({
    total: null,
    items: [{ values: { token: { set: true } } }],
  });
  expect(JSON.parse(queries[0]!.get('filters')!)).toEqual({
    name: null,
    flag: false,
    count: 12,
    empty: '',
  });
  expect(queries[0]!.get('sort[0][key]')).toBe('name');
  await resource.read({
    kind: 'options',
    field: 'reference',
    cursor: 'opaque+cursor',
  });
  expect(resource.result.value).toMatchObject({
    field: 'reference',
    items: [{ value: identity }],
    next: 'opaque+cursor',
  });
  expect(queries[1]!.get('cursor')).toBe('opaque+cursor');
  const calls = queries.length;
  await resource.read({ kind: 'options', field: 'hidden' });
  expect(queries).toHaveLength(calls);
  expect(resource.result.value).toBeUndefined();
  expect(resource.error.value?.message).not.toContain('hidden');
  await resource.read({ kind: 'options', field: 'reference' });
  expect(resource.result.value).toHaveProperty('field', 'reference');
});

it('Admin resource reads coalesce requests and fence page account authority and disposal races', async () => {
  const finishes: ((value: Response) => void)[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin') return respond({ data: bootstrap() });
    if (String(input) === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    return new Promise((resolve) => {
      finishes.push(resolve);
    });
  });
  const { resource, page, session, wrapper } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  const record = (name: string) =>
    respond({
      data: { public_id: identity, revision: 'r1', values: { name } },
    });
  const first = resource.read({ kind: 'record', public_id: identity });
  const same = resource.read({ kind: 'record', public_id: identity });
  expect(same).toBe(first);
  await flushPromises();
  expect(finishes).toHaveLength(1);
  session.paused.value = true;
  finishes[0]!(record('Old account'));
  await first;
  expect(resource.result.value).toBeUndefined();
  expect(resource.busy.value).toBe(false);
  session.paused.value = false;
  await vi.waitFor(() => expect(finishes.length).toBe(2));
  finishes[1]!(record('Current'));
  await vi.waitFor(() =>
    expect(resource.result.value).toMatchObject({
      values: { name: 'Current' },
    }),
  );
  const stale = resource.read({
    kind: 'record',
    public_id: identity,
    limit: 1,
  });
  await flushPromises();
  session.authorityRevision.value++;
  finishes[2]!(record('Old authority'));
  await stale;
  expect(resource.result.value).toBeUndefined();
  await vi.waitFor(() => expect(finishes.length).toBe(4));
  wrapper.unmount();
  finishes[3]!(record('Disposed'));
  await flushPromises();
  expect(resource.result.value).toBeUndefined();
});

it('Admin resource reads reject foreign subjects denied fields raw secrets and mismatched options without disclosure', async () => {
  let data: unknown;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin') return respond({ data: bootstrap() });
    if (String(input) === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    return respond({ data });
  });
  const { resource, page } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  for (const invalid of [
    {
      public_id: '01900000-0000-7000-8000-000000000002',
      revision: 'r1',
      values: {},
    },
    {
      public_id: identity,
      revision: 'r1',
      values: { hidden: 'private-value' },
    },
    {
      public_id: identity,
      revision: 'r1',
      values: { token: 'private-secret' },
    },
    { public_id: identity, revision: 'r1', values: { name: 12 } },
  ]) {
    data = invalid;
    await resource.read({ kind: 'record', public_id: identity });
    expect(resource.result.value).toBeUndefined();
    expect(resource.state.value).toBe('failed');
    expect(JSON.stringify(resource.error.value)).not.toContain('private-');
  }
  data = {
    field: 'hidden',
    items: [{ value: identity, label: 'Private label' }],
    next: null,
  };
  await resource.read({ kind: 'options', field: 'reference' });
  expect(resource.result.value).toBeUndefined();
  const calls = vi.mocked(window.fetch).mock.calls.length;
  expect(
    await resource.command({
      command: 'update',
      public_id: identity,
      revision: 'r1',
      values: {},
    }),
  ).toBeUndefined();
  expect(vi.mocked(window.fetch).mock.calls).toHaveLength(calls);
  expect(resource.error.value?.reason).toBe('admin_operation_unavailable');
});

it('Admin page refresh retains the mounted page while a same-session descriptor read is pending', async () => {
  let finish!: (value: Response) => void;
  let slow = false;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin') return respond({ data: bootstrap() });
    if (String(input) === '/api/admin/app/settings')
      return slow
        ? new Promise((resolve) => {
            finish = resolve;
          })
        : respond({ data: resourcePage() });
    return respond({
      data: { public_id: identity, revision: 'r1', values: {} },
    });
  });
  const { page, session } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  const initial = page.page.value;
  slow = true;
  const refresh = page.refresh();
  expect(page.state.value).toBe('ready');
  expect(page.page.value).toBe(initial);
  finish(respond({ data: resourcePage() }));
  await refresh;
  session.paused.value = true;
  expect(page.page.value).toBeUndefined();
});

it('Admin resource reads discard obsolete queries and refresh after readable section changes', async () => {
  const replies: ((value: Response) => void)[] = [];
  let descriptor = resourcePage();
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin') return respond({ data: bootstrap() });
    if (String(input) === '/api/admin/app/settings')
      return respond({ data: descriptor });
    return new Promise((resolve) => {
      replies.push(resolve);
    });
  });
  const { resource, page, session } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  const older = resource.read({
    kind: 'record',
    public_id: identity,
    filters: { name: 'old' },
  });
  await vi.waitFor(() => expect(replies.length).toBe(1));
  const latest = resource.read({
    kind: 'record',
    public_id: identity,
    filters: { name: 'new' },
  });
  await vi.waitFor(() => expect(replies.length).toBe(2));
  replies[1]!(
    respond({
      data: {
        public_id: identity,
        revision: 'new',
        values: { name: 'Current' },
      },
    }),
  );
  await latest;
  replies[0]!(
    respond({
      data: { public_id: identity, revision: 'old', values: { name: 'Old' } },
    }),
  );
  await older;
  expect(resource.result.value).toMatchObject({ revision: 'new' });
  descriptor = { ...resourcePage(), revision: 'page-2' };
  descriptor.sections[0]!.fields = [];
  await page.refresh();
  expect(resource.result.value).toBeUndefined();
  await vi.waitFor(() => expect(replies.length).toBe(3));
  // Even an old response from a faulty backend cannot reintroduce a revoked field.
  replies[2]!(
    respond({
      data: {
        public_id: identity,
        revision: 'new',
        values: { name: 'Revoked' },
      },
    }),
  );
  await vi.waitFor(() => expect(resource.state.value).toBe('failed'));
  expect(resource.result.value).toBeUndefined();
  session.session.value = {
    ...session.session.value!,
    public_id: '01900000-0000-7000-8000-000000000002',
  };
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  await flushPromises();
  expect(replies).toHaveLength(3);
  expect(resource.result.value).toBeUndefined();
  expect(resource.state.value).toBe('ready');
});

it('Admin automatic records display only readable values and secret state with one native read lane', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  const session = sessionFixture();
  const queries: URLSearchParams[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') {
      const data = resourcePage();
      data.sections[0]!.record = {
        public_id: identity,
        revision: 'r0',
        values: {},
      };
      return respond({ data });
    }
    queries.push(url.searchParams);
    return respond({
      data: {
        public_id: identity,
        revision: 'r1',
        values: { name: 'Readable record', token: { set: true } },
      },
    });
  });
  const wrapper = mount(AdminApplication, { props: { session } });
  await vi.waitFor(() =>
    expect(wrapper.findComponent(AdminForm).exists()).toBe(true),
  );
  await vi.waitFor(() =>
    expect(
      wrapper.getComponent(AdminForm).props('controller').values.value[
        'profile.name'
      ],
    ).toBe('Readable record'),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Readable record'));
  expect(queries).toHaveLength(1);
  expect(queries[0]!.get('kind')).toBe('record');
  expect(queries[0]!.get('public_id')).toBe(identity);
  expect(wrapper.text()).toContain('Set');
  expect(wrapper.text()).not.toContain('Reference');
  expect(wrapper.find('input:not([type="search"])').exists()).toBe(false);
  session.pending.value = true;
  await flushPromises();
  expect(wrapper.text()).not.toContain('Readable record');
  expect(queries).toHaveLength(1);
});

it('Admin dirty navigation retains the current page until explicit approval through the public controller', async () => {
  const session = sessionFixture(),
    go = vi.fn();
  let admin!: AdminController, form!: ReturnType<typeof useAdminForm>;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const path = new URL(String(input), location.origin).pathname;
    if (path === '/api/admin') return respond({ data: bootstrap() });
    if (path === '/api/admin/app/settings') {
      const data = resourcePage();
      data.sections[0]!.readonly = false;
      data.sections[0]!.fields[0]!.readonly = false;
      return respond({ data });
    }
    return respond({
      data: {
        public_id: identity,
        revision: 'r1',
        values: { name: 'Before', token: { set: true } },
      },
    });
  });
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate: go });
        form = useAdminForm(
          useAdminPage(admin, ref({ owner: 'app', page: 'settings' })),
        );
        return () => h(AdminForm, { controller: form });
      },
    }),
  );
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  const cancelled = admin.navigate({ owner: 'app', page: 'settings' });
  await vi.waitFor(() => expect(form.confirmation.value).toBeDefined());
  form.confirm(false);
  expect(await cancelled).toBe(false);
  expect(go).not.toHaveBeenCalled();
  expect(form.values.value['profile.name']).toBe('Draft');
  const accepted = admin.navigate({ owner: 'app', page: 'settings' });
  await vi.waitFor(() => expect(form.confirmation.value).toBeDefined());
  form.confirm(true);
  expect(await accepted).toBe(true);
  expect(go).toHaveBeenCalledTimes(1);
});

it('Admin automatic records refuse raw secrets and recover only through an explicit read retry', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  let reads = 0;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    expect(init?.method ?? 'GET').toBe('GET');
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    reads++;
    return respond({
      data: {
        public_id: identity,
        revision: 'r1',
        values: {
          name: 'Safe record',
          token: reads === 1 ? 'forbidden-raw-secret' : { set: false },
        },
      },
    });
  });
  const wrapper = mount(AdminApplication, {
    props: { session: sessionFixture() },
  });
  await vi.waitFor(() => expect(wrapper.text()).toContain('Retry'));
  expect(wrapper.html()).not.toContain('forbidden-raw-secret');
  expect(wrapper.text()).not.toContain('Safe record');
  expect(reads).toBe(1);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Retry loading')!
    .trigger('click');
  await vi.waitFor(() => expect(wrapper.text()).toContain('Safe record'));
  expect(wrapper.text()).toContain('Not set');
  expect(reads).toBe(2);
});

it('Admin automatic tables dispatch declared collections once and clear rows on session loss', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  const session = sessionFixture();
  const queries: URLSearchParams[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') {
      const data = resourcePage();
      data.sections[0]!.view = 'table';
      return respond({ data });
    }
    queries.push(url.searchParams);
    return respond({
      data: {
        items: [
          {
            public_id: identity,
            revision: 'r1',
            values: { name: 'Automatic record', token: { set: true } },
          },
        ],
        next: null,
        total: null,
      },
    });
  });
  const wrapper = mount(AdminApplication, { props: { session } });
  await vi.waitFor(() => expect(wrapper.text()).toContain('Automatic record'));
  expect(queries).toHaveLength(1);
  expect(queries[0]!.get('kind')).toBe('page');
  expect(wrapper.text()).toContain('Set');
  expect(wrapper.find('table').exists()).toBe(true);
  session.pending.value = true;
  await flushPromises();
  expect(wrapper.text()).not.toContain('Automatic record');
});

it('Admin automatic tables give replacements the page controller without starting a default read', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  const reads = vi.fn();
  let suppliedPage: AdminPageController | undefined;
  const section = defineComponent({
    props: {
      section: {
        type: Object as PropType<AdminSectionPresentationProps['section']>,
        required: true,
      },
      page: {
        type: Object as PropType<AdminSectionPresentationProps['page']>,
        required: true,
      },
    },
    setup(props) {
      suppliedPage = props.page;
      return () => h('p', 'Customer section');
    },
  });
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') {
      const data = resourcePage();
      data.sections[0]!.view = 'table';
      return respond({ data });
    }
    reads();
    return respond({}, 404);
  });
  const session = sessionFixture();
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(
          AdminPresentationProvider,
          { components: { section } },
          {
            default: () => h(AdminApplication, { session }),
          },
        ),
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Customer section'));
  expect(suppliedPage?.page.value?.sections[0]?.view).toBe('table');
  expect(reads).not.toHaveBeenCalled();
  expect(wrapper.find('table').exists()).toBe(false);
});

it('Admin automatic tables isolate unavailable sections before mounting replacements or reading collections', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  const reads = vi.fn(),
    replaced = vi.fn();
  const section = defineComponent({
    setup() {
      replaced();
      return () => h('p', 'Custom');
    },
  });
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') {
      const data = resourcePage();
      data.sections[0]!.view = 'table';
      data.sections[0]!.diagnostic = 'admin_binding_unavailable';
      return respond({ data });
    }
    reads();
    return respond({}, 404);
  });
  const session = sessionFixture();
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(
          AdminPresentationProvider,
          { components: { section } },
          {
            default: () => h(AdminApplication, { session }),
          },
        ),
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('This section is currently unavailable.'),
  );
  expect(reads).not.toHaveBeenCalled();
  expect(replaced).not.toHaveBeenCalled();
});

it('Admin automatic tables refuse unknown presentation intent before a resource request', async () => {
  const data = resourcePage();
  Object.assign(data.sections[0]!, { view: ['record'] });
  const reads = vi.fn();
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') return respond({ data });
    reads();
    return respond({}, 404);
  });
  const { page } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('failed'));
  expect(page.page.value).toBeUndefined();
  expect(reads).not.toHaveBeenCalled();
});

it('Admin automatic tables never fall back from a missing declared component to a provider or collection', async () => {
  window.history.replaceState({}, '', '/admin/app/settings');
  const replaced = vi.fn(),
    reads = vi.fn();
  const section = defineComponent({
    setup() {
      replaced();
      return () => h('p', 'Custom');
    },
  });
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
    if (url.pathname === '/api/admin/app/settings') {
      const data = resourcePage();
      data.sections[0]!.view = 'table';
      data.sections[0]!.component = {
        key: 'app.missing',
        owner: 'app',
        manifest_hash: null,
        build_id: null,
      };
      return respond({ data });
    }
    reads();
    return respond({}, 404);
  });
  const session = sessionFixture();
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(
          AdminPresentationProvider,
          { components: { section } },
          {
            default: () => h(AdminApplication, { session }),
          },
        ),
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain(
      'This administration component is unavailable.',
    ),
  );
  expect(replaced).not.toHaveBeenCalled();
  expect(reads).not.toHaveBeenCalled();
});
