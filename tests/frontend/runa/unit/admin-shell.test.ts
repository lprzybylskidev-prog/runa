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
