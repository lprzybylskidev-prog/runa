/* eslint-disable vue/one-component-per-file -- Independent mounted controller and presentation fixtures. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  AdminApplication,
  AdminPresentationProvider,
  resolveAdminComponent,
  useAdmin,
  useAdminPage,
  useAdminResource,
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
} from '@/runa/admin';
import { accountPreferences } from '../../../../src/runa/modules/auth/frontend/preferences/session';

import CustomerAdmin from './fixtures/customization/CustomerAdmin.vue';
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
