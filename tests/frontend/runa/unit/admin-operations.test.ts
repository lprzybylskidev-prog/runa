import { afterEach, expect, it, vi } from '@runa/testing/vitest';
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
  await vi.waitFor(() => expect(wrapper.text()).toContain('health_expired'));
  expect(wrapper.text()).toContain('Source 1.0.0');
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
  expect(settings.draft.value).toEqual({});
  expect(settings.loaded.value).toBe(false);
  expect(controller.dirty.value).toBe(false);
});
