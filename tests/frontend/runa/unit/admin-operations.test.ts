/* eslint-disable vue/one-component-per-file -- Independent mounted operation and navigation harnesses. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  AdminApplicationSettings,
  useAdminApplicationSettings,
  AdminOperations,
  AdminOperationalEditor,
  AdminOperationResult,
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
  await controller.execute('integrations.configure', {
    module: 'app.example',
    generation: 1,
    key: 'history',
    enabled: true,
    config: {},
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
  await controller.execute('integrations.configure', {
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
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      generation: 1,
      settings: {
        title: {
          definition: { type: 'string', names: { en: 'Title', pl: 'Tytuł' } },
          value: 'Original',
        },
      },
    }),
  );
  const { controller } = harness();
  const w = mount(AdminOperationalEditor, {
    props: { controller, module: 'app.safe', kind: 'settings', writable: true },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.find('input[type="text"]').exists()).toBe(true),
  );
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
  const input = w.get('input[type="text"]');
  expect((input.element as HTMLTextAreaElement).disabled).toBe(false);
  await input.setValue('Newer draft');
  finish(
    response({
      generation: 2,
      settings: {
        title: {
          definition: { type: 'string', names: { en: 'Title', pl: 'Tytuł' } },
          value: 'Old response',
        },
      },
    }),
  );
  await flushPromises();
  expect((input.element as HTMLTextAreaElement).value).toBe('Newer draft');
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

it('dashboard filtering replaces cards with content skeletons and keeps the latest filter response', async () => {
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
  expect(w.find('[data-diagnostics-refresh]').exists()).toBe(false);
  expect(w.find('[data-module-filter-loading] .app-skeleton').exists()).toBe(
    true,
  );
  expect(w.find('[data-module="app.first"]').exists()).toBe(false);
  w.getComponent(Select).vm.$emit('update:modelValue', 'all');
  await vi.waitFor(() => expect(pending).toHaveLength(2));
  expect(pending[0]!.signal?.aborted).toBe(true);
  expect(w.find('[data-module="app.first"]').exists()).toBe(false);
  expect(w.find('[data-module-filter-loading]').exists()).toBe(true);
  pending[1]!.finish(response(snapshot('app.latest')));
  await vi.waitFor(() =>
    expect(w.find('[data-module="app.latest"]').exists()).toBe(true),
  );
  pending[0]!.finish(response(snapshot('app.stale')));
  await flushPromises();
  expect(w.find('[data-module="app.stale"]').exists()).toBe(false);
  expect(w.find('[data-module-filter-loading]').exists()).toBe(false);
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

it.each(['selection', 'deep link'])(
  'module diagnosis retains its panel and limits progress to the clicked operation; task history loads automatically, refreshes and stays in overview (%s)',
  async (entry) => {
    const { AdminOperationResult } = await import('@/runa/admin');
    let finishDiagnosis!: (response: Response) => void;
    const workReads: ((response: Response) => void)[] = [];
    vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
      const path = String(input).split('?')[0]!;
      if (path.endsWith('/capabilities'))
        return response({
          capabilities: [
            'diagnostics.read',
            'health.diagnose',
            'settings.read',
            'work.read',
            'modules.manage',
          ],
        });
      if (path.endsWith('/diagnostics'))
        return response({
          modules: [
            {
              id: 'app.safe',
              kind: 'optional',
              registered: true,
              enabled: true,
              available: true,
              health: 'healthy',
            },
          ],
        });
      if (path.endsWith('/history'))
        return response({ operations: [], next_cursor: null });
      if (path.endsWith('/health.diagnose'))
        return new Promise((finish) => {
          finishDiagnosis = finish;
        });
      if (path.endsWith('/work'))
        return new Promise((finish) => workReads.push(finish));
      return response({});
    });
    if (entry === 'deep link')
      window.history.replaceState({}, '', '/admin?module=app.safe');
    const w = mount(AdminOperations, {
      props: { admin: admin() },
      attachTo: document.body,
    });
    await vi.waitFor(() =>
      expect(w.find('[data-module="app.safe"] button').exists()).toBe(true),
    );
    if (entry === 'selection')
      await w.get('[data-module="app.safe"] button').trigger('click');
    const button = (label: string) =>
      Array.from(
        document.body.querySelectorAll<HTMLButtonElement>(
          '[role="dialog"] button',
        ),
      ).find((button) => button.textContent?.trim() === label)!;
    await vi.waitFor(() => expect(workReads).toHaveLength(1));
    workReads[0]!(response({ work: [], truncated: false }));
    await vi.waitFor(() =>
      expect(button('Refresh tasks')?.disabled).toBe(false),
    );
    expect(
      document.body.querySelector('[data-active-work] table'),
    ).not.toBeNull();
    expect(button('Active work')).toBeUndefined();
    await vi.waitFor(() =>
      expect(button('Diagnose now')?.disabled).toBe(false),
    );
    const panel =
      document.body.querySelector<HTMLElement>('[role="tabpanel"]')!;
    const card = panel.querySelector('[data-module="app.safe"]');
    button('Diagnose now').click();
    await vi.waitFor(() => expect(finishDiagnosis).toBeTypeOf('function'));
    expect(panel.hidden).toBe(false);
    expect(panel.querySelector('[data-module="app.safe"]')).toBe(card);
    expect(button('Diagnose now').getAttribute('aria-busy')).toBe('true');
    expect(button('Refresh tasks').getAttribute('aria-busy')).not.toBe('true');
    finishDiagnosis(
      response({
        module: 'app.safe',
        health: 'healthy',
        blocked: false,
        reasons: [],
      }),
    );
    await vi.waitFor(() =>
      expect(w.findComponent(AdminOperationResult).exists()).toBe(true),
    );
    const result = w.getComponent(AdminOperationResult);
    expect(result.attributes('data-tone')).toBe('success');
    expect(result.text()).not.toContain('app.safe');
    expect(result.element.closest('[data-module-diagnosis]')).not.toBeNull();
    expect(
      panel.querySelector('[data-module-diagnosis] button')?.textContent,
    ).toContain('Diagnose now');
    expect(panel.hidden).toBe(false);
    await vi.waitFor(() => expect(workReads).toHaveLength(2));
    workReads[1]!(response({ work: [], truncated: false }));
    await vi.waitFor(() =>
      expect(button('Refresh tasks').disabled).toBe(false),
    );
    button('Refresh tasks').click();
    await vi.waitFor(() => expect(workReads).toHaveLength(3));
    expect(document.body.querySelector('[data-active-work]')).not.toBeNull();
    expect(button('Refresh tasks').getAttribute('aria-busy')).toBe('true');
    workReads[2]!(response({ work: [], truncated: false }));
    await vi.waitFor(() =>
      expect(button('Refresh tasks').getAttribute('aria-busy')).not.toBe(
        'true',
      ),
    );
    expect(
      document.body.querySelector('[data-active-work] table'),
    ).not.toBeNull();
    button('Refresh tasks').click();
    await vi.waitFor(() => expect(workReads).toHaveLength(4));
    workReads[3]!(
      response({
        work: [
          {
            workId: id,
            operation: 'Report export',
            status: 'succeeded',
            cancellable: true,
            progress: '1/5',
          },
        ],
        truncated: false,
      }),
    );
    await vi.waitFor(() =>
      expect(
        document.body.querySelector('[data-active-work]')?.textContent,
      ).toContain('Report export'),
    );
    expect(button('Request cancellation')).toBeUndefined();
    const settingsTab = Array.from(
      document.body.querySelectorAll<HTMLButtonElement>('[role="tab"]'),
    ).find((tab) => tab.textContent?.trim() === 'Settings')!;
    settingsTab.click();
    await vi.waitFor(() =>
      expect(window.location.search).toContain('section=settings'),
    );
    await vi.waitFor(() => {
      expect(w.findComponent(AdminOperationResult).exists()).toBe(false);
      expect(document.body.querySelector('[data-active-work]')).toBeNull();
      expect(document.body.textContent).not.toContain('Operation history');
    });
  },
);

it('diagnostic outcomes use semantic colors and explain failures without duplicating the module identity', async () => {
  const { AdminOperationResult, AdminModuleCard } =
    await import('@/runa/admin');
  const result = mount(AdminOperationResult, {
    props: {
      result: {
        module: 'runa.admin',
        health: 'unhealthy',
        blocked: true,
        reasons: ['health_check_error'],
      },
    },
  });
  expect(result.attributes('data-tone')).toBe('danger');
  expect(result.text()).toContain('A health check could not complete.');
  expect(result.text()).not.toContain('runa.admin');
  await result.setProps({
    result: {
      module: 'runa.admin',
      health: 'degraded',
      blocked: false,
      reasons: [],
    },
  });
  expect(result.attributes('data-tone')).toBe('warning');
  const card = mount(AdminModuleCard, {
    props: {
      module: {
        id: 'runa.admin',
        kind: 'core',
        registered: true,
        enabled: true,
        available: true,
        health: 'healthy',
      },
      details: true,
      interactive: false,
    },
  });
  expect(
    card.findAll('.app-badge').map((badge) => badge.attributes('data-tone')),
  ).toEqual(['success', 'info', 'success', 'success']);
});

it('native operation history renders safe rows and row actions with paged reads instead of UUID input', async () => {
  const { AdminOperationHistory } = await import('@/runa/admin');
  const row = {
    public_id: id,
    target: 'app.safe',
    intent: 'disable',
    status: 'pending',
    created_at: '2026-10-31T10:00:00Z',
    updated_at: '2026-10-31T10:01:00Z',
    actions: ['modules.status', 'modules.resume', 'modules.cancel'],
  };
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.searchParams.has('cursor'))
      return response({
        operations: [
          {
            ...row,
            public_id: '01900000-0000-7000-8000-000000000002',
            intent: 'settings',
            status: 'succeeded',
            actions: ['settings.status'],
          },
        ],
        next_cursor: null,
      });
    return response({ operations: [row], next_cursor: id });
  });
  const { controller } = harness();
  const w = mount(AdminOperationHistory, {
    props: { controller, module: 'app.safe' },
    attachTo: document.body,
  });
  await vi.waitFor(() => expect(w.text()).toContain('Resume native workflow'));
  expect(w.find('input[name="id"]').exists()).toBe(false);
  expect(fetch.mock.calls[0]?.[0]).toContain('module=app.safe');
  await w
    .findAll('button')
    .find((button) => button.text() === 'Resume native workflow')!
    .trigger('click');
  expect(w.emitted('execute')?.[0]).toEqual(['modules.resume', id]);
  await w
    .findAll('button')
    .find((button) => button.text() === 'Load older operations')!
    .trigger('click');
  await vi.waitFor(() => expect(w.text()).toContain('Settings change'));
  expect(
    fetch.mock.calls.some(([url]) => String(url).includes('cursor=' + id)),
  ).toBe(true);
  expect(
    w
      .findAll('button')
      .filter((button) => button.text() === 'Resume native workflow'),
  ).toHaveLength(1);
});

it.each(['enable', 'disable', 'unregister'] as const)(
  'module %s action reviews effects in one modal before applying the exact token',
  async (intent) => {
    const owner = admin();
    const submitted: { operation: string; input: unknown }[] = [];
    let finishPreview!: (response: Response) => void;
    vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
      const path = new URL(String(input), window.location.origin).pathname;
      if (path.endsWith('/capabilities'))
        return response({
          capabilities: ['diagnostics.read', 'modules.manage'],
        });
      if (path.endsWith('/diagnostics'))
        return response({
          modules: [
            {
              id: 'app.safe',
              kind: 'optional',
              registered: true,
              enabled: intent === 'disable',
              health: 'healthy',
              available: true,
            },
          ],
        });
      if (path.endsWith('/history'))
        return response({ operations: [], next_cursor: null });
      if (init?.method === 'POST')
        submitted.push({
          operation: path.split('/').pop()!,
          input: JSON.parse(String(init.body)),
        });
      if (path.endsWith('/modules.preview'))
        return new Promise((resolve) => {
          finishPreview = resolve;
        });
      if (path.endsWith('/modules.apply'))
        return response({ transition: { operationId: id, status: 'pending' } });
      return response({});
    });
    const w = mount(AdminOperations, {
      props: { admin: owner },
      attachTo: document.body,
    });
    await vi.waitFor(() =>
      expect(w.find('[data-module="app.safe"] button').exists()).toBe(true),
    );
    await w.get('[data-module="app.safe"] button').trigger('click');
    const label =
      intent === 'enable'
        ? 'Enable module'
        : intent === 'disable'
          ? 'Disable module'
          : 'Unregister module';
    const controls = () =>
      document.body.querySelector('[data-module-lifecycle-controls]')!;
    const action = () =>
      Array.from(controls().querySelectorAll('button')).find(
        (button) => button.textContent?.trim() === label,
      )!;
    await vi.waitFor(() => expect(action()?.disabled).toBe(false));
    expect(
      controls().compareDocumentPosition(
        document.body.querySelector(
          '[role="tabpanel"] [data-module="app.safe"]',
        )!,
      ) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
    expect(controls().querySelector('select, .selection-trigger')).toBeNull();
    expect(controls().textContent).not.toContain('Core targets');
    expect(controls().textContent).not.toContain('Preview transition');
    action().focus();
    action().click();
    await vi.waitFor(() => expect(finishPreview).toBeTypeOf('function'));
    const review = () =>
      document.body.querySelector('[data-module-lifecycle-review]')!;
    const dialog = () => review().closest('[role="dialog"]')!;
    const confirm = () =>
      Array.from(dialog().querySelectorAll('button')).find(
        (button) => button.textContent?.trim() === label,
      )!;
    expect(review().querySelector('.app-skeleton')).not.toBeNull();
    expect(confirm().disabled).toBe(true);
    expect(submitted).toEqual([
      { operation: 'modules.preview', input: { module: 'app.safe', intent } },
    ]);
    finishPreview(
      response({
        ready: true,
        review: 'reviewed-token',
        affected: ['app.safe'],
        changes: { 'app.safe': intent === 'enable' },
        active_work: { 'app.safe': { pending: 2 } },
        blockers: [],
      }),
    );
    await vi.waitFor(() => expect(confirm().disabled).toBe(false));
    expect(review().textContent).toContain('Affected modules');
    expect(review().textContent).toContain('Active work');
    expect(review().textContent).toContain('pending: 2');
    (
      dialog().querySelector('[data-lifecycle-cancel]') as HTMLButtonElement
    ).click();
    await vi.waitFor(() => expect(document.activeElement).toBe(action()));
    expect(
      submitted.filter((item) => item.operation === 'modules.apply'),
    ).toHaveLength(0);
    action().click();
    await vi.waitFor(() => expect(submitted).toHaveLength(2));
    finishPreview(
      response({
        ready: true,
        review: 'fresh-token',
        affected: ['app.safe'],
        blockers: [],
      }),
    );
    await vi.waitFor(() => expect(confirm().disabled).toBe(false));
    confirm().click();
    await vi.waitFor(() =>
      expect(
        submitted.filter((item) => item.operation === 'modules.apply'),
      ).toEqual([
        {
          operation: 'modules.apply',
          input: { review: 'fresh-token', confirmed: true },
        },
      ]),
    );
    expect(w.vm.controller.confirmation.value).toBeUndefined();
    await vi.waitFor(() =>
      expect(w.getComponent(AdminOperationResult).text()).toContain('pending'),
    );
  },
);

it.each(['blocked', 'failed apply', 'authority loss'])(
  'module lifecycle confirmation handles %s without replaying a mutation',
  async (scenario) => {
    const owner = admin();
    const posts: string[] = [];
    let finishPreview!: (response: Response) => void;
    vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
      const path = String(input).split('?')[0]!;
      if (path.endsWith('/capabilities'))
        return response({
          capabilities: ['diagnostics.read', 'modules.manage'],
        });
      if (path.endsWith('/diagnostics'))
        return response({
          modules: [
            {
              id: 'app.safe',
              kind: 'optional',
              registered: true,
              enabled: true,
              health: 'healthy',
              available: true,
            },
          ],
        });
      if (path.endsWith('/history'))
        return response({ operations: [], next_cursor: null });
      if (init?.method === 'POST') posts.push(path.split('/').pop()!);
      if (path.endsWith('/modules.preview'))
        return new Promise((resolve) => {
          finishPreview = resolve;
        });
      if (path.endsWith('/modules.apply')) return response({}, 409);
      return response({});
    });
    const w = mount(AdminOperations, {
      props: { admin: owner },
      attachTo: document.body,
    });
    await vi.waitFor(() =>
      expect(w.find('[data-module="app.safe"] button').exists()).toBe(true),
    );
    await w.get('[data-module="app.safe"] button').trigger('click');
    const action = () =>
      Array.from(
        document.body.querySelectorAll<HTMLButtonElement>(
          '[data-module-lifecycle-controls] button',
        ),
      ).find((button) => button.textContent?.trim() === 'Disable module')!;
    await vi.waitFor(() => expect(action()?.disabled).toBe(false));
    action().click();
    await vi.waitFor(() => expect(finishPreview).toBeTypeOf('function'));
    if (scenario === 'authority loss') owner.session.authorityRevision.value++;
    finishPreview(
      response({
        ready: scenario !== 'blocked',
        review: 'unsafe-old-token',
        affected: ['app.safe'],
        blockers: scenario === 'blocked' ? ['disable_before_unregister'] : [],
      }),
    );
    await flushPromises();
    const review = () =>
      document.body.querySelector('[data-module-lifecycle-review]');
    if (scenario === 'authority loss') {
      await vi.waitFor(() => expect(review()).toBeNull());
      expect(posts).toEqual(['modules.preview']);
      return;
    }
    const dialog = () => review()!.closest('[role="dialog"]')!;
    const confirm = () =>
      Array.from(dialog().querySelectorAll<HTMLButtonElement>('button')).find(
        (button) => button.textContent?.trim() === 'Disable module',
      )!;
    await vi.waitFor(() =>
      expect(review()?.querySelector('.app-skeleton')).toBeNull(),
    );
    if (scenario === 'blocked') {
      expect(confirm().disabled).toBe(true);
      expect(review()?.textContent).toContain('Transition blocked');
      expect(posts).toEqual(['modules.preview']);
      return;
    }
    await vi.waitFor(() => expect(confirm().disabled).toBe(false));
    confirm().click();
    await vi.waitFor(() =>
      expect(review()?.textContent).toContain('Refresh and review'),
    );
    expect(confirm().disabled).toBe(true);
    expect(review()?.textContent).toContain('Review again');
    expect(posts).toEqual(['modules.preview', 'modules.apply']);
    expect(w.vm.controller.confirmation.value).toBeUndefined();
  },
);
