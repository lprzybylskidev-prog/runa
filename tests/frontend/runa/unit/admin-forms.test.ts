/* eslint-disable vue/one-component-per-file -- Independent controller and replacement fixtures. */
import { afterEach, beforeEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  AdminForm,
  AdminSection,
  useAdminForm,
  useAdminTable,
} from '@/runa/admin';
import CustomerAdminEditor from './fixtures/customization/CustomerAdminEditor.vue';
import type {
  AdminCommandData,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminRecord,
} from '@/runa/admin';
import type {
  ExportProvider,
  BulkProvider,
  TableQuery,
  ExportResult,
  OperationResult,
} from '@/runa/ui';
enableAutoUnmount(afterEach);
const id = '01900000-0000-7000-8000-000000000001';
const otherId = '01900000-0000-7000-8000-000000000002';
const command = (change: Partial<AdminCommandData> = {}): AdminCommandData => ({
  key: 'save',
  title: 'Save profile',
  mode: 'independent',
  destructive: false,
  requirement: 'none',
  preview_required: false,
  inputs: ['name', 'enabled'],
  bulk: false,
  action: null,
  available: true,
  intent: 'save',
  ...change,
});
function descriptor(): AdminPageData {
  return {
    schema_version: 1,
    owner: 'app',
    key: 'users',
    title: 'Users',
    generation: 1,
    revision: 'page-1',
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
        commands: [command()],
        fields: ['name', 'enabled', 'token'].map((key) => ({
          key,
          type: key === 'enabled' ? 'boolean' : 'text',
          label: key,
          readonly: key === 'token',
          sensitive: key === 'token',
          filterable: key === 'name',
          sortable: key === 'name',
          lookup: false,
          required: key === 'name',
          max_length: key === 'name' ? 80 : null,
          choices: [],
          multiple: false,
          component: null,
        })),
      },
    ],
  };
}
const record = (change: Partial<AdminRecord> = {}): AdminRecord => ({
  public_id: id,
  revision: 'r1',
  values: { name: 'Before', enabled: true, token: { set: true } },
  ...change,
});
const respond = (data: unknown, status = 200) =>
  new Response(JSON.stringify(data), {
    status,
    headers: { 'Content-Type': 'application/json' },
  });
let server: AdminRecord;
let writes: Record<string, unknown>[];
beforeEach(() => {
  localStorage.setItem('runa.locale', 'en');
  server = record();
  writes = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (_input, init) => {
    if (init?.method === 'POST') {
      const input = JSON.parse(String(init.body)) as Record<string, unknown>;
      writes.push(input);
      server = record({
        revision: 'r2',
        values: {
          ...server.values,
          ...(input.values as Record<string, string>),
        },
      });
      return respond({
        data: { status: 'committed', record: server, operation_id: null },
      });
    }
    return respond({ data: server });
  });
});
function harness(
  data = descriptor(),
  options: Parameters<typeof useAdminForm>[1] = {},
) {
  const value = shallowRef(data),
    state = ref<'ready' | 'forbidden'>('ready');
  const page: AdminPageController = {
    page: value,
    state: computed(() => state.value),
    error: ref(),
    refresh: vi.fn(async () => {}),
  };
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(page, options);
        return () =>
          h(
            AdminForm,
            { controller: form },
            {
              default: () =>
                form.sections.value.map((section) =>
                  h(AdminSection, { section, controller: form }),
                ),
            },
          );
      },
    }),
  );
  return { form, page, value, state, wrapper };
}

it('Admin forms support a public customer page with shared form ownership and no second read lane', async () => {
  const data = shallowRef(descriptor());
  const page: AdminPageController = {
    page: data,
    state: computed(() => 'ready'),
    error: ref(),
    refresh: async () => {},
  };
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(page, { autoRead: false });
        expect(useAdminForm(page)).toBe(form);
        return () => h(CustomerAdminEditor, { controller: page, form });
      },
    }),
  );
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  expect(wrapper.text()).toContain('Customer editor');
  expect(vi.mocked(window.fetch)).toHaveBeenCalledTimes(1);
  expect(wrapper.find('input').exists()).toBe(true);
});

it('Admin forms generate controlled fields and dispatch only explicit authorized command inputs', async () => {
  const { form, wrapper } = harness();
  await vi.waitFor(() =>
    expect(form.records.value.profile?.revision).toBe('r1'),
  );
  form.set('profile.name', 'After');
  expect(form.dirty.value).toBe(true);
  expect(wrapper.get('form').attributes('novalidate')).toBeDefined();
  const result = await form.execute('profile', 'save');
  expect(result?.status).toBe('committed');
  expect(writes).toHaveLength(1);
  expect(writes[0]).toMatchObject({
    command: 'save',
    public_id: id,
    revision: 'r1',
    values: { name: 'After', enabled: true },
  });
  expect(JSON.stringify(writes[0])).not.toContain('token');
  await vi.waitFor(() => expect(form.dirty.value).toBe(false));
  expect(form.notice.value).toBe('Changes saved.');
});

it('Admin forms merge disjoint reads without reinitializing edited fields or advancing conflicting tokens', async () => {
  const { form } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  server = record({
    revision: 'r2',
    values: { ...server.values, enabled: false },
  });
  await form.refresh();
  expect(form.values.value['profile.name']).toBe('Draft');
  expect(form.values.value['profile.enabled']).toBe(false);
  expect(form.records.value.profile?.revision).toBe('r2');
  expect(form.conflicts.value).toEqual([]);
  server = record({
    revision: 'r3',
    values: { ...server.values, name: 'Remote' },
  });
  await form.refresh();
  expect(form.values.value['profile.name']).toBe('Draft');
  expect(form.records.value.profile?.revision).toBe('r2');
  expect(form.conflicts.value[0]).toMatchObject({
    path: 'profile.name',
    base: 'Before',
    local: 'Draft',
    remote: 'Remote',
  });
  expect(await form.execute('profile', 'save')).toBeUndefined();
  expect(writes).toHaveLength(0);
  form.resolve('profile.name', 'local');
  expect(form.records.value.profile?.revision).toBe('r3');
  expect(form.values.value['profile.name']).toBe('Draft');
  expect(writes).toHaveLength(0);
  form.reset();
  expect(form.values.value['profile.name']).toBe('Remote');
  expect(form.dirty.value).toBe(false);
});

it('Admin forms refresh a 409 automatically without replay and retain a locally resolvable draft', async () => {
  const { form } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  let posts = 0;
  vi.mocked(window.fetch).mockImplementation(async (_input, init) => {
    if (init?.method === 'POST') {
      posts++;
      server = record({
        revision: 'r2',
        values: { ...server.values, name: 'Remote' },
      });
      return respond({ reason: 'stale_revision' }, 409);
    }
    return respond({ data: server });
  });
  expect(await form.execute('profile', 'save')).toBeUndefined();
  await vi.waitFor(() => expect(form.conflicts.value).toHaveLength(1));
  expect(posts).toBe(1);
  expect(form.values.value['profile.name']).toBe('Draft');
  expect(form.error.value?.message).toContain('not saved');
  form.resolve('profile.name', 'remote');
  expect(form.values.value['profile.name']).toBe('Remote');
  expect(posts).toBe(1);
});

it('Admin forms map 422 errors without owner payload echoes or forbidden-field disclosure', async () => {
  const { form } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  vi.mocked(window.fetch).mockResolvedValue(
    respond(
      {
        message: 'unsafe-payload',
        errors: { name: ['unsafe-echo'], forbidden: ['hidden-value'] },
      },
      422,
    ),
  );
  await form.execute('profile', 'save');
  expect(form.errors.value).toEqual({ 'profile.name': ['Invalid input.'] });
  expect(JSON.stringify(form.error.value)).not.toContain('unsafe');
  expect(form.values.value['profile.name']).toBe('Draft');
  expect(form.dirty.value).toBe(true);
});

it('Admin forms refuse known validation errors locally and require an exact current preview', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [command({ preview_required: true })];
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', '');
  expect(await form.execute('profile', 'save')).toBeUndefined();
  expect(writes).toHaveLength(0);
  form.set('profile.name', 'Reviewed');
  expect(await form.execute('profile', 'save')).toBeUndefined();
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (String(input).endsWith('/preview'))
      return respond({
        data: {
          fingerprint: 'proof-1',
          changes: { name: 'Reviewed' },
          requirement: 'none',
          every_action: false,
        },
      });
    if (init?.method === 'POST') {
      writes.push(JSON.parse(String(init.body)));
      return respond({
        data: {
          status: 'committed',
          record: record({
            revision: 'r2',
            values: { ...server.values, name: 'Reviewed' },
          }),
          operation_id: null,
        },
      });
    }
    return respond({ data: server });
  });
  await form.reviewCommand('profile', 'save');
  expect(form.commandReviews.value['profile:save']?.fingerprint).toBe(
    'proof-1',
  );
  form.set('profile.name', 'Changed after preview');
  expect(form.commandReviews.value).toEqual({});
  expect(await form.execute('profile', 'save')).toBeUndefined();
  expect(writes).toHaveLength(0);
});

it('Admin forms confirm destructive commands and preserve the draft on cancel', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [
    command({
      key: 'remove',
      destructive: true,
      title: 'Delete profile',
      intent: 'delete',
    }),
  ];
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  const pending = form.execute('profile', 'remove');
  await vi.waitFor(() =>
    expect(form.confirmation.value?.title).toBe('Delete profile'),
  );
  form.confirm(false);
  expect(await pending).toBeUndefined();
  expect(writes).toHaveLength(0);
  expect(form.values.value['profile.name']).toBe('Draft');
  const accepted = form.execute('profile', 'remove');
  await vi.waitFor(() => expect(form.confirmation.value).toBeDefined());
  form.confirm(true);
  await accepted;
  expect(writes[0]?.confirmed).toBe(true);
});

it('Admin forms protect subject navigation and erase all protected state on authority loss', async () => {
  const subject = ref<string | null>(id);
  const { form, state } = harness(descriptor(), { publicId: subject });
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  const pending = form.selectSubject(otherId);
  await vi.waitFor(() => expect(form.confirmation.value).toBeDefined());
  form.confirm(false);
  expect(await pending).toBe(false);
  expect(subject.value).toBe(id);
  expect(form.values.value['profile.name']).toBe('Draft');
  state.value = 'forbidden';
  await flushPromises();
  expect(form.values.value).toEqual({});
  expect(form.records.value).toEqual({});
  expect(form.conflicts.value).toEqual([]);
});

it('Admin forms fence late responses and erase newly forbidden values without discarding readable drafts', async () => {
  const { form, value } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  const next = descriptor();
  next.revision = 'page-2';
  next.sections[0]!.fields = next.sections[0]!.fields.filter(
    (field) => field.key !== 'enabled',
  );
  server = record({ values: { name: 'Before', token: { set: true } } });
  value.value = next;
  await vi.waitFor(() =>
    expect(form.records.value.profile?.values.enabled).toBeUndefined(),
  );
  expect(form.values.value['profile.enabled']).toBeUndefined();
  expect(form.values.value['profile.name']).toBe('Draft');
});

it('Admin forms share one mutation lane and never replay a rejected concurrent intent', async () => {
  const data = descriptor();
  data.sections.push({
    ...data.sections[0]!,
    key: 'secondary',
    title: 'Secondary',
  });
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.secondary).toBeDefined());
  let finish!: (value: Response) => void;
  let posts = 0;
  vi.mocked(window.fetch).mockImplementation(async (_input, init) => {
    if (init?.method === 'POST') {
      posts++;
      return new Promise((resolve) => {
        finish = resolve;
      });
    }
    return respond({ data: server });
  });
  const first = form.execute('profile', 'save');
  await vi.waitFor(() => expect(posts).toBe(1));
  expect(await form.execute('secondary', 'save')).toBeUndefined();
  finish(respond({ reason: 'validation_failed' }, 422));
  await first;
  expect(posts).toBe(1);
});

it('Admin forms keep pending workflows distinct from committed saves and prevent blind resubmission', async () => {
  const { form } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Draft');
  let posts = 0;
  vi.mocked(window.fetch).mockImplementation(async (_input, init) => {
    if (init?.method === 'POST') {
      posts++;
      return respond({
        data: { status: 'pending', record: null, operation_id: 'native-work' },
      });
    }
    return respond({ data: server });
  });
  expect((await form.execute('profile', 'save'))?.status).toBe('pending');
  expect(form.notice.value).toBe('');
  expect(form.dirty.value).toBe(true);
  expect(form.operations.value.profile).toEqual({
    status: 'pending',
    operationId: 'native-work',
  });
  expect(await form.execute('profile', 'save')).toBeUndefined();
  expect(posts).toBe(1);
});

it('Admin forms support explicit new-record bindings and block required unavailable field presentations', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [command({ key: 'create', intent: 'create' })];
  server = record({
    public_id: null,
    revision: 'new-record',
    values: { name: '', enabled: false, token: { set: false } },
  });
  const { form, value } = harness(data);
  await vi.waitFor(() =>
    expect(form.records.value.profile?.revision).toBe('new-record'),
  );
  form.set('profile.name', 'New');
  expect((await form.execute('profile', 'create'))?.status).toBe('committed');
  expect(writes[0]?.public_id).toBeNull();
  const next = descriptor();
  next.sections[0]!.fields[0]!.component = {
    owner: 'app',
    key: 'app.missing',
    manifest_hash: null,
    build_id: null,
  };
  value.value = next;
  await vi.waitFor(() => expect(form.blocked.value).toBe(true));
});

it('Admin collection adapters retain native export limits and deny secret columns or stale bulk selections', async () => {
  const data = descriptor();
  data.sections[0]!.view = 'table';
  data.sections[0]!.export_allowed = true;
  data.sections[0]!.commands = [
    command({ key: 'revoke', bulk: true, intent: 'action', inputs: [] }),
  ];
  const page: AdminPageController = {
    page: shallowRef(data),
    state: computed(() => 'ready'),
    error: ref(),
    refresh: async () => {},
  };
  const exports: ExportProvider<TableQuery> = {
    capabilities: {
      execution: 'server',
      formats: ['csv'],
      scopes: ['filtered', 'selected'],
      cancellation: false,
    },
    start: vi.fn(async (): Promise<ExportResult> => ({
      kind: 'download',
      download: {
        filename: 'safe.csv',
        mimeType: 'text/csv',
        source: new Blob(['safe']),
      },
    })),
  };
  const bulk: BulkProvider<TableQuery> = {
    actions: [
      { key: 'revoke', label: 'Revoke' },
      { key: 'foreign', label: 'Foreign' },
    ],
    cancellation: false,
    start: vi.fn(async (): Promise<OperationResult> => ({
      kind: 'status',
      status: { state: 'completed', succeeded: 1 },
    })),
  };
  vi.mocked(window.fetch).mockResolvedValue(
    respond({ data: { items: [server], next: null, total: null } }),
  );
  let table!: ReturnType<typeof useAdminTable>;
  mount(
    defineComponent({
      setup() {
        table = useAdminTable(page, 'profile', { adapters: { exports, bulk } });
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(table.resource.result.value).toBeDefined());
  expect(table.adapters.value.exports?.capabilities).toEqual(
    exports.capabilities,
  );
  expect(table.adapters.value.bulk?.actions).toEqual([
    { key: 'revoke', label: 'Revoke' },
  ]);
  await expect(
    table.adapters.value.exports!.start(
      {
        format: 'csv',
        scope: { kind: 'filtered', query: table.query.value },
        columnKeys: ['token'],
        filename: 'leak',
      },
      new AbortController().signal,
    ),
  ).rejects.toThrow();
  expect(exports.start).not.toHaveBeenCalled();
  table.selection.value = { mode: 'explicit', publicIds: [id] };
  const pending = table.adapters.value.bulk!.start(
    { action: 'revoke', selection: table.selection.value, operationKey: 'key' },
    new AbortController().signal,
  );
  await vi.waitFor(() => expect(table.confirmation.value).toBe('Save profile'));
  table.selection.value = { mode: 'explicit', publicIds: [otherId] };
  table.confirm(true);
  await expect(pending).rejects.toThrow();
  expect(bulk.start).not.toHaveBeenCalled();
});

it('Admin forms reconcile one aggregate revision only after every participant response is decoded', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [
    command({ mode: 'atomic', preview_required: true }),
  ];
  data.sections.push({ ...data.sections[0]!, key: 'access', title: 'Access' });
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.access).toBeDefined());
  form.set('profile.name', 'Profile draft');
  form.set('access.name', 'Access draft');
  const posts: Record<string, unknown>[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (init?.method !== 'POST') return respond({ data: server });
    const body = JSON.parse(String(init.body)) as {
      participants: { id: string; input: { values: Record<string, string> } }[];
    };
    posts.push(body);
    if (String(input).endsWith('/preview'))
      return respond({
        data: {
          revision: 'aggregate-1',
          participants: body.participants.map((participant) => ({
            id: participant.id,
            review: {
              fingerprint: `review-${participant.id}`,
              changes: participant.input.values,
              requirement: 'none',
              every_action: false,
            },
          })),
        },
      });
    return respond({
      data: {
        status: 'committed',
        revision: 'aggregate-2',
        participants: [
          {
            id: 'profile',
            record: record({
              revision: 'r2',
              values: { ...server.values, name: 'Profile draft' },
            }),
          },
          {
            id: 'access',
            record: record({
              revision: 'r2',
              values: {
                ...server.values,
                name: 'Access draft',
                token: 'forbidden-secret',
              },
            }),
          },
        ],
      },
    });
  });
  expect((await form.preview())?.revision).toBe('aggregate-1');
  expect(await form.save()).toBeUndefined();
  expect(form.records.value.profile?.revision).toBe('r1');
  expect(form.records.value.access?.revision).toBe('r1');
  expect(form.values.value['profile.name']).toBe('Profile draft');
  expect(posts).toHaveLength(2);
  expect(JSON.stringify(posts[1])).toContain('aggregate-1');
});

it('Admin forms send reviewed aggregate inputs once and retain sibling drafts after independent success', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [
    command({ mode: 'atomic', preview_required: true }),
  ];
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Saved aggregate');
  let posts = 0;
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (init?.method === 'POST') {
      posts++;
      if (String(input).endsWith('/preview'))
        return respond({
          data: {
            revision: 'aggregate-1',
            participants: [
              {
                id: 'profile',
                review: {
                  fingerprint: 'review-1',
                  changes: { name: 'Saved aggregate' },
                  requirement: 'none',
                  every_action: false,
                },
              },
            ],
          },
        });
      expect(JSON.parse(String(init.body))).toMatchObject({
        revision: 'aggregate-1',
        participants: [
          { id: 'profile', input: { review: { fingerprint: 'review-1' } } },
        ],
      });
      server = record({
        revision: 'r2',
        values: { ...server.values, name: 'Saved aggregate' },
      });
      return respond({
        data: {
          status: 'committed',
          revision: 'aggregate-2',
          participants: [{ id: 'profile', record: server }],
        },
      });
    }
    return respond({ data: server });
  });
  await form.preview();
  expect((await form.save())?.revision).toBe('aggregate-2');
  await vi.waitFor(() => expect(form.dirty.value).toBe(false));
  expect(posts).toBe(2);
  expect(form.notice.value).toBe('Changes saved.');
});

it('Admin forms keep field-bound cursor lookups separate from the record and respect cancellation', async () => {
  const data = descriptor();
  const reference = {
    ...data.sections[0]!.fields[0]!,
    key: 'manager',
    type: 'uuid',
    lookup: true,
    required: false,
    max_length: null,
  };
  data.sections[0]!.fields.push(reference);
  server = record({ values: { ...server.values, manager: otherId } });
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  const queries: URLSearchParams[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const query = new URL(String(input), location.origin).searchParams;
    queries.push(query);
    return respond({
      data: {
        field: 'manager',
        items: [{ value: otherId, label: 'Manager' }],
        next: 'opaque-next',
      },
    });
  });
  const lookup = form.lookup('profile', 'manager');
  const signal = new AbortController().signal;
  expect(lookup.searchable).toBe(false);
  expect(
    await lookup.provider({ query: '', cursor: null, limit: 7, signal }),
  ).toEqual({
    options: [{ value: otherId, label: 'Manager' }],
    nextCursor: 'opaque-next',
  });
  expect(queries[0]?.get('public_id')).toBe(id);
  expect(queries[0]?.get('field')).toBe('manager');
  expect(form.records.value.profile?.values.name).toBe('Before');
  expect(
    await lookup.provider({
      query: '',
      cursor: 'opaque-next',
      limit: 7,
      signal,
    }),
  ).toMatchObject({ nextCursor: 'opaque-next' });
  expect(queries[1]?.get('cursor')).toBe('opaque-next');
});

it('Admin forms refuse secret or undeclared preview differences before exposing a review', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [command({ preview_required: true })];
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  vi.mocked(window.fetch).mockResolvedValue(
    respond({
      data: {
        fingerprint: 'unsafe-review',
        changes: { token: 'private-review-value' },
        requirement: 'none',
        every_action: false,
      },
    }),
  );
  expect(await form.reviewCommand('profile', 'save')).toBeUndefined();
  expect(form.commandReviews.value).toEqual({});
  expect(JSON.stringify(form.error.value)).not.toContain(
    'private-review-value',
  );
});

it('Admin collection adapters preserve views and authorize export columns through current presentation', async () => {
  const data = descriptor();
  data.sections[0]!.view = 'table';
  data.sections[0]!.export_allowed = true;
  const page: AdminPageController = {
    page: shallowRef(data),
    state: computed(() => 'ready'),
    error: ref(),
    refresh: async () => {},
  };
  const exports: ExportProvider<TableQuery> = {
    capabilities: {
      execution: 'server',
      formats: ['csv'],
      scopes: ['filtered'],
      cancellation: false,
    },
    start: vi.fn(async (): Promise<ExportResult> => ({
      kind: 'download',
      download: {
        filename: 'safe.csv',
        mimeType: 'text/csv',
        source: new Blob(['safe']),
      },
    })),
  };
  vi.mocked(window.fetch).mockResolvedValue(
    respond({ data: { items: [server], next: null, total: null } }),
  );
  let table!: ReturnType<typeof useAdminTable>;
  mount(
    defineComponent({
      setup() {
        table = useAdminTable(page, 'profile', { adapters: { exports } });
        return () => h('div');
      },
    }),
  );
  await vi.waitFor(() => expect(table.resource.result.value).toBeDefined());
  table.views.create('Names');
  table.views.setColumns(
    table.views.columns.value.map((column) => ({
      ...column,
      visible: column.key === 'name',
    })),
  );
  expect(table.views.state.value.views).toHaveLength(1);
  const input = {
    format: 'csv' as const,
    scope: { kind: 'filtered' as const, query: table.query.value },
    columnKeys: ['name'],
    filename: 'safe',
  };
  expect(
    (
      await table.adapters.value.exports!.start(
        input,
        new AbortController().signal,
      )
    ).kind,
  ).toBe('download');
  expect(exports.start).toHaveBeenCalledTimes(1);
  await expect(
    table.adapters.value.exports!.start(
      { ...input, columnKeys: ['enabled'] },
      new AbortController().signal,
    ),
  ).rejects.toThrow();
  expect(exports.start).toHaveBeenCalledTimes(1);
});

it('Admin forms select a directory subject ahead of an initial descriptor record', async () => {
  const data = descriptor();
  data.sections[0]!.record = record();
  const reads: string[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    const subject = url.searchParams.get('public_id') ?? id;
    reads.push(subject);
    return respond({
      data: record({
        public_id: subject,
        values: {
          name: subject === id ? 'Actor' : 'Other account',
          enabled: false,
          token: { set: true },
        },
      }),
    });
  });
  const { form, state } = harness(data);
  await vi.waitFor(() =>
    expect(form.records.value.profile?.public_id).toBe(id),
  );
  expect(await form.selectSubject(otherId)).toBe(true);
  expect(reads.at(-1)).toBe(otherId);
  expect(form.records.value.profile?.public_id).toBe(otherId);
  expect(form.values.value['profile.name']).toBe('Other account');
  await form.refresh();
  expect(reads.at(-1)).toBe(otherId);
  state.value = 'forbidden';
  await flushPromises();
  expect(form.records.value).toEqual({});
});

it('Admin forms isolate optional independent workflow failures while required Save content remains blocking', async () => {
  const data = descriptor();
  const optional = {
    ...data.sections[0]!,
    key: 'independent',
    required: false,
    fields: [],
    commands: [],
    diagnostic: 'admin_binding_unavailable',
  };
  data.sections.push(optional);
  const { form, value } = harness(data);
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.setAvailable('independent', false);
  expect(form.blocked.value).toBe(false);
  value.value = {
    ...value.value,
    sections: value.value.sections.map((section) =>
      section.key === 'independent' ? { ...section, required: true } : section,
    ),
  };
  await flushPromises();
  expect(form.blocked.value).toBe(true);
});

it('Admin native HTTP operations share the mutation lane and preserve unrelated dirty fields', async () => {
  const { form } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  form.set('profile.name', 'Local draft');
  let release!: () => void;
  const gate = new Promise<void>((resolve) => {
    release = resolve;
  });
  const operation = form.runIndependent('profile', async (snapshot, signal) => {
    expect(snapshot.public_id).toBe(id);
    expect(snapshot.revision).toBe('r1');
    expect(signal.aborted).toBe(false);
    await gate;
    server = record({
      revision: 'r2',
      values: { name: 'Before', enabled: false, token: { set: true } },
    });
  });
  expect(form.busy.value).toBe(true);
  const competing = vi.fn(async () => {});
  expect(await form.runIndependent('profile', competing)).toBe(false);
  expect(competing).not.toHaveBeenCalled();
  release();
  expect(await operation).toBe(true);
  expect(form.busy.value).toBe(false);
  expect(form.values.value['profile.name']).toBe('Local draft');
  expect(form.values.value['profile.enabled']).toBe(false);
  expect(form.dirty.value).toBe(true);
});

it('Admin native HTTP operations abort and ignore completion after authority loss', async () => {
  const { form, state } = harness();
  await vi.waitFor(() => expect(form.records.value.profile).toBeDefined());
  let release!: () => void;
  let signal!: AbortSignal;
  const gate = new Promise<void>((resolve) => {
    release = resolve;
  });
  const operation = form.runIndependent('profile', async (_record, current) => {
    signal = current;
    await gate;
  });
  state.value = 'forbidden';
  await flushPromises();
  expect(signal.aborted).toBe(true);
  release();
  expect(await operation).toBe(false);
  expect(form.records.value).toEqual({});
  expect(form.notice.value).toBe('');
});

it('native atomic participants review and save one section with visible activity and preserve unrelated drafts', async () => {
  const data = descriptor();
  data.sections[0]!.commands = [
    command({ mode: 'atomic', preview_required: true }),
  ];
  data.sections.push({ ...data.sections[0]!, key: 'access', title: 'Access' });
  const { form } = harness(data);
  await vi.waitFor(() => expect(form.records.value.access).toBeDefined());
  form.set('profile.name', 'Changed profile');
  form.set('access.name', 'Keep unrelated draft');
  let release!: (response: Response) => void;
  const paths: string[] = [];
  vi.mocked(window.fetch).mockImplementation(async (input, init) => {
    if (init?.method !== 'POST') return respond({ data: server });
    paths.push(String(input));
    if (String(input).endsWith('/preview'))
      return new Promise((resolve) => {
        release = resolve;
      });
    return respond({
      data: {
        status: 'committed',
        record: record({
          revision: 'r2',
          values: { ...server.values, name: 'Changed profile' },
        }),
        operation_id: null,
      },
    });
  });
  const preview = form.reviewCommand('profile', 'save');
  await vi.waitFor(() => expect(release).toBeTypeOf('function'));
  expect(form.busy.value).toBe(true);
  release(
    respond({
      data: {
        fingerprint: 'native-review',
        changes: { name: { old: 'Before', new: 'Changed profile' } },
        requirement: 'none',
        every_action: false,
      },
    }),
  );
  await preview;
  expect(form.busy.value).toBe(false);
  expect(form.commandReviews.value['profile:save']?.fingerprint).toBe(
    'native-review',
  );
  expect((await form.execute('profile', 'save'))?.status).toBe('committed');
  expect(paths).toHaveLength(2);
  expect(paths.every((path) => path.includes('/sections/profile/'))).toBe(true);
  expect(form.values.value['access.name']).toBe('Keep unrelated draft');
  expect(form.dirty.value).toBe(true);
});
