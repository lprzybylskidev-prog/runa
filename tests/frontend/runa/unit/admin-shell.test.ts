/* eslint-disable vue/one-component-per-file -- Independent mounted controller and presentation fixtures. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import {
  computed,
  defineAsyncComponent,
  defineComponent,
  h,
  ref,
  shallowRef,
} from 'vue';
import type { Component, PropType } from 'vue';
import {
  AdminApplication,
  AdminField,
  useAdminLocale,
  adminText,
  validateAdminField,
  AdminForm,
  AdminPresentationProvider,
  resolveAdminComponent,
  useAdmin,
  useAdminOperations,
  useAdminApplicationSettings,
  useAdminForm,
  useAdminPage,
  useAdminPageContext,
  useAdminResource,
  useAdminTable,
  AdminResourceTable,
  useAdminPresentation,
} from '@/runa/admin';
import {
  useApplicationSession,
  ApplicationSessionShell,
  AuthOperationProofPage,
  AuthPresentationProvider,
  useOperationProof,
} from '@/runa/auth';
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

import AdminReadiness from '../../../../src/runa/modules/admin/frontend/AdminReadiness.vue';
import AdminComponentBoundary from '../../../../src/runa/modules/admin/frontend/AdminComponentBoundary.vue';
import { useAdminInitialReadiness } from '../../../../src/runa/modules/admin/frontend/initialReadiness';
import CustomerAdmin from './fixtures/customization/CustomerAdmin.vue';
import CustomerAdminTable from './fixtures/customization/CustomerAdminTable.vue';
import CustomerAdminSection from './fixtures/customization/CustomerAdminSection.vue';
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
    refreshing: ref(false),
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
    activity: { remaining_seconds: 300, heartbeat_seconds: 30 },
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
  localStorage.setItem(
    'runa.preferences:runa:display:global:runa.locale',
    JSON.stringify({ version: 1, value: 'en' }),
  );
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const path = String(input);
    if (path === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (path === '/api/admin') return respond({ data: bootstrap() });
    if (path === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 300, heartbeat_seconds: 30 },
      });
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

it('uses one supplied session, follows the English application preference and places Admin entry only in the account menu', async () => {
  const session = sessionFixture();
  const w = mount(AdminApplication, {
    props: { session },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.find('[data-admin-content]').exists()).toBe(true),
  );
  expect(w.text()).not.toContain('No dashboard widgets');
  const shell = w.getComponent(ApplicationSessionShell);
  expect(shell.props('session')).toBe(session);
  expect(shell.props('navigation')?.map((n) => n.label)).toEqual([
    'Administrator panel',
    'Application settings',
  ]);
  const button = w
    .findAll('button')
    .find((b) => b.attributes('aria-label')?.startsWith('User menu:'))!;
  expect(button).toBeTruthy();
  await vi.waitFor(() => expect(button.attributes('disabled')).toBeUndefined());
  await button.trigger('click');
  await flushPromises();
  const menu = document.querySelector('.account-menu')!;
  expect(menu.querySelector('a[href="/admin"]')?.textContent).toContain(
    'Administrator panel',
  );
  expect(menu.querySelector('a[href="/account"]')?.textContent).toContain(
    'User panel',
  );
  expect(w.find('nav a[href="/account"]').exists()).toBe(false);
  expect(w.find('[aria-label*="Change language"]').exists()).toBe(false);
  expect(
    JSON.parse(
      localStorage.getItem('runa.preferences:runa:display:global:runa.locale')!,
    ).value,
  ).toBe('en');
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

it.each([false, true])(
  'composes a customer section with the public form owner inside a full replacement: %s',
  async (full) => {
    window.history.replaceState({}, '', '/admin/app/settings');
    vi.mocked(window.fetch).mockImplementation(async (input) => {
      const url = new URL(String(input), window.location.origin);
      if (url.pathname === '/api/admin/entry')
        return respond({ data: { available: true } });
      if (url.pathname === '/api/admin') return respond({ data: bootstrap() });
      if (url.pathname === '/api/admin/app/settings') {
        const data = resourcePage();
        data.sections[0]!.fields = data.sections[0]!.fields.slice(0, 1);
        return respond({ data });
      }
      return respond({
        data: {
          public_id: null,
          revision: 'r1',
          values: { name: 'Customer-visible record' },
        },
      });
    });
    const session = sessionFixture();
    const wrapper = mount(
      defineComponent({
        setup: () => () =>
          h(
            AdminPresentationProvider,
            { components: { section: CustomerAdminSection } },
            {
              default: () =>
                h(full ? CustomerAdmin : AdminApplication, { session }),
            },
          ),
      }),
    );
    await vi.waitFor(() =>
      expect(wrapper.find('[data-customer-section]').exists()).toBe(true),
    );
    await vi.waitFor(() =>
      expect(wrapper.text()).toContain('Customer-visible record'),
    );
    expect(wrapper.find('input').exists()).toBe(false);
    const section = wrapper.getComponent(CustomerAdminSection);
    expect(section.props('controller').sections.value[0]?.key).toBe('profile');
    expect(wrapper.find('[data-customer-page]').exists()).toBe(full);
    session.paused.value = true;
    await flushPromises();
    expect(wrapper.find('[data-customer-section]').exists()).toBe(false);
    expect(session.logout).not.toHaveBeenCalled();
  },
);

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
    counts: 'exact',
    pagination: 'cursor',
    total: 900,
    hasNext: true,
  });
  expect(wrapper.text()).toContain('Results: 900');
  expect(wrapper.text()).not.toContain('Count unconfirmed');
  expect(wrapper.find('nav[aria-label="Page"]').exists()).toBe(false);
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

it('Admin cursor tables type filters refuse invalid input and retain filters while resetting cursors on revalidation', async () => {
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
  expect(document.body.textContent).toContain('Enter a valid integer filter.');
  table.query.value = {
    ...table.query.value,
    filters: { name: 'Sensitive filter' },
  };
  await vi.waitFor(() => expect(queries).toHaveLength(3));
  session.authorityRevision.value++;
  await vi.waitFor(() =>
    expect(table.query.value.filters).toEqual({ name: 'Sensitive filter' }),
  );
  await vi.waitFor(() => expect(queries.length).toBeGreaterThan(3));
  expect(queries.at(-1)!.has('cursor')).toBe(false);
  expect(JSON.parse(queries.at(-1)!.get('filters')!)).toEqual({
    name: 'Sensitive filter',
  });
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
    if (String(input) === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 900, heartbeat_seconds: 30 },
      });
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
  expect(resource.result.value).toMatchObject({ values: { name: 'Current' } });
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
    if (String(input) === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 900, heartbeat_seconds: 30 },
      });
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
    if (String(input) === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 900, heartbeat_seconds: 30 },
      });
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
  const descriptorReads = () =>
    vi
      .mocked(window.fetch)
      .mock.calls.filter(([url]) => String(url) === '/api/admin/app/settings')
      .length;
  const beforeActivity = descriptorReads();
  session.session.value = {
    ...session.session.value!,
    session: {
      ...session.session.value!.session,
      activity_at: new Date(Date.now() + 1000).toISOString(),
    },
  };
  await flushPromises();
  expect(descriptorReads()).toBe(beforeActivity);
  expect(page.page.value).toBe(initial);
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
    if (String(input) === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 900, heartbeat_seconds: 30 },
      });
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
    if (path === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 300, heartbeat_seconds: 30 },
      });
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

it('Admin admitted revalidation retains the mounted dirty editor and drops it on access refusal', async () => {
  const session = sessionFixture();
  let admin!: AdminController, form!: ReturnType<typeof useAdminForm>;
  let delay = false,
    finish!: (response: Response) => void;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const path = new URL(String(input), location.origin).pathname;
    if (path === '/api/admin') {
      if (delay)
        return new Promise<Response>((resolve) => {
          finish = resolve;
        });
      return respond({ data: bootstrap() });
    }
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
  const w = mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate: vi.fn() });
        form = useAdminForm(
          useAdminPage(admin, ref({ owner: 'app', page: 'settings' })),
        );
        return () =>
          h(
            AdminForm,
            { controller: form },
            {
              default: () =>
                h('input', {
                  type: 'text',
                  value: form.values.value['profile.name'],
                }),
            },
          );
      },
    }),
    { attachTo: document.body },
  );
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  await vi.waitFor(() =>
    expect(w.find('input[type="text"]').exists()).toBe(true),
  );
  const input = w.get('input[type="text"]');
  (input.element as HTMLInputElement).focus();
  delay = true;
  const explicit = admin.refresh();
  expect(admin.state.value).toBe('ready');
  finish(respond({ data: bootstrap() }));
  await explicit;
  expect(form.values.value['profile.name']).toBe('Draft');
  session.authorityRevision.value++;
  await flushPromises();
  expect(admin.state.value).toBe('ready');
  expect(form.values.value['profile.name']).toBe('Draft');
  expect(w.get('input[type="text"]').element).toBe(input.element);
  expect(document.activeElement).toBe(input.element);
  finish(
    new Response(JSON.stringify({ reason: 'access_denied' }), {
      status: 403,
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  await vi.waitFor(() => expect(admin.state.value).toBe('forbidden'));
  expect(form.values.value).toEqual({});
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

it('Admin resource clear cancels old subject reads and erases the retained query', async () => {
  const replies: ((value: Response) => void)[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin') return respond({ data: bootstrap() });
    if (String(input) === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 900, heartbeat_seconds: 30 },
      });
    if (String(input) === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    return new Promise((resolve) => {
      replies.push(resolve);
    });
  });
  const { resource, page } = resourceHarness();
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  const pending = resource.read({ kind: 'record', public_id: identity });
  await vi.waitFor(() => expect(replies.length).toBe(1));
  resource.clear();
  replies[0]!(
    respond({
      data: {
        public_id: identity,
        revision: 'old',
        values: { name: 'Previous subject' },
      },
    }),
  );
  await pending;
  expect(resource.result.value).toBeUndefined();
  document.dispatchEvent(new Event('visibilitychange'));
  await flushPromises();
  expect(replies).toHaveLength(1);
});

it('application settings retain drafts after confirmed Audit refusal while real Core loss closes the shell', async () => {
  let reason = 'application_settings_audit_unavailable';
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const path = String(input);
    if (path === '/api/admin') return respond({ data: bootstrap() });
    if (path === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 300, heartbeat_seconds: 30 },
      });
    if (path.endsWith('/application'))
      return respond({
        data: {
          revision: 1,
          values: { 'presentation.name': 'Source name' },
          definitions: {
            'presentation.name': {
              title: 'Displayed application name',
              type: 'string',
              max_length: 160,
              choices: [],
              writable: true,
            },
          },
        },
      });
    return respond({ reason, scope: 'operation' }, 503);
  });
  let admin!: AdminController;
  let settings!: ReturnType<typeof useAdminApplicationSettings>;
  const session = sessionFixture();
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session });
        const operations = useAdminOperations(admin);
        settings = useAdminApplicationSettings(operations);
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(admin.state.value).toBe('ready'));
  await settings.refresh();
  settings.edit('presentation.name', 'Uncommitted draft');
  const mutationCalls = () =>
    fetch.mock.calls.filter(([, init]) => init?.method === 'POST').length;
  const calls = mutationCalls();
  await settings.save();
  await flushPromises();
  expect(admin.state.value).toBe('ready');
  expect(settings.draft.value['presentation.name']).toBe('Uncommitted draft');
  expect(settings.operations.dirty.value).toBe(true);
  expect(settings.operations.error.value).not.toBe('');
  expect(mutationCalls()).toBe(calls + 1);
  reason = 'module_unavailable';
  await settings.save();
  await flushPromises();
  expect(admin.state.value).toBe('unavailable');
  expect(settings.draft.value).toEqual({});
  expect(mutationCalls()).toBe(calls + 2);
});

it('Admin entry proof renders Auth MinimalLayout before admission and retries without duplicating password requests', async () => {
  window.history.replaceState({}, '', '/admin');
  let confirmed = false,
    attempts = 0;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/runa/auth/step-up') {
      attempts++;
      confirmed = attempts > 1;
      return confirmed
        ? respond({ data: { confirmed: true } })
        : respond({ reason: 'invalid_credentials' }, 422);
    }
    if (String(input) === '/api/admin')
      return confirmed
        ? respond({ data: bootstrap() })
        : respond({ reason: 'step_up_required' }, 403);
    return respond({ reason: 'not_found' }, 404);
  });
  const minimal = defineComponent({
    setup:
      (_, { slots }) =>
      () =>
        h('section', { 'data-test': 'auth-minimal' }, slots.default?.()),
  });
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(
          AuthPresentationProvider,
          { components: { minimalLayout: minimal } },
          {
            default: () =>
              h(
                AdminApplication,
                { session: sessionFixture(), navigate: vi.fn() },
                { default: () => h('p', 'Admitted panel') },
              ),
          },
        ),
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('input[name="current_password"]').exists()).toBe(true),
  );
  expect(wrapper.find('[data-test="auth-minimal"]').exists()).toBe(true);
  expect(wrapper.text()).not.toContain('Application');
  expect(wrapper.text()).not.toContain('Admitted panel');
  await wrapper
    .get('input[name="current_password"]')
    .setValue('Wrong password');
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() => expect(attempts).toBe(1));
  await flushPromises();
  expect(
    (wrapper.get('input[name="current_password"]').element as HTMLInputElement)
      .value,
  ).toBe('');
  expect(wrapper.find('[data-test="auth-minimal"]').exists()).toBe(true);
  await wrapper
    .get('input[name="current_password"]')
    .setValue('Correct password');
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() => expect(wrapper.text()).toContain('Admitted panel'));
  expect(attempts).toBe(2);
  expect(wrapper.find('[data-test="auth-minimal"]').exists()).toBe(false);
});

it('Auth operation proof uses Polish defaults, confirms only identity and cancels transient input', async () => {
  let proof!: ReturnType<typeof useOperationProof>;
  const confirmed = vi.fn(),
    cancel = vi.fn();
  vi.mocked(window.fetch).mockImplementation(async () =>
    respond({ data: { confirmed: true } }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => identity,
          action: () => 'app.example',
          requirement: () => 'password',
          locale: () => 'pl',
        });
        return () =>
          h(AuthOperationProofPage, {
            controller: proof,
            locale: 'pl',
            onConfirmed: confirmed,
            onCancel: cancel,
          });
      },
    }),
    { attachTo: document.body },
  );
  await flushPromises();
  expect(wrapper.text()).toContain('Potwierdź hasłem');
  const heading = wrapper.get('h1').element;
  expect(document.activeElement).toBe(heading);
  expect(getComputedStyle(heading).outlineStyle).toBe('none');
  await wrapper.get('form').trigger('submit');
  expect(proof.error.value).toBe('Uzupełnij wymagane pola potwierdzenia.');
  const proofRequests = () =>
    vi
      .mocked(window.fetch)
      .mock.calls.filter(([url]) => String(url) === '/api/runa/auth/step-up');
  expect(proofRequests()).toHaveLength(0);
  await wrapper
    .get('input[name="current_password"]')
    .setValue('Private password');
  await wrapper.get('form').trigger('submit');
  await vi.waitFor(() => expect(confirmed).toHaveBeenCalledOnce());
  expect(proof.password.value).toBe('');
  expect(proofRequests()).toHaveLength(1);
  proof.password.value = 'Never persisted';
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Anuluj')!
    .trigger('click');
  expect(cancel).toHaveBeenCalledOnce();
  expect(proof.password.value).toBe('');
  expect(proof.confirmed.value).toBe(false);
});

it('Auth operation proof allows a full page replacement with the supplied controller and scoped content', async () => {
  let proof!: ReturnType<typeof useOperationProof>;
  const cancel = vi.fn();
  const replacement = defineComponent({
    props: {
      controller: {
        type: Object as PropType<ReturnType<typeof useOperationProof>>,
        required: true,
      },
      title: { type: String, required: true },
    },
    emits: ['cancel'],
    setup:
      (props, { slots, emit }) =>
      () =>
        h('section', [
          h('h1', String(props.title)),
          slots.default?.({ controller: props.controller }),
          h('button', { onClick: () => emit('cancel') }, 'Custom cancel'),
        ]),
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        proof = useOperationProof({
          identity: () => identity,
          action: () => 'app.example',
          requirement: () => 'password',
        });
        return () =>
          h(
            AuthPresentationProvider,
            { components: { operationProofPage: replacement } },
            {
              default: () =>
                h(
                  AuthOperationProofPage,
                  {
                    controller: proof,
                    title: 'Custom proof',
                    onCancel: cancel,
                  },
                  {
                    default: ({ controller }: { controller: typeof proof }) =>
                      h(
                        'p',
                        controller === proof
                          ? 'Shared controller'
                          : 'Wrong owner',
                      ),
                  },
                ),
            },
          );
      },
    }),
  );
  expect(wrapper.text()).toContain('Custom proof');
  expect(wrapper.text()).toContain('Shared controller');
  expect(wrapper.find('form').exists()).toBe(false);
  await wrapper.get('button').trigger('click');
  expect(cancel).toHaveBeenCalledOnce();
});

it('Admin entry proof preserves a retry when the shared session read fails before admission', async () => {
  const session = sessionFixture();
  session.session.value = undefined;
  session.failure.value = 'failed';
  const wrapper = mount(AdminApplication, {
    props: { session, navigate: vi.fn() },
  });
  await flushPromises();
  expect(wrapper.text()).toContain('Session could not be loaded.');
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Retry')!
    .trigger('click');
  expect(session.refresh).toHaveBeenCalledOnce();
});

it('Admin bilingual presentation follows the shared preference and keeps a dirty field mounted without refetching', async () => {
  localStorage.removeItem('runa.preferences:runa:display:global:runa.locale');
  const data = bootstrap();
  data.owners[0]!.title = {
    pl: 'Ustawienia własnej aplikacji',
    en: 'Custom application settings',
  };
  vi.mocked(window.fetch).mockImplementation(async (input) =>
    String(input) === '/api/admin'
      ? respond({ data })
      : respond({ data: { available: true } }),
  );
  let language!: ReturnType<typeof useAdminLocale>;
  const draft = ref('');
  const field = {
    key: 'name',
    type: 'text',
    label: { pl: 'Nazwa robocza', en: 'Draft name' },
    readonly: false,
    sensitive: false,
    filterable: false,
    sortable: false,
    lookup: false,
    required: true,
    max_length: 160,
    choices: [],
    multiple: false,
    component: null,
  };
  const session = sessionFixture();
  const wrapper = mount(
    defineComponent({
      setup() {
        language = useAdminLocale();
        return () =>
          h(
            AdminApplication,
            { session },
            {
              default: () =>
                h(AdminField, {
                  field,
                  modelValue: draft.value,
                  errors: [],
                  readonly: false,
                  busy: false,
                  'onUpdate:modelValue': (value) => {
                    draft.value = String(value ?? '');
                  },
                }),
            },
          );
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.find('input').exists()).toBe(true));
  expect(wrapper.text()).toContain('Panel administratora');
  expect(
    wrapper
      .getComponent(ApplicationSessionShell)
      .props('navigation')
      ?.map((entry) => entry.label),
  ).toContain('Ustawienia własnej aplikacji');
  const input = wrapper.get('input');
  await input.setValue('Niezapisana nazwa');
  language.locale.value = 'en';
  await flushPromises();
  expect(wrapper.text()).toContain('Administrator panel');
  expect(wrapper.text()).toContain('Draft name');
  expect(wrapper.get('input').element).toBe(input.element);
  expect(draft.value).toBe('Niezapisana nazwa');
  expect(wrapper.getComponent(ApplicationSessionShell).props('session')).toBe(
    session,
  );
  expect(
    wrapper
      .getComponent(ApplicationSessionShell)
      .props('navigation')
      ?.map((entry) => entry.label),
  ).toContain('Custom application settings');
  expect(
    vi
      .mocked(window.fetch)
      .mock.calls.filter(([url]) => String(url) === '/api/admin'),
  ).toHaveLength(1);
  language.locale.value = 'pl';
  await input.setValue('');
  await flushPromises();
  expect(wrapper.text()).toContain('To pole jest wymagane.');
  expect(wrapper.text()).toContain('Nazwa robocza');
  expect(
    vi
      .mocked(window.fetch)
      .mock.calls.filter(([, init]) => init?.method === 'POST'),
  ).toHaveLength(0);
});

it('Admin bilingual password admission changes language without losing transient input or starting another proof', async () => {
  localStorage.setItem(
    'runa.preferences:runa:display:global:runa.locale',
    JSON.stringify({ version: 1, value: 'pl' }),
  );
  vi.mocked(window.fetch).mockImplementation(async () =>
    respond({ reason: 'step_up_required' }, 403),
  );
  let language!: ReturnType<typeof useAdminLocale>;
  const wrapper = mount(
    defineComponent({
      setup() {
        language = useAdminLocale();
        return () =>
          h(AdminApplication, { session: sessionFixture(), navigate: vi.fn() });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('input[name="current_password"]').exists()).toBe(true),
  );
  expect(wrapper.text()).toContain(
    'Potwierdź hasło, aby wejść do panelu administratora.',
  );
  const input = wrapper.get('input[name="current_password"]');
  await input.setValue('Transient password');
  language.locale.value = 'en';
  await flushPromises();
  expect(wrapper.text()).toContain(
    'Confirm your password to enter the administrator panel.',
  );
  expect(wrapper.text()).toContain('Current password');
  expect(wrapper.get('input[name="current_password"]').element).toBe(
    input.element,
  );
  expect((input.element as HTMLInputElement).value).toBe('Transient password');
  expect(
    vi
      .mocked(window.fetch)
      .mock.calls.filter(([url]) => String(url).endsWith('/step-up')),
  ).toHaveLength(0);
});

it('Admin bilingual public translation supports explicit source maps and App overrides without changing business values', () => {
  expect(adminText({ pl: 'Własny tytuł', en: 'Custom title' }, 'pl')).toBe(
    'Własny tytuł',
  );
  expect(adminText({ pl: 'Własny tytuł', en: 'Custom title' }, 'en')).toBe(
    'Custom title',
  );
  expect(adminText('Retry', 'pl', { Retry: 'Spróbuj jeszcze raz' })).toBe(
    'Spróbuj jeszcze raz',
  );
  expect(
    adminText('revoke_others', 'pl', { revoke_others: 'Własna etykieta' }),
  ).toBe('Własna etykieta');
  expect(adminText('revoke_others', 'en')).toBe('Revoke other sessions');
  expect(adminText('App-specific title', 'pl')).toBe('App-specific title');
  expect(adminText('Save', 'en')).toBe('Save');
  expect(adminText({ pl: 'Save', en: 'Save' }, 'pl')).toBe('Save');
  expect(adminText('constructor', 'pl')).toBe('constructor');
  expect(
    validateAdminField(
      {
        key: 'name',
        type: 'text',
        label: 'Name',
        readonly: false,
        sensitive: false,
        filterable: false,
        sortable: false,
        lookup: false,
        required: true,
        max_length: 2,
        choices: [],
        multiple: false,
        component: null,
      },
      'long',
      'pl',
    ),
  ).toEqual(['Użyj najwyżej 2 znaków.']);
});

it('Admin revalidation keeps the menu link stable through unchanged activity and hides a denied background advertisement', async () => {
  const session = sessionFixture();
  let reads = 0,
    finish!: (value: Response) => void;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) !== '/api/admin/entry') return respond({}, 404);
    reads++;
    if (reads === 1) return respond({ data: { available: true } });
    return new Promise((resolve) => {
      finish = resolve;
    });
  });
  const wrapper = mount(ApplicationSessionShell, {
    props: { session },
    attachTo: document.body,
  });
  await flushPromises();
  const menu = wrapper
    .findAll('button')
    .find((button) =>
      button.attributes('aria-label')?.startsWith('User menu:'),
    )!;
  await vi.waitFor(() => expect(menu.attributes('disabled')).toBeUndefined());
  await menu.trigger('click');
  await flushPromises();
  const link = () => document.querySelector('.account-menu a[href="/admin"]');
  expect(link()).not.toBeNull();
  session.session.value = {
    ...session.session.value!,
    session: {
      ...session.session.value!.session,
      activity_at: new Date(Date.now() + 1000).toISOString(),
    },
  };
  await menu.trigger('click');
  await menu.trigger('click');
  await flushPromises();
  expect(reads).toBe(1);
  expect(link()).not.toBeNull();
  session.authorityRevision.value++;
  await flushPromises();
  expect(reads).toBe(2);
  expect(link()).not.toBeNull();
  finish(respond({ data: { available: false } }));
  await flushPromises();
  await vi.waitFor(() => expect(link()).toBeNull());
  session.authorityRevision.value++;
  await flushPromises();
  const late = finish;
  session.paused.value = true;
  late(respond({ data: { available: true } }));
  await flushPromises();
  await vi.waitFor(() => expect(link()).toBeNull());
});

it('Admin revalidation retains the mounted password form and input until a real access denial', async () => {
  const session = sessionFixture();
  let reads = 0,
    finish!: (value: Response) => void;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    if (String(input) === '/api/admin/entry')
      return respond({ data: { available: true } });
    if (String(input) !== '/api/admin') return respond({}, 404);
    reads++;
    if (reads === 1) return respond({ reason: 'step_up_required' }, 403);
    return new Promise((resolve) => {
      finish = resolve;
    });
  });
  const wrapper = mount(AdminApplication, {
    props: { session, navigate: vi.fn() },
  });
  await vi.waitFor(() =>
    expect(wrapper.find('input[name="current_password"]').exists()).toBe(true),
  );
  const input = wrapper.get('input[name="current_password"]');
  await input.setValue('Transient draft');
  session.authorityRevision.value++;
  await flushPromises();
  expect(reads).toBe(2);
  expect(wrapper.get('input[name="current_password"]').element).toBe(
    input.element,
  );
  expect((input.element as HTMLInputElement).value).toBe('Transient draft');
  finish(respond({ reason: 'step_up_required' }, 403));
  await flushPromises();
  expect(wrapper.get('input[name="current_password"]').element).toBe(
    input.element,
  );
  expect((input.element as HTMLInputElement).value).toBe('Transient draft');
  session.authorityRevision.value++;
  await flushPromises();
  finish(respond({ reason: 'permission_denied' }, 403));
  await vi.waitFor(() =>
    expect(wrapper.find('input[name="current_password"]').exists()).toBe(false),
  );
  expect(wrapper.text()).toContain(
    'You do not have permission for this operation.',
  );
  expect(
    vi
      .mocked(window.fetch)
      .mock.calls.filter(([, init]) => init?.method === 'POST'),
  ).toHaveLength(0);
});

it('Admin revalidation still erases proof input and rejects a late bootstrap after an account change', async () => {
  const session = sessionFixture();
  let admin!: AdminController,
    finish!: (value: Response) => void,
    reads = 0;
  vi.mocked(window.fetch).mockImplementation(async () => {
    reads++;
    if (reads === 1) return respond({ reason: 'step_up_required' }, 403);
    return new Promise((resolve) => {
      finish = resolve;
    });
  });
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate: vi.fn() });
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(admin.state.value).toBe('proof_required'));
  admin.entryProof.password.value = 'Transient draft';
  session.authorityRevision.value++;
  await flushPromises();
  const old = finish;
  session.session.value = {
    ...session.session.value!,
    public_id: '01900000-0000-7000-8000-000000000002',
  };
  expect(admin.entryProof.password.value).toBe('');
  expect(admin.entryProof.open.value).toBe(false);
  old(respond({ data: bootstrap() }));
  await flushPromises();
  expect(admin.bootstrap.value).toBeUndefined();
  expect(admin.state.value).toBe('loading');
});

it('Admin revalidation clears transient proof when background bootstrap admits the current session', async () => {
  const session = sessionFixture();
  let admin!: AdminController,
    finish!: (value: Response) => void,
    reads = 0;
  vi.mocked(window.fetch).mockImplementation(async () => {
    reads++;
    if (reads === 1) return respond({ reason: 'step_up_required' }, 403);
    return new Promise((resolve) => {
      finish = resolve;
    });
  });
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate: vi.fn() });
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(admin.state.value).toBe('proof_required'));
  admin.entryProof.password.value = 'Transient draft';
  session.authorityRevision.value++;
  await flushPromises();
  finish(respond({ data: bootstrap() }));
  await vi.waitFor(() => expect(admin.state.value).toBe('ready'));
  expect(admin.entryProof.password.value).toBe('');
  expect(admin.entryProof.open.value).toBe(false);
});

it('Admin initial readiness keeps the Application frame through bootstrap and delayed diagnostics', async () => {
  let finishBootstrap!: (value: Response) => void;
  let finishCapabilities!: (value: Response) => void;
  let finishDiagnostics!: (value: Response) => void;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const path = String(input);
    if (path === '/api/admin')
      return new Promise((resolve) => {
        finishBootstrap = resolve;
      });
    if (path.includes('/operations/capabilities'))
      return new Promise((resolve) => {
        finishCapabilities = resolve;
      });
    if (path.includes('/operations/diagnostics'))
      return new Promise((resolve) => {
        finishDiagnostics = resolve;
      });
    return respond({ data: { available: true } });
  });
  const wrapper = mount(AdminApplication, {
    attachTo: document.body,
    props: { session: sessionFixture() },
  });
  await flushPromises();
  const frame = wrapper.get('.application-page').element;
  expect(wrapper.find('.minimal-page').exists()).toBe(false);
  expect(wrapper.find('[data-breadcrumbs-loading]').exists()).toBe(true);
  expect(wrapper.find('.shell-breadcrumbs nav').exists()).toBe(false);
  finishBootstrap(respond({ data: bootstrap() }));
  await flushPromises();
  await vi.waitFor(() => expect(finishCapabilities).toBeTypeOf('function'));
  expect(wrapper.get('[data-admin-content]').isVisible()).toBe(false);
  expect(wrapper.get('.shell-breadcrumbs').attributes('aria-busy')).toBe(
    'true',
  );
  expect(wrapper.find('[data-breadcrumbs-loading]').exists()).toBe(true);
  expect(wrapper.find('.shell-breadcrumbs nav').exists()).toBe(false);
  expect(wrapper.find('[data-navigation-loading]').exists()).toBe(true);
  finishCapabilities(respond({ data: { capabilities: ['diagnostics.read'] } }));
  await flushPromises();
  await vi.waitFor(() => expect(finishDiagnostics).toBeTypeOf('function'));
  expect(wrapper.get('[data-admin-content]').isVisible()).toBe(false);
  expect(wrapper.get('.shell-breadcrumbs').attributes('aria-busy')).toBe(
    'true',
  );
  expect(wrapper.find('[data-breadcrumbs-loading]').exists()).toBe(true);
  expect(wrapper.find('.shell-breadcrumbs nav').exists()).toBe(false);
  finishDiagnostics(
    respond({
      data: {
        modules: [],
        platform_version: '0.7.0',
        application_version: '0.1.0',
      },
    }),
  );
  await flushPromises();
  await vi.waitFor(() =>
    expect(wrapper.get('[data-admin-content]').isVisible()).toBe(true),
  );
  expect(wrapper.find('[data-navigation-loading]').exists()).toBe(false);
  expect(wrapper.find('[data-breadcrumbs-loading]').exists()).toBe(false);
  expect(wrapper.get('.shell-breadcrumbs').attributes('aria-busy')).toBe(
    'false',
  );
  expect(wrapper.get('.shell-breadcrumbs [aria-current="page"]').text()).toBe(
    'Modules',
  );
  expect(wrapper.get('.application-page').element).toBe(frame);
});

it('Admin initial readiness waits for a lazy renderer and its read without hiding a later dirty draft', async () => {
  const settled = ref(false);
  let finish!: (component: Component) => void;
  const lazy = defineAsyncComponent(
    () =>
      new Promise<Component>((resolve) => {
        finish = resolve;
      }),
  );
  const wrapper = mount(AdminReadiness, {
    attachTo: document.body,
    slots: {
      default: () =>
        h(
          AdminComponentBoundary,
          { identity: 'lazy-page' },
          { default: () => h(lazy) },
        ),
    },
  });
  await flushPromises();
  expect(wrapper.get('[data-admin-content]').isVisible()).toBe(false);
  finish(
    defineComponent({
      setup() {
        useAdminInitialReadiness(() => settled.value);
        return () => h('input', { 'aria-label': 'Draft' });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.find('input').exists()).toBe(true));
  expect(wrapper.get('[data-admin-content]').isVisible()).toBe(false);
  settled.value = true;
  await vi.waitFor(() =>
    expect(wrapper.get('[data-admin-content]').isVisible()).toBe(true),
  );
  const input = wrapper.get('input');
  await input.setValue('Retained draft');
  input.element.focus();
  settled.value = false;
  await flushPromises();
  expect(wrapper.get('[data-admin-content]').isVisible()).toBe(true);
  expect(wrapper.get('input').element).toBe(input.element);
  expect(document.activeElement).toBe(input.element);
  expect(input.element.value).toBe('Retained draft');
});

it('Admin initial readiness settles a stalled lazy renderer to the bounded failure fallback', async () => {
  vi.useFakeTimers();
  const lazy = defineAsyncComponent(() => new Promise<Component>(() => {}));
  const failure = vi.fn();
  const wrapper = mount(AdminReadiness, {
    attachTo: document.body,
    slots: {
      default: () =>
        h(
          AdminComponentBoundary,
          { identity: 'stalled', onFailure: failure },
          { default: () => h(lazy) },
        ),
    },
  });
  try {
    await vi.advanceTimersByTimeAsync(14999);
    expect(wrapper.get('[data-admin-content]').isVisible()).toBe(false);
    await vi.advanceTimersByTimeAsync(1);
    expect(wrapper.get('[data-admin-content]').isVisible()).toBe(true);
    expect(failure).toHaveBeenCalledWith(true);
    expect(wrapper.text()).toContain('unavailable');
  } finally {
    wrapper.unmount();
    vi.useRealTimers();
  }
});

it('Admin dashboard uses welcome catalogue icons, area breadcrumbs and omits empty widget copy', async () => {
  const { getModulePresentation } = await import('@/runa/ui');
  const source = bootstrap();
  source.owners = ['runa.auth', 'runa.audit', 'runa.permissions'].map((id) => ({
    id,
    title: id,
    default_page: 'settings',
    pages: [
      {
        key: 'settings',
        title: 'Settings',
        href: '/admin/' + id + '/settings',
        group: null,
        order: 0,
      },
    ],
  }));
  vi.mocked(window.fetch).mockImplementation(async (input) =>
    String(input) === '/api/admin'
      ? respond({ data: source })
      : respond({ data: { capabilities: [] } }),
  );
  const session = sessionFixture();
  session.welcomeEnabled.value = true;
  const w = mount(AdminApplication, {
    props: { session },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(
      w
        .find('.shell-sidebar-scroll nav a[href="/admin/runa.auth/settings"]')
        .exists(),
    ).toBe(true),
  );
  const navigation = w
    .getComponent(ApplicationSessionShell)
    .props('navigation')!;
  for (const owner of source.owners) {
    expect(navigation.find((entry) => entry.key === owner.id)?.icon).toBe(
      getModulePresentation(owner.id).icon,
    );
    expect(
      w
        .find(
          '.shell-sidebar-scroll nav a[href="/admin/' +
            owner.id +
            '/settings"] svg',
        )
        .exists(),
    ).toBe(true);
  }
  expect(w.get('.shell-breadcrumbs').text()).toContain('Home');
  expect(w.get('.shell-breadcrumbs').text()).toContain('Administrator panel');
  expect(w.find('.shell-breadcrumbs a[href="/app"]').exists()).toBe(false);
  expect(w.get('.shell-breadcrumbs [aria-current="page"]').text()).toBe(
    'Modules',
  );
  expect(w.get('.shell-breadcrumbs a[href="/admin"]').text()).toBe(
    'Administrator panel',
  );
  const application = w
    .findAll('.shell-top-nav a')
    .find((link) => link.text().trim() === 'Application settings');
  expect(application).toBeDefined();
  await application!.trigger('click');
  await vi.waitFor(() =>
    expect(w.get('.shell-breadcrumbs [aria-current="page"]').text()).toBe(
      'Application settings',
    ),
  );

  expect(w.text()).not.toContain('No dashboard widgets');
});

it('shared breadcrumbs include linked Admin owner ancestors and account subviews', async () => {
  const session = sessionFixture();
  session.welcomeEnabled.value = true;
  const w = mount(ApplicationSessionShell, {
    props: {
      session,
      current: {
        routeKey: 'runa.auth.users',
        moduleKey: 'runa.auth',
        href: '/admin/runa.auth/users',
      },
      navigation: [
        {
          key: 'auth',
          routeKey: 'runa.auth.users',
          moduleKey: 'runa.auth',
          label: 'Accounts',
          href: '/admin/runa.auth/users',
          availability: 'available',
        },
      ],
      topNavigation: [
        {
          key: 'users',
          routeKey: 'runa.auth.users',
          moduleKey: 'runa.auth',
          label: 'Users',
          href: '/admin/runa.auth/users',
          availability: 'available',
        },
      ],
    },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.find('.shell-breadcrumbs [aria-current="page"]').exists()).toBe(
      true,
    ),
  );
  expect(
    w
      .findAll('.shell-breadcrumbs li')
      .map((item) => item.text().replace(/^\/\s*/, '')),
  ).toEqual(['Home', 'Administrator panel', 'Accounts', 'Users']);
  expect(w.find('.shell-breadcrumbs a[href="/admin"]').exists()).toBe(true);
  expect(w.get('.shell-breadcrumbs [aria-current="page"]').text()).toBe(
    'Users',
  );
  await w.setProps({
    current: {
      routeKey: 'account.security',
      moduleKey: 'runa.auth',
      href: '/account?tab=security',
    },
    topNavigation: [
      {
        key: 'security',
        routeKey: 'account.security',
        moduleKey: 'runa.auth',
        label: 'Security',
        href: '/account?tab=security',
        availability: 'available',
      },
    ],
  });
  expect(
    w
      .findAll('.shell-breadcrumbs li')
      .map((item) => item.text().replace(/^\/\s*/, '')),
  ).toEqual(['Home', 'Application', 'User panel', 'Security']);
  expect(w.find('.shell-breadcrumbs a[href="/account"]').exists()).toBe(true);
});

it.each(['entry first', 'preferences first'])(
  'session reveal waits for Admin menu and hydrated theme (%s)',
  async (order) => {
    let finishEntry!: (value: Response) => void;
    let finishPreferences!: (value: Response) => void;
    vi.mocked(window.fetch).mockImplementation(async (input) => {
      if (String(input) === '/api/admin/entry')
        return new Promise((resolve) => {
          finishEntry = resolve;
        });
      if (String(input) === '/api/runa/auth/preferences')
        return new Promise((resolve) => {
          finishPreferences = resolve;
        });
      return respond({}, 404);
    });
    const connecting = accountPreferences.connect(identity);
    const session = sessionFixture();
    const wrapper = mount(ApplicationSessionShell, {
      props: { session },
      slots: { default: '<p data-ready-body>Ready body</p>' },
      attachTo: document.body,
    });
    await vi.waitFor(() => expect(finishEntry).toBeTypeOf('function'));
    await vi.waitFor(() => expect(finishPreferences).toBeTypeOf('function'));
    const menu = () => wrapper.get('.shell-avatar-button');
    const assertLoading = () => {
      expect(wrapper.find('[data-ready-body]').exists()).toBe(false);
      expect(wrapper.find('[data-preferences-loading]').exists()).toBe(true);
      expect(menu().attributes('disabled')).toBeDefined();
    };
    assertLoading();
    const entry = () => finishEntry(respond({ data: { available: true } }));
    const preferences = () =>
      finishPreferences(
        respond({
          data: {
            account: identity,
            revision: 1,
            values: [
              {
                scope: 'runa:runa.auth:global:theme',
                version: 1,
                stored: true,
                value: 'dark',
              },
              {
                scope: 'runa:runa.auth:global:locale',
                version: 1,
                stored: true,
                value: 'en',
              },
            ],
          },
        }),
      );
    (order === 'entry first' ? entry : preferences)();
    await flushPromises();
    assertLoading();
    (order === 'entry first' ? preferences : entry)();
    await connecting;
    await vi.waitFor(() =>
      expect(wrapper.find('[data-ready-body]').exists()).toBe(true),
    );
    expect(wrapper.find('[data-preferences-loading]').exists()).toBe(false);
    expect(document.documentElement.dataset.theme).toBe('dark');
    expect(menu().attributes('disabled')).toBeUndefined();
    expect(wrapper.find('button[aria-label^="Theme: Dark"]').exists()).toBe(
      true,
    );
    await menu().trigger('click');
    await vi.waitFor(() =>
      expect(
        document.querySelector('.account-menu a[href="/admin"]'),
      ).not.toBeNull(),
    );
    const content = wrapper.get('[data-ready-body]').element;
    session.authorityRevision.value++;
    await flushPromises();
    expect(wrapper.get('[data-ready-body]').element).toBe(content);
    expect(wrapper.find('[data-preferences-loading]').exists()).toBe(false);
    finishEntry(respond({ data: { available: false } }));
    await vi.waitFor(() =>
      expect(
        document.querySelector('.account-menu a[href="/admin"]'),
      ).toBeNull(),
    );
  },
);

it('initial Admin advertisement failure settles the shell to an absent link', async () => {
  vi.mocked(window.fetch).mockImplementation(async () => {
    throw new DOMException('Timed out', 'TimeoutError');
  });
  const wrapper = mount(ApplicationSessionShell, {
    props: { session: sessionFixture() },
    slots: { default: '<p data-ready-body>Ready body</p>' },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(wrapper.find('[data-ready-body]').exists()).toBe(true),
  );
  expect(wrapper.find('[data-preferences-loading]').exists()).toBe(false);
  await wrapper.get('.shell-avatar-button').trigger('click');
  await flushPromises();
  expect(document.querySelector('.account-menu a[href="/admin"]')).toBeNull();
});

it('bounded Admin advertisement timeout releases initial readiness and fences a withdrawn session', async () => {
  const timeout = new AbortController();
  const timed = vi
    .spyOn(AbortSignal, 'timeout')
    .mockReturnValue(timeout.signal);
  vi.mocked(window.fetch).mockImplementation(
    async (_input, init) =>
      new Promise((_resolve, reject) => {
        init?.signal?.addEventListener(
          'abort',
          () => reject(init.signal!.reason),
          { once: true },
        );
      }),
  );
  const session = sessionFixture();
  const wrapper = mount(ApplicationSessionShell, {
    props: { session },
    slots: { default: '<p data-ready-body>Ready body</p>' },
    attachTo: document.body,
  });
  await flushPromises();
  expect(timed).toHaveBeenCalledWith(5000);
  expect(wrapper.find('[data-ready-body]').exists()).toBe(false);
  timeout.abort(new DOMException('Timed out', 'TimeoutError'));
  await vi.waitFor(() =>
    expect(wrapper.find('[data-ready-body]').exists()).toBe(true),
  );
  session.session.value = undefined;
  await flushPromises();
  expect(wrapper.find('[data-ready-body]').exists()).toBe(false);
  expect(document.querySelector('.account-menu a[href="/admin"]')).toBeNull();
});

it('custom Admin pages share admitted identity, register exit refusal and remove it on disposal', async () => {
  const session = sessionFixture(),
    navigate = vi.fn(),
    show = ref(true);
  const beforeLeave = vi.fn(async () => false);
  let admin!: ReturnType<typeof useAdmin>;
  let page!: ReturnType<typeof useAdminPage>;
  let context!: ReturnType<typeof useAdminPageContext>;
  const baseFetch = vi.mocked(window.fetch).getMockImplementation()!;
  vi.mocked(window.fetch).mockImplementation(async (input, init) =>
    String(input) === '/api/admin/app/settings'
      ? respond({
          data: {
            schema_version: 1,
            owner: 'app',
            key: 'settings',
            title: 'Settings',
            generation: 7,
            revision: 'page',
            component: null,
            sections: [],
          },
        })
      : baseFetch(input, init),
  );
  const Child = defineComponent({
    setup() {
      context = useAdminPageContext(page, { beforeLeave, dirty: () => true });
      return () => h('output', context.identity.value);
    },
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session, navigate });
        page = useAdminPage(admin, ref({ owner: 'app', page: 'settings' }));
        return () => (show.value ? h(Child) : null);
      },
    }),
  );
  await vi.waitFor(() => expect(context.identity.value).toBeDefined());
  const original = context.identity.value;
  session.authorityRevision.value++;
  await flushPromises();
  expect(context.identity.value).toBe(original);
  expect(await admin.navigate({ owner: 'app', page: 'settings' })).toBe(false);
  expect(beforeLeave).toHaveBeenCalledTimes(1);
  expect(navigate).not.toHaveBeenCalled();
  session.paused.value = true;
  expect(context.identity.value).toBeUndefined();
  session.paused.value = false;
  await vi.waitFor(() => expect(context.identity.value).toBeDefined());
  show.value = false;
  await wrapper.vm.$nextTick();
  expect(await admin.navigate({ owner: 'app', page: 'settings' })).toBe(true);
  expect(beforeLeave).toHaveBeenCalledTimes(1);
  expect(navigate).toHaveBeenCalledWith('/admin/app/settings');
});

it('action-driven refresh keeps same-session security checks independent of Admin resources and preferences', async () => {
  accountPreferences.disconnect();
  const current = sessionFixture().session.value!;
  let denied = false;
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const path = String(input).split('?')[0]!;
    if (path.endsWith('/csrf')) return respond({});
    if (path.endsWith('/auth/session'))
      return denied
        ? respond({ error: { code: 'signed_out' } }, 401)
        : respond({ data: current });
    if (path.endsWith('/auth/preferences'))
      return respond({
        data: {
          account: current.public_id,
          revision: 0,
          values: [],
          legacy_claimed: true,
        },
      });
    if (path === '/api/admin/activity')
      return respond({
        data: { remaining_seconds: 900, heartbeat_seconds: 30 },
      });
    if (path === '/api/admin') return respond({ data: bootstrap() });
    if (path === '/api/admin/app/settings')
      return respond({ data: resourcePage() });
    if (path.includes('/sections/profile'))
      return respond({
        data: {
          public_id: identity,
          revision: 'r1',
          values: { name: 'Retained' },
        },
      });
    return respond({}, 404);
  });
  let session!: ApplicationSessionController;
  let page!: AdminPageController;
  let resource!: AdminResourceController;
  mount(
    defineComponent({
      setup() {
        session = useApplicationSession(vi.fn());
        const admin = useAdmin({ session });
        page = useAdminPage(admin, ref({ owner: 'app', page: 'settings' }));
        resource = useAdminResource(page, 'profile');
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  await resource.read({ kind: 'record', public_id: identity });
  await flushPromises();
  const revision = session.authorityRevision.value;
  const before = vi.mocked(window.fetch).mock.calls.length;
  window.dispatchEvent(new Event('focus'));
  document.dispatchEvent(new Event('visibilitychange'));
  window.dispatchEvent(
    new PageTransitionEvent('pageshow', { persisted: true }),
  );
  await flushPromises();
  await session.refresh();
  await flushPromises();
  const calls = vi
    .mocked(window.fetch)
    .mock.calls.slice(before)
    .map(([path]) => String(path));
  expect(calls.some((path) => path.endsWith('/auth/session'))).toBe(true);
  expect(
    calls.filter(
      (path) =>
        !path.endsWith('/csrf') &&
        !path.endsWith('/auth/session') &&
        !path.endsWith('/admin/activity'),
    ),
  ).toEqual([]);
  expect(session.authorityRevision.value).toBe(revision);
  expect(resource.result.value).toMatchObject({ values: { name: 'Retained' } });
  await resource.read({ kind: 'record', public_id: identity });
  expect(vi.mocked(window.fetch).mock.calls.at(-1)![0]).toContain(
    '/sections/profile',
  );
  denied = true;
  await session.refresh();
  await flushPromises();
  expect(session.paused.value).toBe(true);
  expect(resource.result.value).toBeUndefined();
  expect(accountPreferences.currentAccount()).toBeNull();
});

it('Admin document cancellation preserves admitted bootstrap and page while real errors still refuse', async () => {
  const { RequestCancelledError } = await import('@/runa/http');
  let admin!: AdminController, page!: AdminPageController;
  vi.mocked(window.fetch).mockImplementation(async (input) =>
    respond({
      data: String(input) === '/api/admin' ? bootstrap() : resourcePage(),
    }),
  );
  mount(
    defineComponent({
      setup() {
        admin = useAdmin({ session: sessionFixture(), navigate: vi.fn() });
        page = useAdminPage(admin, ref({ owner: 'app', page: 'settings' }));
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(page.state.value).toBe('ready'));
  const original = page.page.value;
  vi.mocked(window.fetch).mockRejectedValue(new RequestCancelledError());
  await admin.refresh();
  await page.refresh();
  expect(admin.state.value).toBe('ready');
  expect(admin.error.value).toBeUndefined();
  expect(page.state.value).toBe('ready');
  expect(page.page.value).toBe(original);
  expect(page.error.value).toBeUndefined();
  vi.mocked(window.fetch).mockImplementation(async () =>
    respond({ reason: 'availability_changed' }, 503),
  );
  await page.refresh();
  expect(page.state.value).toBe('unavailable');
  expect(page.page.value).toBeUndefined();
  await admin.refresh();
  expect(admin.state.value).toBe('unavailable');
  expect(admin.bootstrap.value).toBeUndefined();
});
