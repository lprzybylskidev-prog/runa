/* eslint-disable vue/one-component-per-file -- Independent mounted operation and navigation harnesses. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  AdminApplicationSettings,
  useAdminApplicationSettings,
  AdminOperations,
  AdminOperationalEditor,
  useAdminOperations,
} from '@/runa/admin';
import type {
  AdminController,
  AdminOperationsController,
  AdminOperationData,
} from '@/runa/admin';
import type {
  ActivitySession,
  ApplicationSessionController,
  Profile,
} from '@/runa/auth';
enableAutoUnmount(afterEach);
beforeEach(() => {
  window.history.replaceState({}, '', '/admin');
  localStorage.setItem('runa.locale', 'en');
});
const id = '01900000-0000-7000-8000-000000000001';
function admin(): AdminController {
  const session: ApplicationSessionController = {
    session: shallowRef<ActivitySession>({
      public_id: id,
      display_name: 'Owner',
      restrictions: [],
      session: {
        public_id: id,
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
  return {
    session,
    state: computed(() => 'ready'),
    bootstrap: shallowRef(),
    location: computed(() => undefined),
    error: shallowRef(),
    entryProof: {
      password: ref(''),
      code: ref(''),
      method: ref('totp'),
      methods: shallowRef([]),
      open: ref(false),
      busy: ref(false),
      confirmed: ref(false),
      error: computed(() => ''),
      requirement: computed(() => 'password'),
      begin: vi.fn(async () => {}),
      confirm: vi.fn(async () => {}),
      sendEmail: vi.fn(async () => {}),
      reset: vi.fn(),
    },
    refresh: vi.fn(async () => {}),
    navigate: vi.fn(async () => true),
    confirmEntry: vi.fn(async () => {}),
    cancelEntry: vi.fn(),
  };
}
const response = (data: AdminOperationData, status = 200) =>
  new Response(JSON.stringify({ data }), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
function harness(
  owner = admin(),
  onSetup?: (operations: AdminOperationsController) => void,
) {
  let controller!: AdminOperationsController;
  const wrapper = mount(
    defineComponent({
      setup() {
        controller = useAdminOperations(owner);
        onSetup?.(controller);
        return () => h('div');
      },
    }),
  );
  return { controller, wrapper, owner };
}
it('operational diagnostic fences late reads and never replays a failed write', async () => {
  let finish!: (value: Response) => void;
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(
    () =>
      new Promise((resolve) => {
        finish = resolve;
      }),
  );
  const { controller, owner } = harness();
  const pending = controller.refresh();
  owner.session.authorityRevision.value++;
  await flushPromises();
  finish(response({ modules: [], generation: 1 }));
  await pending;
  expect(controller.snapshot.value).toBeUndefined();
  fetch.mockResolvedValue(response({}, 409));
  controller.dirty.value = true;
  await controller.execute('health.configure', {
    module: 'app.example',
    generation: 1,
    overrides: {},
  });
  expect(controller.conflict.value).toBe(true);
  expect(controller.dirty.value).toBe(true);
  await flushPromises();
  expect(fetch).toHaveBeenCalledTimes(2);
});
it('operational diagnostic confirms destructive commands and freezes submitted input', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(response({ status: 'pending' }));
  const { controller } = harness();
  const input = ref({ id }).value;
  const pending = controller.execute('work.cancel', input, true);
  expect(fetch).not.toHaveBeenCalled();
  input.id = '01900000-0000-7000-8000-000000000002';
  controller.confirm(true);
  await pending;
  expect(JSON.parse(String(fetch.mock.calls[0]?.[1]?.body))).toEqual({ id });
  const leave = controller.beforeLeave();
  expect(await leave).toBe(true);
  controller.dirty.value = true;
  const guarded = controller.beforeLeave();
  controller.confirm(false);
  expect(await guarded).toBe(false);
  const anchor = document.createElement('a');
  anchor.href = '/account';
  document.body.append(anchor);
  const click = new MouseEvent('click', {
    bubbles: true,
    cancelable: true,
    button: 0,
  });
  anchor.dispatchEvent(click);
  expect(click.defaultPrevented).toBe(true);
  controller.confirm(false);
  anchor.remove();
});
it('operational diagnostic renders persisted health and isolates denied management controls', async () => {
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/capabilities')
      ? response({ capabilities: ['diagnostics.read'] })
      : response({
          modules: [
            {
              id: 'app.safe',
              available: false,
              health: 'unknown',
              enabled: true,
              source_version: '1.0.0',
              installed_version: '1.0.0',
              required: {},
              optional: {},
              reasons: ['health_expired'],
              observations: [],
            },
          ],
          platform_version: '0.7.0',
          application_version: '1.0.0',
          runtime: { php: '8.4' },
        }),
  );
  const wrapper = mount(AdminOperations, { props: { admin: admin() } });
  await flushPromises();
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('Health observations have expired.'),
  );
  expect(wrapper.get('[data-module="app.safe"]').text()).toContain(
    'Source version',
  );
  expect(wrapper.text()).not.toContain('Preview transition');
  expect(wrapper.text()).not.toContain('Diagnose now');
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'app.safe')!
    .trigger('click');
  expect(wrapper.text()).not.toContain('Recover settings');
});
it('operational diagnostic preserves secret set-state and dirty settings after conflict', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      generation: 3,
      revision: 4,
      editable: true,
      settings: {
        credential: {
          definition: {
            type: 'string',
            sensitive: true,
            nullable: true,
            names: { en: 'Credential' },
          },
          set: true,
        },
      },
    }),
  );
  const { controller } = harness();
  const wrapper = mount(AdminOperationalEditor, {
    props: { controller, module: 'app.safe', kind: 'settings', writable: true },
  });
  await flushPromises();
  await vi.waitFor(() => expect(wrapper.text()).toContain('Set'));
  expect(
    wrapper.find<HTMLInputElement>('input[type=password]').element.value,
  ).toBe('');
  await wrapper
    .find<HTMLInputElement>('input[type=password]')
    .setValue('replacement');
  fetch.mockResolvedValue(response({}, 409));
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Save settings')!
    .trigger('click');
  await flushPromises();
  await vi.waitFor(() => expect(controller.conflict.value).toBe(true));
  expect(
    wrapper.find<HTMLInputElement>('input[type=password]').element.value,
  ).toBe('replacement');
  expect(controller.dirty.value).toBe(true);
  expect(JSON.parse(String(fetch.mock.calls.at(-1)?.[1]?.body))).toEqual({
    module: 'app.safe',
    generation: 3,
    revision: 4,
    patch: { credential: 'replacement' },
  });
});

const applicationState = (
  revision: number,
  name: string,
  label = 'Source label',
): AdminOperationData => ({
  revision,
  values: { 'presentation.name': name, 'business.label': label },
  definitions: {
    'presentation.name': {
      type: 'string',
      title: 'Displayed application name',
      max_length: 160,
      choices: [],
      writable: true,
    },
    'business.label': {
      type: 'string',
      title: 'Business label',
      max_length: 100,
      choices: [],
      writable: true,
    },
  },
});
it('application settings retain dirty drafts across disjoint and overlapping revisions without replay', async () => {
  let settings!: ReturnType<typeof useAdminApplicationSettings>;
  const { controller } = harness(admin(), (operations) => {
    settings = useAdminApplicationSettings(operations);
  });
  const fetch = vi.spyOn(window, 'fetch');
  fetch.mockResolvedValueOnce(response(applicationState(1, 'Source name')));
  await settings.refresh();
  settings.edit('presentation.name', 'My draft');
  fetch.mockResolvedValueOnce(
    response(applicationState(2, 'Source name', 'Remote label')),
  );
  await settings.refresh();
  expect(settings.draft.value['presentation.name']).toBe('My draft');
  expect(settings.draft.value['business.label']).toBe('Remote label');
  expect(settings.revision.value).toBe(2);
  expect(settings.conflicts.value.size).toBe(0);
  fetch.mockResolvedValueOnce(
    response(applicationState(3, 'Remote name', 'Remote label')),
  );
  await settings.refresh();
  expect(settings.conflicts.value.has('presentation.name')).toBe(true);
  const calls = fetch.mock.calls.length;
  await settings.save();
  expect(fetch).toHaveBeenCalledTimes(calls);
  settings.resolve('presentation.name', true);
  fetch.mockResolvedValueOnce(
    response({ reason: 'application_settings_revision_changed' }, 409),
  );
  await settings.save();
  expect(settings.draft.value['presentation.name']).toBe('My draft');
  expect(controller.dirty.value).toBe(true);
  expect(fetch).toHaveBeenCalledTimes(calls + 1);
  expect(JSON.parse(String(fetch.mock.calls.at(-1)?.[1]?.body))).toEqual({
    revision: 3,
    patch: { 'presentation.name': 'My draft' },
  });
});
it('application settings defaults compose readonly typed fields and source reset controls', async () => {
  const { controller } = harness();
  const state = applicationState(1, 'Source name');
  const definitions = state.definitions as Record<string, AdminOperationData>;
  definitions['business.label']!.writable = false;
  vi.spyOn(window, 'fetch').mockResolvedValue(response(state));
  const wrapper = mount(AdminApplicationSettings, { props: { controller } });
  await flushPromises();
  expect(wrapper.text()).toContain('Application settings');
  await vi.waitFor(() => expect(wrapper.text()).toContain('Read only.'));
  expect(wrapper.find('input[disabled]').exists()).toBe(true);
  const reset = wrapper
    .findAll('button')
    .find((button) => button.text() === 'Reset Displayed application name');
  await reset!.trigger('click');
  expect(controller.dirty.value).toBe(true);
  expect(
    wrapper
      .findAll('button')
      .some((button) => button.text() === 'Reset Business label'),
  ).toBe(false);
});
it('application settings clear private drafts when the account context changes', async () => {
  let settings!: ReturnType<typeof useAdminApplicationSettings>;
  const { controller, owner } = harness(admin(), (operations) => {
    settings = useAdminApplicationSettings(operations);
  });
  vi.spyOn(window, 'fetch').mockResolvedValue(
    response(applicationState(1, 'Source name')),
  );
  await settings.refresh();
  settings.edit('presentation.name', 'Private draft');
  owner.session.authorityRevision.value++;
  await flushPromises();
  expect(settings.draft.value['presentation.name']).toBe('Private draft');
  expect(controller.dirty.value).toBe(true);
  owner.session.session.value = {
    ...owner.session.session.value!,
    public_id: '01900000-0000-7000-8000-000000000099',
  };
  await flushPromises();
  expect(settings.draft.value).toEqual({});
  expect(settings.loaded.value).toBe(false);
  expect(controller.dirty.value).toBe(false);
});

it('operational background reads leave editors available and an explicit write fences the old read without replay', async () => {
  let finish!: (value: Response) => void;
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(async (_, init) => {
      if (init?.method === 'POST') return response({ accepted: true });
      return new Promise<Response>((resolve) => {
        finish = resolve;
      });
    });
  const { controller } = harness();
  const read = controller.refresh();
  expect(controller.refreshing.value).toBe(true);
  expect(controller.busy.value).toBe(false);
  await controller.execute('health.configure', {
    module: 'app',
    enabled: true,
  });
  finish(response({ modules: ['Old read'] }));
  await read;
  expect(controller.snapshot.value).toBeUndefined();
  expect(controller.refreshing.value).toBe(false);
  expect(controller.busy.value).toBe(false);
  expect(fetch).toHaveBeenCalledTimes(2);
});

it('operational reload never overwrites a newer edit made while its read is pending', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(
      response({ generation: 1, health_policy: true, health_overrides: {} }),
    );
  const { controller } = harness();
  const w = mount(AdminOperationalEditor, {
    props: { controller, module: 'app.safe', kind: 'health', writable: true },
    attachTo: document.body,
  });
  await vi.waitFor(() => expect(w.find('textarea').exists()).toBe(true));
  let finish!: (value: Response) => void;
  fetch.mockImplementation(
    () =>
      new Promise<Response>((resolve) => {
        finish = resolve;
      }),
  );
  await w
    .findAll('button')
    .find((button) => button.text() === 'Reload current state')!
    .trigger('click');
  await flushPromises();
  const input = w.get('textarea');
  expect((input.element as HTMLTextAreaElement).disabled).toBe(false);
  await input.setValue('{"newer":"draft"}');
  finish(
    response({
      generation: 2,
      health_policy: true,
      health_overrides: { old: 'response' },
    }),
  );
  await flushPromises();
  expect((input.element as HTMLTextAreaElement).value).toBe(
    '{"newer":"draft"}',
  );
  expect(controller.dirty.value).toBe(true);
});

it('Admin subviews use top navigation and a cancelled dirty transition preserves its URL and draft', async () => {
  const { ApplicationLayout, createPreferenceStore } =
    await import('@/runa/ui');
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/capabilities')
      ? response({ capabilities: ['diagnostics.read'] })
      : String(input).endsWith('/application')
        ? response(applicationState(1, 'Source'))
        : response({ modules: [] }),
  );
  const owner = admin();
  const w = mount(
    defineComponent({
      setup: () => () =>
        h(
          ApplicationLayout,
          {
            title: 'Admin',
            navigation: [],
            preferenceStore: createPreferenceStore(),
          },
          { default: () => h(AdminOperations, { admin: owner }) },
        ),
    }),
    { attachTo: document.body },
  );
  await vi.waitFor(() =>
    expect(
      w.find('header .shell-top-nav a[href*="section=application"]').exists(),
    ).toBe(true),
  );
  expect(
    w
      .findAll('main button')
      .some((button) => button.text() === 'Application settings'),
  ).toBe(false);
  await w
    .get('header .shell-top-nav a[href*="section=application"]')
    .trigger('click');
  await vi.waitFor(() =>
    expect(w.findComponent(AdminApplicationSettings).exists()).toBe(true),
  );
  const settings = w.getComponent(AdminApplicationSettings).vm.settings;
  await vi.waitFor(() => expect(settings.loaded.value).toBe(true));
  settings.edit('presentation.name', 'Unsaved');
  const controller = w.getComponent(AdminOperations).vm.controller;
  await w
    .get('header .shell-top-nav a:not([href*="section=application"])')
    .trigger('click');
  await vi.waitFor(() => expect(controller.confirmation.value).toBeDefined());
  controller.confirm(false);
  await flushPromises();
  expect(new URL(window.location.href).searchParams.get('section')).toBe(
    'application',
  );
  expect(settings.draft.value['presentation.name']).toBe('Unsaved');
  await w
    .get('header .shell-top-nav a:not([href*="section=application"])')
    .trigger('click');
  await vi.waitFor(() => expect(controller.confirmation.value).toBeDefined());
  controller.confirm(true);
  await vi.waitFor(() =>
    expect(w.findComponent(AdminApplicationSettings).exists()).toBe(false),
  );
  expect(new URL(window.location.href).searchParams.has('section')).toBe(false);
});

it('Admin module subviews restore deep links inside a grant-filtered dialog', async () => {
  window.history.replaceState(
    {},
    '',
    '/admin?module=app.safe&section=settings',
  );
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/capabilities')
      ? response({ capabilities: ['diagnostics.read', 'settings.read'] })
      : String(input).includes('/diagnostics')
        ? response({ modules: [] })
        : response({
            module: 'app.safe',
            generation: 1,
            revision: 1,
            settings: {},
          }),
  );
  const w = mount(AdminOperations, {
    props: { admin: admin() },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(document.body.textContent).toContain('No settings are declared.'),
  );
  expect(w.getComponent(AdminOperationalEditor).props('module')).toBe(
    'app.safe',
  );
  const dialog = document.body.querySelector('[role="dialog"]')!;
  expect(dialog).not.toBeNull();
  const tabs = Array.from(dialog.querySelectorAll('[role="tab"]')).map((tab) =>
    tab.textContent?.trim(),
  );
  expect(tabs).toEqual(['Overview', 'Settings']);
  expect(
    fetch.mock.calls.filter(([url]) => String(url).includes('/diagnostics')),
  ).toHaveLength(1);
  expect(new URL(window.location.href).searchParams.get('section')).toBe(
    'settings',
  );
});

it('dashboard filtering refreshes immediately, shows progress and keeps the latest filter response', async () => {
  const { Select, IdentityProvider } = await import('@/runa/ui');
  const pending: {
    filter: string;
    signal?: AbortSignal | null | undefined;
    finish: (response: Response) => void;
  }[] = [];
  const module = (id: string) => ({
    id,
    kind: 'optional',
    registered: true,
    enabled: true,
    health: 'healthy',
    available: true,
    source_version: '1.0.0',
    installed_version: '1.0.0',
    reasons: [],
    required: {},
    optional: {},
    observations: [],
  });
  const snapshot = (id: string) => ({
    modules: [module(id)],
    platform_version: '0.7.0',
    application_version: '3.2.1',
    runtime: { php: '8.4.27' },
  });
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname.endsWith('/capabilities'))
      return response({ capabilities: ['diagnostics.read'] });
    const filter = url.searchParams.get('filter') ?? 'enabled';
    if (filter === 'enabled') return response(snapshot('app.first'));
    return new Promise((finish) =>
      pending.push({ filter, signal: init?.signal, finish }),
    );
  });
  const w = mount(
    defineComponent({
      setup: () => () =>
        h(
          IdentityProvider,
          { identity: { applicationName: 'Customer CRM' } },
          { default: () => h(AdminOperations, { admin: admin() }) },
        ),
    }),
    { attachTo: document.body },
  );
  await vi.waitFor(() =>
    expect(w.find('[data-module="app.first"]').exists()).toBe(true),
  );
  const runtime = w.get('[data-admin-runtime]').text();
  expect(runtime).toContain('Runa 0.7.0');
  expect(runtime).toContain('Customer CRM 3.2.1');
  expect(runtime).toContain('PHP 8.4.27');
  expect(runtime).not.toContain('{');
  expect(w.text()).not.toContain('Refresh observations');
  w.getComponent(Select).vm.$emit('update:modelValue', 'registered');
  await vi.waitFor(() => expect(pending).toHaveLength(1));
  expect(w.find('[data-diagnostics-refresh]').exists()).toBe(true);
  const retained = w.get('[data-module="app.first"]').element;
  w.getComponent(Select).vm.$emit('update:modelValue', 'all');
  await vi.waitFor(() => expect(pending).toHaveLength(2));
  expect(pending[0]!.signal?.aborted).toBe(true);
  expect(w.get('[data-module="app.first"]').element).toBe(retained);
  pending[1]!.finish(response(snapshot('app.latest')));
  await vi.waitFor(() =>
    expect(w.find('[data-module="app.latest"]').exists()).toBe(true),
  );
  pending[0]!.finish(response(snapshot('app.stale')));
  await flushPromises();
  expect(w.find('[data-module="app.stale"]').exists()).toBe(false);
  expect(w.find('[data-diagnostics-refresh]').exists()).toBe(false);
});

it('module dialog guards close and tab transitions without losing a dirty draft', async () => {
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = String(input);
    if (url.endsWith('/capabilities'))
      return response({
        capabilities: ['diagnostics.read', 'settings.read', 'settings.manage'],
      });
    if (url.includes('/diagnostics'))
      return response({
        modules: [
          {
            id: 'app.safe',
            kind: 'optional',
            enabled: true,
            registered: true,
            available: true,
            health: 'healthy',
          },
        ],
      });
    return response({
      module: 'app.safe',
      generation: 1,
      revision: 1,
      settings: {
        title: {
          definition: {
            type: 'string',
            nullable: true,
            names: { pl: 'Nazwa', en: 'Name' },
          },
          value: 'Original',
        },
      },
    });
  });
  const w = mount(AdminOperations, {
    props: { admin: admin() },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.find('[data-module="app.safe"] button').exists()).toBe(true),
  );
  const trigger = w.get<HTMLButtonElement>('[data-module="app.safe"] button');
  trigger.element.focus();
  await trigger.trigger('click');
  await vi.waitFor(() =>
    expect(document.body.querySelector('[role="dialog"]')).not.toBeNull(),
  );
  const tab = Array.from(
    document.body.querySelectorAll<HTMLButtonElement>('[role="tab"]'),
  ).find((tab) => tab.textContent?.trim() === 'Settings')!;
  tab.click();
  await vi.waitFor(() =>
    expect(w.findComponent(AdminOperationalEditor).exists()).toBe(true),
  );
  const editor = w.getComponent(AdminOperationalEditor);
  await vi.waitFor(() => expect(editor.find('input').exists()).toBe(true));
  await editor.get('input').setValue('Unsaved name');
  const controller = w.vm.controller;
  const close = document.body.querySelector<HTMLButtonElement>(
    '[aria-label="Close dialog"]',
  )!;
  close.click();
  await vi.waitFor(() => expect(controller.confirmation.value).toBeDefined());
  controller.confirm(false);
  await flushPromises();
  expect(new URL(window.location.href).searchParams.get('section')).toBe(
    'settings',
  );
  expect(editor.get('input').element.value).toBe('Unsaved name');
  close.click();
  await vi.waitFor(() => expect(controller.confirmation.value).toBeDefined());
  controller.confirm(true);
  await vi.waitFor(() =>
    expect(new URL(window.location.href).searchParams.has('module')).toBe(
      false,
    ),
  );
  expect(w.findComponent(AdminOperationalEditor).exists()).toBe(false);
});

it('an operations slot replacement owns its module dialog presentation', async () => {
  window.history.replaceState({}, '', '/admin?module=app.safe');
  vi.spyOn(window, 'fetch').mockResolvedValue(response({ capabilities: [] }));
  const w = mount(AdminOperations, {
    props: { admin: admin() },
    slots: { default: () => h('p', 'Custom operations') },
    attachTo: document.body,
  });
  await vi.waitFor(() => expect(w.text()).toContain('Custom operations'));
  expect(document.body.querySelector('[role="dialog"]')).toBeNull();
});
