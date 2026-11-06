/* eslint-disable vue/one-component-per-file -- Independent mounted operation and navigation harnesses. */
import { savePreference } from '../../../../src/runa/resources/js/lib/preferences';
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  AdminApplicationSettings,
  useAdminApplicationSettings,
  AdminOperations,
  AdminOperationResult,
  useAdminOperations,
  useAdminLocale,
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
  savePreference('runa.locale', 'en');
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
      open: ref(false),
      busy: ref(false),
      confirmed: ref(false),
      error: computed(() => ''),
      requirement: computed(() => 'password'),
      begin: vi.fn(async () => {}),
      confirm: vi.fn(async () => {}),
      request: vi.fn(async () => true),
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
  await controller.execute('application.update', { revision: 1, patch: {} });
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
  expect(wrapper.find('h3').exists()).toBe(false);
  await vi.waitFor(() => expect(wrapper.text()).toContain('Read only.'));
  expect(wrapper.find('input[disabled]').exists()).toBe(true);
  const reset = wrapper
    .findAll('button')
    .find(
      (button) =>
        button.attributes('aria-label') ===
        'Restore defaults: Displayed application name',
    );
  await reset!.trigger('click');
  expect(controller.dirty.value).toBe(true);
  expect(
    wrapper
      .findAll('button')
      .some(
        (button) =>
          button.attributes('aria-label') ===
          'Restore defaults: Business label',
      ),
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
  await controller.execute('work.cancel', { id });
  finish(response({ modules: ['Old read'] }));
  await read;
  expect(controller.snapshot.value).toBeUndefined();
  expect(controller.refreshing.value).toBe(false);
  expect(controller.busy.value).toBe(false);
  expect(fetch).toHaveBeenCalledTimes(2);
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

it.each(['integrations', 'settings', 'health'])(
  'old module %s deep links open plain content without tabs or integration requests',
  async (section) => {
    window.history.replaceState(
      {},
      '',
      `/admin?module=app.safe&section=${section}`,
    );
    const fetch = vi
      .spyOn(window, 'fetch')
      .mockImplementation(async (input) =>
        String(input).endsWith('/capabilities')
          ? response({ capabilities: ['diagnostics.read'] })
          : response({ modules: [] }),
      );
    mount(AdminOperations, {
      props: { admin: admin() },
      attachTo: document.body,
    });
    await vi.waitFor(() =>
      expect(
        document.body.querySelector('[data-module-content]'),
      ).not.toBeNull(),
    );
    const dialog = document.body.querySelector('[role="dialog"]')!;
    expect(dialog.querySelector('[role="tablist"]')).toBeNull();
    expect(dialog.querySelector('textarea')).toBeNull();
    expect(
      fetch.mock.calls.some(([url]) =>
        String(url).includes('/operations/integrations'),
      ),
    ).toBe(false);
  },
);

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

it('module dialog closes directly and restores focus to its card without tab navigation', async () => {
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/capabilities')
      ? response({ capabilities: ['diagnostics.read'] })
      : response({
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
        }),
  );
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
  expect(document.body.querySelector('[role="tablist"]')).toBeNull();
  document.body
    .querySelector<HTMLButtonElement>('[aria-label="Close dialog"]')!
    .click();
  await vi.waitFor(() =>
    expect(new URL(window.location.href).searchParams.has('module')).toBe(
      false,
    ),
  );
  await vi.waitFor(() => expect(document.activeElement).toBe(trigger.element));
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
      ).find((button) =>
        label === 'Refresh tasks'
          ? button.matches('[data-active-work] button[aria-label="Refresh"]')
          : button.textContent?.trim() === label,
      )!;
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
    const panel = document.body.querySelector<HTMLElement>(
      '[data-module-content]',
    )!;
    const card = panel.querySelector('[data-module="app.safe"]');
    button('Diagnose now').focus();
    button('Diagnose now').click();
    await vi.waitFor(() => expect(finishDiagnosis).toBeTypeOf('function'));
    expect(panel.hidden).toBe(false);
    expect(panel.querySelector('[data-module="app.safe"]')).toBe(card);
    expect(button('Diagnose now').getAttribute('aria-busy')).toBe('true');
    expect(button('Refresh tasks').querySelector('.animate-spin')).toBeNull();
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
    expect(result.element.closest('[role="dialog"]')).not.toBe(
      panel.closest('[role="dialog"]'),
    );
    expect(panel.querySelector('[data-module-diagnosis]')).toBeNull();
    await vi.waitFor(() =>
      expect(
        document.body.querySelector<HTMLButtonElement>('[data-diagnosis-close]')
          ?.disabled,
      ).toBe(false),
    );
    document.body
      .querySelector<HTMLButtonElement>('[data-diagnosis-close]')!
      .click();
    await vi.waitFor(() =>
      expect(document.activeElement).toBe(button('Diagnose now')),
    );
    expect(panel.hidden).toBe(false);
    await vi.waitFor(() => expect(workReads).toHaveLength(2));
    workReads[1]!(response({ work: [], truncated: false }));
    await vi.waitFor(() =>
      expect(button('Refresh tasks').disabled).toBe(false),
    );
    button('Refresh tasks').click();
    await vi.waitFor(() => expect(workReads).toHaveLength(3));
    expect(document.body.querySelector('[data-active-work]')).not.toBeNull();
    expect(
      button('Refresh tasks').querySelector('.animate-spin'),
    ).not.toBeNull();
    workReads[2]!(response({ work: [], truncated: false }));
    await vi.waitFor(() =>
      expect(button('Refresh tasks').querySelector('.animate-spin')).toBeNull(),
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
    document.body
      .querySelector<HTMLButtonElement>('[aria-label="Close dialog"]')!
      .click();
    await vi.waitFor(() =>
      expect(window.location.search).not.toContain('module='),
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
          '[data-module-content] [data-module="app.safe"]',
        )!,
      ) & Node.DOCUMENT_POSITION_FOLLOWING,
    ).toBeTruthy();
    expect(controls().querySelector('select, .selection-trigger')).toBeNull();
    expect(controls().textContent).not.toContain('Core targets');
    expect(document.body.textContent).not.toContain('Optional lifecycle');
    expect(controls().textContent).not.toContain('Preview transition');
    action().focus();
    action().click();
    await vi.waitFor(() => expect(finishPreview).toBeTypeOf('function'));
    const review = () =>
      document.body.querySelector('[data-module-lifecycle-review]')!;
    const dialog = () =>
      review()?.closest('[role="dialog"]') ??
      Array.from(document.body.querySelectorAll('[role="dialog"]')).at(-1)!;
    const confirm = () =>
      Array.from(dialog().querySelectorAll('button')).find(
        (button) => button.textContent?.trim() === label,
      )!;
    await vi.waitFor(() =>
      expect(
        document.querySelector('[role=dialog] .app-skeleton'),
      ).not.toBeNull(),
    );
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
      Array.from(dialog().querySelectorAll('button')).find(
        (button) => button.textContent?.trim() === 'Cancel',
      ) as HTMLButtonElement
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
      expect(review()?.textContent).toContain('Review again'),
    );
    expect(confirm().disabled).toBe(true);
    expect(review()?.textContent).toContain('Review again');
    expect(posts).toEqual(['modules.preview', 'modules.apply']);
    expect(w.vm.controller.confirmation.value).toBeUndefined();
  },
);

it.each(['failure', 'authority loss'])(
  'diagnosis modal handles %s without retaining stale results or replaying requests',
  async (scenario) => {
    const owner = admin();
    let finish!: (response: Response) => void;
    let calls = 0;
    vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
      const path = String(input).split('?')[0]!;
      if (path.endsWith('/capabilities'))
        return response({
          capabilities: ['diagnostics.read', 'health.diagnose'],
        });
      if (path.endsWith('/diagnostics'))
        return response({
          modules: [
            {
              id: 'runa.audit',
              kind: 'core',
              enabled: true,
              registered: true,
              available: true,
              health: 'healthy',
            },
          ],
        });
      if (path.endsWith('/health.diagnose')) {
        calls++;
        return new Promise((resolve) => {
          finish = resolve;
        });
      }
      return response({});
    });
    const w = mount(AdminOperations, {
      props: { admin: owner },
      attachTo: document.body,
    });
    await vi.waitFor(() =>
      expect(w.find('[data-module="runa.audit"] button').exists()).toBe(true),
    );
    await w.get('[data-module="runa.audit"] button').trigger('click');
    const trigger = () =>
      document.body.querySelector<HTMLButtonElement>(
        '[data-module-actions] button',
      )!;
    await vi.waitFor(() => expect(trigger()?.disabled).toBe(false));
    expect(
      document.body.querySelector('[data-module-lifecycle-controls]'),
    ).toBeNull();
    expect(trigger().classList.contains('ml-auto')).toBe(true);
    trigger().focus();
    trigger().click();
    await vi.waitFor(() => expect(calls).toBe(1));
    const modal = () => document.body.querySelector('[data-module-diagnosis]');
    expect(modal()?.querySelector('.app-skeleton')).not.toBeNull();
    if (scenario === 'authority loss') owner.session.authorityRevision.value++;
    finish(
      scenario === 'failure'
        ? response({}, 409)
        : response({ health: 'healthy', blocked: false, reasons: [] }),
    );
    if (scenario === 'authority loss') {
      await vi.waitFor(() => expect(modal()).toBeNull());
      expect(w.findComponent(AdminOperationResult).exists()).toBe(false);
    } else {
      await vi.waitFor(() =>
        expect(modal()?.closest('.dialog-backdrop')?.textContent).toContain(
          'Retry',
        ),
      );
      expect(modal()?.querySelector('.app-skeleton')).toBeNull();
      const dialog = modal()!.closest('[role="dialog"]')!;
      expect(
        Array.from(dialog.querySelectorAll('button')).some(
          (button) => button.textContent?.trim() === 'Retry',
        ),
      ).toBe(true);
      (
        dialog.querySelector('[data-diagnosis-close]') as HTMLButtonElement
      ).click();
      await vi.waitFor(() => expect(document.activeElement).toBe(trigger()));
    }
    expect(calls).toBe(1);
  },
);

it.each(['enable', 'disable', 'unregister'] as const)(
  'Polish module %s confirmation translates its cancel action',
  async (intent) => {
    savePreference('runa.locale', 'pl');
    vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
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
              enabled: intent === 'disable',
              health: 'healthy',
              available: true,
            },
          ],
        });
      if (path.endsWith('/history'))
        return response({ operations: [], next_cursor: null });
      if (path.endsWith('/modules.preview'))
        return response({
          ready: true,
          review: 'token',
          affected: ['app.safe'],
          blockers: [],
        });
      return response({});
    });
    let language!: ReturnType<typeof useAdminLocale>;
    const owner = admin();
    const w = mount(
      defineComponent({
        setup() {
          language = useAdminLocale();
          language.locale.value = 'pl';
          return () => h(AdminOperations, { admin: owner });
        },
      }),
      { attachTo: document.body },
    );
    await vi.waitFor(() =>
      expect(w.find('[data-module="app.safe"] button').exists()).toBe(true),
    );
    await w.get('[data-module="app.safe"] button').trigger('click');
    const label =
      intent === 'enable'
        ? 'Włącz moduł'
        : intent === 'disable'
          ? 'Wyłącz moduł'
          : 'Wyrejestruj moduł';
    const action = () =>
      Array.from(
        document.body.querySelectorAll<HTMLButtonElement>(
          '[data-module-lifecycle-controls] button',
        ),
      ).find((button) => button.textContent?.trim() === label)!;
    await vi.waitFor(() => expect(action()?.disabled).toBe(false));
    action().click();
    await vi.waitFor(() =>
      expect(
        document.body.querySelector<HTMLButtonElement>(
          '[data-lifecycle-cancel]',
        )?.disabled,
      ).toBe(false),
    );
    const dialog = document.body
      .querySelector('[data-module-lifecycle-review]')!
      .closest('[role="dialog"]')!;
    expect(
      dialog.querySelector('[data-lifecycle-cancel]')?.textContent?.trim(),
    ).toBe('Anuluj');
    expect(
      Array.from(dialog.querySelectorAll('button')).some(
        (button) => button.textContent?.trim() === 'Cancel',
      ),
    ).toBe(false);
    language.locale.value = 'en';
  },
);

it.each(['runa.auth', 'runa.audit', 'runa.permissions', 'app.reference'])(
  'module %s never exposes or reads a generic settings editor, including an old deep link',
  async (module) => {
    window.history.replaceState(
      {},
      '',
      `/admin?module=${module}&section=settings`,
    );
    const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
      String(input).endsWith('/capabilities')
        ? response({ capabilities: ['diagnostics.read', 'settings.read'] })
        : response({
            modules: [
              {
                id: module,
                kind: 'optional',
                registered: true,
                enabled: true,
                health: 'healthy',
                available: true,
              },
            ],
          }),
    );
    mount(AdminOperations, {
      props: { admin: admin() },
      attachTo: document.body,
    });
    await vi.waitFor(() =>
      expect(
        document.body.querySelector('[data-module-content]'),
      ).not.toBeNull(),
    );
    expect(
      Array.from(document.body.querySelectorAll('[role="tab"]')).map((tab) =>
        tab.textContent?.trim(),
      ),
    ).toEqual([]);
    expect(
      fetch.mock.calls.some(([url]) =>
        String(url).includes('/operations/settings'),
      ),
    ).toBe(false);
  },
);

async function brandingFile(color: string): Promise<File> {
  const canvas = document.createElement('canvas');
  canvas.width = 80;
  canvas.height = 40;
  const context = canvas.getContext('2d')!;
  context.fillStyle = color;
  context.fillRect(0, 0, 80, 40);
  const blob = await new Promise<Blob>((resolve) =>
    canvas.toBlob((value) => resolve(value!), 'image/png'),
  );
  return new File([blob], 'brand.png', { type: 'image/png' });
}
const brandingState = (revision = 1): AdminOperationData => ({
  revision,
  values: {
    'presentation.name': null,
    'presentation.logo': null,
    'presentation.favicon': 'automatic',
    'presentation.locale': 'pl',
    'presentation.theme': 'system',
    'presentation.timezone': 'Europe/Warsaw',
  },
  definitions: {
    'presentation.name': {
      title: 'Displayed application name',
      type: 'string',
      max_length: 160,
      choices: [],
      default: null,
      writable: true,
    },
    'presentation.logo': {
      title: 'Application logo',
      type: 'enum',
      choices: ['uploaded'],
      default: null,
      writable: true,
    },
    'presentation.favicon': {
      title: 'Application favicon',
      type: 'enum',
      choices: ['automatic', 'uploaded'],
      default: 'automatic',
      writable: true,
    },
    'presentation.locale': {
      title: 'Default language',
      type: 'enum',
      choices: ['pl', 'en'],
      default: 'pl',
      writable: true,
    },
    'presentation.theme': {
      title: 'Default theme',
      type: 'enum',
      choices: ['system', 'light', 'dark'],
      default: 'system',
      writable: true,
    },
    'presentation.timezone': {
      title: 'Default timezone',
      type: 'timezone',
      choices: ['Europe/Warsaw', 'UTC', 'America/New_York'],
      default: 'Europe/Warsaw',
      writable: true,
    },
  },
  presentation: { name: 'Runa', logo: null, favicon: null },
});

it('application branding converts a logo to PNG and a square favicon, preserving images on conflict', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(response(brandingState()));
  let settings!: ReturnType<typeof useAdminApplicationSettings>;
  const { controller } = harness(admin(), (operations) => {
    settings = useAdminApplicationSettings(operations);
  });
  await settings.refresh();
  await settings.selectImage('logo', [await brandingFile('red')]);
  expect(settings.dirty.value).toBe(true);
  const preview = new Image();
  preview.src = settings.previewImage('favicon')!;
  await preview.decode();
  expect([preview.naturalWidth, preview.naturalHeight]).toEqual([64, 64]);
  fetch.mockResolvedValue(response({}, 409));
  await settings.save();
  const posted = JSON.parse(String(fetch.mock.calls.at(-1)?.[1]?.body));
  expect(posted.patch).toEqual({ 'presentation.logo': 'uploaded' });
  expect(posted.images.logo).toBeTypeOf('string');
  expect(posted.images.logo_icon).toBeTypeOf('string');
  expect(controller.conflict.value).toBe(true);
  expect(settings.imageFiles.value.logo).toHaveLength(1);
  const remote = brandingState(2);
  (remote.values as AdminOperationData)['presentation.logo'] = 'uploaded';
  remote.presentation = {
    name: 'Runa',
    logo: { src: '/remote.png', accessibleName: 'Remote' },
    favicon: '/remote-icon.png',
  };
  fetch.mockResolvedValueOnce(response(remote));
  await settings.refresh();
  expect(settings.conflicts.value.has('presentation.logo')).toBe(true);
  settings.resolve('presentation.logo', true);

  const before = settings.previewImage('favicon');
  await settings.selectImage('favicon', [await brandingFile('blue')]);
  expect(settings.previewImage('favicon')).not.toBe(before);
  settings.edit('presentation.favicon', null);
  expect(settings.previewImage('favicon')).toBe(before);
  const calls = fetch.mock.calls.length;
  await settings.selectImage('logo', [
    new File(['<svg/>'], 'logo.svg', { type: 'image/svg+xml' }),
  ]);
  expect(settings.imageError.value).toContain('PNG, JPEG or WebP');
  await settings.selectImage('favicon', [await brandingFile('blue')]);
  expect(settings.fieldErrors.value['presentation.logo']).toContain(
    'PNG, JPEG or WebP',
  );
  expect(settings.valid.value).toBe(false);
  expect(fetch).toHaveBeenCalledTimes(calls);
});

it('application branding fences pending file preparation on account loss', async () => {
  vi.spyOn(window, 'fetch').mockResolvedValue(response(brandingState()));
  let settings!: ReturnType<typeof useAdminApplicationSettings>;
  const { owner } = harness(admin(), (operations) => {
    settings = useAdminApplicationSettings(operations);
  });
  await settings.refresh();
  const file = await brandingFile('red');
  const pending = settings.selectImage('logo', [file]);
  owner.session.session.value = undefined;
  await pending;
  await flushPromises();
  expect(settings.fields.value).toEqual([]);
  expect(settings.imageFiles.value.logo).toEqual([]);
  expect(settings.previewImage('logo')).toBeUndefined();
});

it('application settings use one main heading, bounded fields and adjacent restore buttons', async () => {
  window.history.replaceState({}, '', '/admin?section=application');
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/capabilities')
      ? response({ capabilities: [] })
      : response(brandingState()),
  );
  const w = mount(AdminOperations, {
    props: { admin: admin() },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.findAll('h1').map((heading) => heading.text())).toEqual([
      'Application settings',
    ]),
  );
  await vi.waitFor(() =>
    expect(w.find('input[type="file"]').exists()).toBe(true),
  );
  expect(
    w
      .findAll('h2,h3')
      .some((heading) => heading.text() === 'Application settings'),
  ).toBe(false);
  const buttons = w
    .findAll('button')
    .filter((button) => button.text() === 'Restore defaults');
  expect(buttons).toHaveLength(6);
  for (const button of buttons) {
    expect(button.attributes('aria-label')).toContain('Restore defaults:');
    expect(
      button.element.parentElement?.querySelector(
        'input,button[aria-haspopup="dialog"]',
      ),
    ).not.toBeNull();
  }
  expect(
    w
      .findAll('input')
      .filter((input) => input.attributes('type') !== 'file')
      .every((input) => input.element.getBoundingClientRect().width < 768),
  ).toBe(true);
});

it('application settings show one dismissible failure notification and reopen it for an identical image attempt', async () => {
  vi.spyOn(window, 'fetch').mockResolvedValue(response(brandingState()));
  const { controller } = harness();
  const w = mount(AdminApplicationSettings, {
    props: { controller },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.find('input[type="file"]').exists()).toBe(true),
  );
  const { FileInput } = await import('@/runa/ui');
  const file = new File(['bad'], 'brand.png', { type: 'image/png' });
  const picker = w.findAllComponents(FileInput)[0]!;
  picker.vm.$emit('update:modelValue', [file]);
  await vi.waitFor(() =>
    expect(
      document.querySelectorAll('.toast-viewport .app-toast'),
    ).toHaveLength(1),
  );
  expect(document.querySelector('.toast-viewport')?.textContent).toContain(
    'The image could not be read.',
  );
  expect(w.text()).not.toContain('Correct the setting values before saving.');
  expect(picker.find('[aria-invalid="true"]').exists()).toBe(true);
  (
    document.querySelector(
      '.toast-viewport button[aria-label="Close"]',
    ) as HTMLButtonElement
  ).click();
  await vi.waitFor(() =>
    expect(
      document.querySelectorAll('.toast-viewport .app-toast'),
    ).toHaveLength(0),
  );
  picker.vm.$emit('update:modelValue', [file]);
  await vi.waitFor(() =>
    expect(
      document.querySelectorAll('.toast-viewport .app-toast'),
    ).toHaveLength(1),
  );
});

it('application settings focus invalid fields without writing and clear feedback after correction', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(response(brandingState()));
  const { controller } = harness();
  const w = mount(AdminApplicationSettings, {
    props: { controller },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(w.find('input[type="text"]').exists()).toBe(true),
  );
  const input = w.find('input[type="text"]');
  await input.setValue('   ');
  const before = fetch.mock.calls.length;
  await w.get('form').trigger('submit');
  await vi.waitFor(() => expect(document.activeElement).toBe(input.element));
  expect(fetch).toHaveBeenCalledTimes(before);
  expect(input.attributes('aria-invalid')).toBe('true');
  expect(document.querySelectorAll('.toast-viewport .app-toast')).toHaveLength(
    1,
  );
  await input.setValue('Corrected');
  await vi.waitFor(() =>
    expect(
      document.querySelectorAll('.toast-viewport .app-toast'),
    ).toHaveLength(0),
  );
  expect(input.attributes('aria-invalid')).toBeUndefined();
});

it('application settings retry failed reads without duplicate parent notifications', async () => {
  window.history.replaceState({}, '', '/admin?section=application');
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(response({}, 500));
  const w = mount(AdminOperations, {
    props: { admin: admin() },
    attachTo: document.body,
  });
  await vi.waitFor(() =>
    expect(document.querySelectorAll('.app-toast')).toHaveLength(1),
  );
  expect(w.find('input[type=file]').exists()).toBe(false);
  fetch.mockImplementation(async (input) =>
    String(input).endsWith('/capabilities')
      ? response({ capabilities: ['application.read', 'application.manage'] })
      : response(brandingState()),
  );
  const retry = Array.from(
    document.querySelectorAll<HTMLButtonElement>('.app-toast button'),
  ).find((button) => button.textContent?.trim() === 'Retry')!;
  retry.click();
  await vi.waitFor(() =>
    expect(w.find('input[type=file]').exists()).toBe(true),
  );
  expect(document.querySelectorAll('.app-toast')).toHaveLength(0);
});
