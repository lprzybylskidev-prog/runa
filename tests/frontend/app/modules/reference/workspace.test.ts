/* eslint-disable vue/one-component-per-file -- Nested controller and confirmation-provider harnesses exercise the public composition boundary. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { defineComponent, h, nextTick, ref } from 'vue';
import { providePasswordConfirmation } from '@/runa/auth';
import { TextInput } from '@/runa/ui';
import Workspace from '../../../../../src/app/modules/reference/frontend/components/workspace.vue';
import { useReferenceWorkspace } from '../../../../../src/app/modules/reference/frontend/useReferenceWorkspace';
import type { ReferenceWorkspaceController } from '../../../../../src/app/modules/reference/frontend/useReferenceWorkspace';
import type {
  ReferenceAccess,
  ReferenceView,
} from '../../../../../src/app/modules/reference/frontend/contracts';

enableAutoUnmount(afterEach);
afterEach(() => vi.restoreAllMocks());
const id = '019a22c7-0000-7000-8000-000000000001';
const response = (data: unknown, status = 200) =>
  new Response(JSON.stringify(status === 200 ? { data } : data), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
const access = (): ReferenceAccess => ({
  identity: 'account:session',
  read: true,
  write: true,
  codeRead: true,
  codeWrite: true,
  credentialRead: true,
  credentialWrite: true,
});
const settings = (label = 'Committed', revision = 1) => ({
  revision,
  review_id: String(revision).repeat(64),
  settings: {
    label: {
      value: label,
      names: { pl: 'Etykieta', en: 'Label' },
      descriptions: { pl: 'Opublikowana etykieta.', en: 'Committed label.' },
    },
    credential: { set: true, names: { pl: 'Sekret', en: 'Secret' } },
  },
});
const snapshots = (values: object = {}) => ({
  snapshots: [
    {
      public_id: id,
      name: 'Saved label',
      source_revision: 1,
      created_at: '2026-11-07T12:00:00Z',
    },
  ],
  values: values,
});
function harness(view: ReferenceView) {
  const authority = ref(access()),
    language = ref<'pl' | 'en'>('pl');
  let controller!: ReferenceWorkspaceController;
  let host!: ReturnType<typeof providePasswordConfirmation>;
  const Child = defineComponent({
    setup() {
      controller = useReferenceWorkspace(
        () => authority.value,
        () => view,
        () => language.value,
      );
      return () => h('output', controller.error.value);
    },
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        host = providePasswordConfirmation();
        return () => h(Child);
      },
    }),
  );
  return {
    wrapper,
    authority,
    language,
    get controller() {
      return controller;
    },
    get host() {
      return host;
    },
  };
}

it('retains a conflicting sample draft, fetches current state once, and sends no replay', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValueOnce(
      response({ items: [{ public_id: id, revision: '1', code: 'old' }] }),
    )
    .mockResolvedValueOnce(
      response({ error: { code: 'reference_sample_stale' } }, 409),
    )
    .mockResolvedValueOnce(
      response({ items: [{ public_id: id, revision: '2', code: 'remote' }] }),
    )
    .mockResolvedValueOnce(
      response({ public_id: id, revision: '3', code: 'draft' }),
    )
    .mockResolvedValueOnce(
      response({ items: [{ public_id: id, revision: '3', code: 'draft' }] }),
    );
  const test = harness('samples'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  await c.selectSample(c.rows.value[0]!);
  c.code.value = 'draft';
  await c.saveSample();
  expect(c.code.value).toBe('draft');
  expect(c.selected.value?.revision).toBe('1');
  expect(c.sampleConflict.value?.code).toBe('remote');
  expect(fetch).toHaveBeenCalledTimes(3);
  await c.saveSample();
  expect(fetch).toHaveBeenCalledTimes(3);
  c.resolveSample(true);
  expect(fetch).toHaveBeenCalledTimes(3);
  expect(c.selected.value?.revision).toBe('2');
  await c.saveSample();
  expect(JSON.parse(String(fetch.mock.calls[3]?.[1]?.body))).toEqual({
    public_id: id,
    revision: '2',
    code: 'draft',
  });
  expect(c.sampleDirty.value).toBe(false);
});

it('fences late reads and clears protected values immediately after access withdrawal', async () => {
  let finish!: (value: Response) => void;
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValueOnce(response(settings()))
    .mockResolvedValueOnce(response(snapshots()))
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = resolve;
        }),
    );
  const test = harness('settings'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  c.label.value = 'Private draft';
  c.secretMode.value = 'replace';
  c.credential.value = 'Private secret';
  const pending = c.refresh();
  test.authority.value = { ...access(), identity: undefined, read: false };
  expect(c.state.value).toBeUndefined();
  expect(c.credential.value).toBe('');
  expect(c.label.value).toBe('');
  finish(response(settings('Late private state')));
  await pending;
  expect(c.state.value).toBeUndefined();
  expect(c.error.value).toBe('');
  expect(fetch).toHaveBeenCalledTimes(3);
});

it('publishes the exact reviewed settings only once after its own native password proof', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const path = String(input);
    if (path.endsWith('/settings/review'))
      return response({
        review: 'opaque-review',
        changes: { label: { old: 'Committed', new: 'Draft' } },
      });
    if (path.endsWith('/auth/step-up')) return response({ confirmed: true });
    if (path.endsWith('/settings/publish'))
      return response({ status: 'succeeded', operation: id });
    if (path.endsWith('/settings')) return response(settings());
    if (path.endsWith('/snapshots')) return response(snapshots());
    throw new Error('Unexpected request');
  });
  const test = harness('settings'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  c.label.value = 'Draft';
  await nextTick();
  await c.reviewSettings();
  const pending = c.publish();
  await vi.waitFor(() => expect(test.host.active.value).toBeDefined());
  expect(
    fetch.mock.calls.filter(([path]) =>
      String(path).endsWith('/settings/publish'),
    ),
  ).toHaveLength(0);
  await c.publish();
  const proof = test.host.active.value!;
  proof.password.value = 'Local password';
  await proof.confirm();
  test.host.complete(true);
  await pending;
  const proofCall = fetch.mock.calls.find(([path]) =>
    String(path).endsWith('/auth/step-up'),
  )!;
  expect(JSON.parse(String(proofCall[1]?.body))).toEqual({
    action: 'app.reference.settings.publish',
    current_password: 'Local password',
  });
  const writes = fetch.mock.calls.filter(([path]) =>
    String(path).endsWith('/settings/publish'),
  );
  expect(writes).toHaveLength(1);
  expect(JSON.parse(String(writes[0]?.[1]?.body))).toEqual({
    values: { label: 'Draft' },
    revision: 1,
    review_id: '1'.repeat(64),
    review: 'opaque-review',
  });
  expect(proof.password.value).toBe('');
  expect(c.review.value).toBeUndefined();
});

it('cancels protected work without dispatch and reports admission separately from completion', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(async (input, init) => {
      if (String(input).endsWith('/auth/step-up'))
        return response({ confirmed: true });
      if (init?.method === 'POST')
        return response({ work_id: id, status: 'admitted' });
      return response({
        items: [
          {
            work_id: id,
            operation: 'reference.work',
            status: 'pending',
            progress: 'queued',
            attempts: 0,
            cancellation_requested: false,
          },
        ],
      });
    });
  const test = harness('work'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  const cancelled = c.dispatch();
  await vi.waitFor(() => expect(test.host.active.value).toBeDefined());
  test.host.cancel();
  await cancelled;
  expect(
    fetch.mock.calls.filter(([, init]) => init?.method === 'POST'),
  ).toHaveLength(0);
  const dispatched = c.dispatch();
  await vi.waitFor(() => expect(test.host.active.value).toBeDefined());
  const proof = test.host.active.value!;
  proof.password.value = 'Local password';
  await proof.confirm();
  test.host.complete(true);
  await dispatched;
  expect(c.success.value).toContain('przyjęte');
  expect(c.workRows.value[0]?.status).toBe('pending');
});

it('loads a snapshot only into the draft and invalidates a review when the draft changes', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const path = String(input);
    if (path.includes('/snapshots?'))
      return response(snapshots({ label: 'From snapshot' }));
    if (path.endsWith('/snapshots')) return response(snapshots());
    if (path.endsWith('/settings/review'))
      return response({ review: 'review', changes: {} });
    return response(settings());
  });
  const test = harness('settings'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  await c.loadSnapshot(c.snapshotRows.value[0]!);
  expect(c.label.value).toBe('From snapshot');
  expect(c.state.value?.label).toBe('Committed');
  expect(fetch.mock.calls.some(([, init]) => init?.method === 'POST')).toBe(
    false,
  );
  await nextTick();
  await c.reviewSettings();
  expect(c.review.value).toBeDefined();
  c.label.value = 'Different';
  await nextTick();
  expect(c.review.value).toBeUndefined();
  await c.publish();
  expect(
    fetch.mock.calls.some(([path]) =>
      String(path).endsWith('/settings/publish'),
    ),
  ).toBe(false);
});

it('does not reconcile or replay a snapshot business conflict and preserves drafts', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValueOnce(response(settings()))
    .mockResolvedValueOnce(response(snapshots()))
    .mockResolvedValueOnce(
      response({ error: { code: 'settings_snapshot_name_or_limit' } }, 409),
    );
  const test = harness('settings'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  c.label.value = 'Unsaved label';
  c.snapshotName.value = 'Existing name';
  await c.capture();
  expect(fetch).toHaveBeenCalledTimes(3);
  expect(c.label.value).toBe('Unsaved label');
  expect(c.snapshotName.value).toBe('Existing name');
  expect(c.error.value).toContain('nazwa');
  test.language.value = 'en';
  expect(c.error.value).toContain('name exists');
});

it('renders readonly settings, omits unavailable secret controls and keeps replacement composition typed', async () => {
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/settings')
      ? response({
          ...settings(),
          settings: { label: settings().settings.label },
        })
      : response(snapshots()),
  );
  const wrapper = mount(Workspace, {
    props: {
      view: 'settings',
      access: {
        ...access(),
        write: false,
        credentialRead: false,
        credentialWrite: false,
      },
    },
  });
  await vi.waitFor(() =>
    expect(wrapper.findComponent(TextInput).exists()).toBe(true),
  );
  expect(wrapper.getComponent(TextInput).props('readonly')).toBe(true);
  expect(wrapper.find('input[type=password]').exists()).toBe(false);
  expect(wrapper.text()).not.toContain('Sekret');
  expect(wrapper.text()).not.toContain('Opublikuj');
  await wrapper.setProps({
    access: { ...access(), identity: undefined, read: false },
  });
  expect(wrapper.findComponent(TextInput).exists()).toBe(false);
});

it('keeps drafts after a cancelled password page and never sends a changed reviewed input', async () => {
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    if (String(input).endsWith('/settings/review'))
      return response({ review: 'opaque', changes: {} });
    return String(input).endsWith('/settings')
      ? response(settings())
      : response(snapshots());
  });
  const test = harness('settings'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  c.label.value = 'Retained draft';
  await nextTick();
  await c.reviewSettings();
  const pending = c.publish();
  await vi.waitFor(() => expect(test.host.active.value).toBeDefined());
  test.host.cancel();
  await pending;
  expect(c.label.value).toBe('Retained draft');
  expect(c.review.value).toBeDefined();
  expect(
    fetch.mock.calls.some(([path]) =>
      String(path).endsWith('/settings/publish'),
    ),
  ).toBe(false);
});

it('clears a removed sample editor after an authoritative missing-record refusal', async () => {
  vi.spyOn(window, 'fetch')
    .mockResolvedValueOnce(
      response({ items: [{ public_id: id, revision: '1', code: 'old' }] }),
    )
    .mockResolvedValueOnce(
      response({ error: { code: 'reference_sample_missing' } }, 404),
    )
    .mockResolvedValueOnce(response({ items: [] }));
  const test = harness('samples'),
    c = test.controller;
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  await c.selectSample(c.rows.value[0]!);
  c.code.value = 'draft';
  await c.saveSample();
  expect(c.selected.value).toBeUndefined();
  expect(c.code.value).toBe('');
  expect(c.rows.value).toEqual([]);
});

it('renders custom workspace markup with the same typed controller and cancels its lifetime', async () => {
  let signal: AbortSignal | undefined;
  vi.spyOn(window, 'fetch').mockImplementation((_input, init) => {
    signal = init?.signal ?? undefined;
    return new Promise(() => {});
  });
  const wrapper = mount(Workspace, {
    props: { access: access(), view: 'work' },
    slots: {
      default: ({ controller }: { controller: ReferenceWorkspaceController }) =>
        h(
          'output',
          controller.busy.value ? 'Pending custom work view' : 'Ready',
        ),
    },
  });
  expect(wrapper.get('output').text()).toBe('Pending custom work view');
  expect(signal?.aborted).toBe(false);
  wrapper.unmount();
  expect(signal?.aborted).toBe(true);
});

it('allows navigation during a clean pending read while a pending protected mutation blocks it', async () => {
  let finish!: (value: Response) => void;
  vi.spyOn(window, 'fetch').mockImplementationOnce(
    () =>
      new Promise((resolve) => {
        finish = resolve;
      }),
  );
  const test = harness('work'),
    c = test.controller;
  expect(c.busy.value).toBe(true);
  expect(await c.beforeLeave()).toBe(true);
  finish(response({ items: [] }));
  await vi.waitFor(() => expect(c.ready.value).toBe(true));
  const pending = c.dispatch();
  await vi.waitFor(() => expect(test.host.active.value).toBeDefined());
  expect(await c.beforeLeave()).toBe(false);
  test.host.cancel();
  await pending;
  expect(await c.beforeLeave()).toBe(true);
});

it('two independent viewers keep old snapshots until a write conflicts or a target disappears', async () => {
  let current:
    { public_id: string; revision: string; code: string } | undefined = {
    public_id: id,
    revision: '1',
    code: 'original',
  };
  const writes: Array<{ revision: string; code: string }> = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (_path, init) => {
    if (init?.method === 'PATCH') {
      const input = JSON.parse(String(init.body)) as {
        revision: string;
        code: string;
      };
      writes.push(input);
      if (!current)
        return response({ error: { code: 'reference_sample_missing' } }, 404);
      if (input.revision !== current.revision)
        return response({ error: { code: 'reference_sample_stale' } }, 409);
      current = {
        ...current,
        code: input.code,
        revision: String(Number(current.revision) + 1),
      };
      return response(current);
    }
    return response({ items: current ? [current] : [] });
  });
  const x = harness('samples');
  const y = harness('samples');
  y.authority.value = { ...access(), identity: 'other-account:other-session' };
  await vi.waitFor(() =>
    expect(x.controller.ready.value && y.controller.ready.value).toBe(true),
  );
  await x.controller.selectSample(x.controller.rows.value[0]!);
  await y.controller.selectSample(y.controller.rows.value[0]!);
  y.controller.code.value = 'y_draft';
  x.controller.code.value = 'x_committed';
  await x.controller.saveSample();
  window.dispatchEvent(new Event('focus'));
  document.dispatchEvent(new Event('visibilitychange'));
  expect(y.controller.selected.value?.revision).toBe('1');
  await y.controller.saveSample();
  expect(writes.map((input) => input.revision)).toEqual(['1', '1']);
  expect(y.controller.code.value).toBe('y_draft');
  expect(y.controller.sampleConflict.value?.code).toBe('x_committed');
  expect(y.controller.error.value).not.toBe('');
  y.controller.resolveSample(true);
  current = undefined;
  await y.controller.saveSample();
  expect(writes.map((input) => input.revision)).toEqual(['1', '1', '2']);
  expect(y.controller.selected.value).toBeUndefined();
  await y.controller.saveSample();
  expect(writes).toHaveLength(3);
});
