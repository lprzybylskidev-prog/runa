/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { beforeEach, afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import DirectoryFilters from '../../../../src/runa/modules/auth/frontend/AuthDirectoryFilters.vue';
import AuthAdminBulkAction from '../../../../src/runa/modules/auth/frontend/AuthAdminBulkAction.vue';
import { DateTimeRangeInput, Select, NumberInput } from '@/runa/ui';
import type { TableQuery } from '@/runa/ui';
import { AuthAdminUsers, AuthAdminSection } from '@/runa/auth';
import { AdminForm, useAdminForm } from '@/runa/admin';
import type {
  AdminFieldData,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminSectionData,
} from '@/runa/admin';
enableAutoUnmount(afterEach);
beforeEach(() => localStorage.setItem('runa.locale', 'en'));
afterEach(() => localStorage.removeItem('runa.locale'));
const actor = '01900000-0000-7000-8000-000000000001';
const target = '01900000-0000-7000-8000-000000000002';
function field(key: string, readonly = true): AdminFieldData {
  return {
    key,
    label: key,
    type: 'text',
    readonly,
    sensitive: false,
    filterable: key === 'display_name' || key === 'state',
    sortable: false,
    lookup: false,
    required: false,
    max_length: null,
    choices: [],
    multiple: false,
    component: null,
  };
}
function section(
  key: string,
  fields: AdminFieldData[],
  view: 'record' | 'table' = 'record',
): AdminSectionData {
  return {
    key,
    title: key,
    view,
    readonly: false,
    required: false,
    component: null,
    record: null,
    diagnostic: null,
    fields,
    commands: [],
  };
}
function page(sections: AdminSectionData[]): AdminPageController {
  return {
    page: shallowRef<AdminPageData>({
      schema_version: 1,
      owner: 'runa.auth',
      key: 'users',
      title: 'Users',
      generation: 1,
      revision: 'page-1',
      component: null,
      sections,
    }),
    state: computed(() => 'ready'),
    error: ref(),
    refresh: vi.fn(async () => {}),
  };
}
const response = (data: unknown) =>
  new Response(JSON.stringify({ data }), {
    status: 200,
    headers: { 'Content-Type': 'application/json' },
  });
afterEach(() => vi.restoreAllMocks());

it('Auth deleted filter defaults to current records and exposes deleted and all choices', async () => {
  const update = vi.fn();
  const wrapper = mount(DirectoryFilters, {
    props: {
      fields: [
        {
          ...field('state'),
          type: 'dictionary',
          choices: ['current', 'deleted', 'all'],
        },
      ],
      query: { page: 1, perPage: 25, sort: null, filters: {} },
      update,
      resetKey: 0,
      setValidity: vi.fn(),
    },
  });
  const filter = wrapper.getComponent(Select);
  const presented = filter.props() as {
    label: string;
    modelValue: string;
    options: { value: string; label: string }[];
  };
  expect(presented.label).toBe('Deleted');
  expect(presented.modelValue).toBe('current');
  expect(presented.options).toEqual([
    { value: 'current', label: 'Not deleted' },
    { value: 'deleted', label: 'Deleted' },
    { value: 'all', label: 'All' },
  ]);
  filter.vm.$emit('update:modelValue', 'deleted');
  expect(update).toHaveBeenCalledWith(
    expect.objectContaining({ filters: { state: 'deleted' } }),
  );
});

it.each(['schedule_activation', 'delete', 'set_attempt_lock'])(
  'Auth bulk %s reviews impact and confirms through its password step only when required',
  async (operation) => {
    const protectedAction = operation === 'delete';
    const bulk = section('bulk_users', [
      { ...field('public_ids', false), type: 'uuid', multiple: true },
      { ...field('at', false), type: 'datetime' },
      field('summary'),
    ]);
    bulk.commands = [
      {
        key: operation,
        title: operation,
        inputs: ['public_ids', ...(protectedAction ? [] : ['at'])],
        mode: 'independent',
        requirement: protectedAction ? 'password' : 'none',
        destructive: true,
        preview_required: true,
        bulk: false,
        action: protectedAction ? 'runa.auth.users.bulk.delete' : null,
        available: true,
        intent: 'action',
      },
    ];
    const controller = page([bulk]);
    const writes: string[] = [];
    vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
      const path = new URL(String(input), window.location.origin).pathname;
      if (init?.method === 'POST') writes.push(path);
      if (path.endsWith('/preview'))
        return response({
          fingerprint: 'a'.repeat(64),
          changes: { summary: { accounts: 10, affected_accounts: 2 } },
          requirement: protectedAction ? 'password' : 'none',
          every_action: true,
        });
      if (path.endsWith('/step-up')) return response({ confirmed: true });
      if (path.endsWith('/commands'))
        return response({
          status: 'committed',
          record: null,
          operation_id: null,
        });
      return response({
        public_id: null,
        revision: '0',
        values: { public_ids: [target], at: null, summary: null },
      });
    });
    let form!: AdminFormController;
    const wrapper = mount(
      defineComponent({
        setup() {
          form = useAdminForm(controller);
          return () =>
            h(
              AdminForm,
              { controller: form },
              {
                default: () =>
                  h(AuthAdminBulkAction, {
                    page: controller,
                    section: bulk,
                    controller: form,
                  }),
              },
            );
        },
      }),
    );
    await vi.waitFor(() =>
      expect(wrapper.text()).toContain('Affected accounts'),
    );
    expect(wrapper.text()).not.toContain('Confirm required operation proof');
    expect(wrapper.find('input[type=password]').exists()).toBe(false);
    expect(wrapper.findAllComponents(DateTimeRangeInput)).toHaveLength(0);
    if (!protectedAction) expect(wrapper.find('input').exists()).toBe(true);
    if (operation === 'set_attempt_lock') {
      await vi.waitFor(() => expect(form.busy.value).toBe(false));
      wrapper
        .getComponent(Select)
        .vm.$emit('update:modelValue', 'set_attempt_lock:clear');
      await vi.waitFor(() =>
        expect(writes.filter((path) => path.endsWith('/preview'))).toHaveLength(
          2,
        ),
      );
      await vi.waitFor(() => expect(form.busy.value).toBe(false));
      expect(form.values.value['bulk_users.at']).toBeNull();
      expect(wrapper.find('input').exists()).toBe(false);
    }
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Confirm')!
      .trigger('click');
    if (protectedAction) {
      await vi.waitFor(() =>
        expect(wrapper.find('input[type=password]').exists()).toBe(true),
      );
      expect(writes.some((path) => path.endsWith('/commands'))).toBe(false);
      await wrapper.get('input[type=password]').setValue('admin-password');
      await wrapper
        .findAll('button')
        .find((button) => button.text() === 'Confirm')!
        .trigger('click');
    }
    await vi.waitFor(() =>
      expect(writes.filter((path) => path.endsWith('/commands'))).toHaveLength(
        1,
      ),
    );
    expect(writes.filter((path) => path.endsWith('/step-up'))).toHaveLength(
      protectedAction ? 1 : 0,
    );
    expect(form.confirmation.value).toBeUndefined();
  },
);

it('Auth administration user page selects another account through the shared guarded form controller', async () => {
  const controller = page([
    section('directory', [field('display_name'), field('state')], 'table'),
    section('profile', [field('name', false)]),
  ]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname.endsWith('/directory'))
      return response({
        items: [
          {
            public_id: target,
            revision: '1',
            values: { display_name: 'Directory target', state: 'current' },
          },
        ],
        next: null,
        total: null,
      });
    const id = url.searchParams.get('public_id') ?? actor;
    return response({
      public_id: id,
      revision: '1',
      values: { name: id === target ? 'Selected target' : 'Current actor' },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          autoRead: false,
          confirmLeave: async () => false,
        });
        return () => h(AuthAdminUsers, { controller, form });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Directory target'));
  expect(wrapper.text()).toContain('User directory');
  const open = wrapper
    .findAll('button')
    .find((button) => button.text() === 'Details');
  expect(open).toBeDefined();
  await open!.trigger('click');
  await vi.waitFor(() =>
    expect(form.records.value.profile?.public_id).toBe(target),
  );
  expect(form.values.value['profile.name']).toBe('Selected target');
  await vi.waitFor(() =>
    expect(
      document.querySelector<HTMLInputElement>('[role=dialog] input')?.value,
    ).toBe('Selected target'),
  );
  expect(wrapper.find('[role=dialog]').exists()).toBe(false);
});

it('Auth administration collection sections use only authorized fields and expose safe entry selection', async () => {
  const secondary = section('secondary', [
    field('addresses'),
    { ...field('address_id', false), type: 'uuid' },
    field('email', false),
  ]);
  const controller = page([secondary]);
  vi.spyOn(window, 'fetch').mockImplementation(async () =>
    response({
      public_id: target,
      revision: '1',
      values: {
        addresses: JSON.stringify([
          {
            public_id: target,
            email: 'secondary@example.test',
            verified: false,
          },
        ]),
        address_id: null,
        email: null,
      },
    }),
  );
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller);
        return () =>
          h(AuthAdminSection, {
            page: controller,
            section: secondary,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('secondary@example.test'),
  );
  expect(wrapper.text()).not.toContain('token_hash');
  const use = wrapper
    .findAll('button')
    .find((button) => button.text() === 'Use this entry');
  await use!.trigger('click');
  expect(form.values.value['secondary.address_id']).toBe(target);
  controller.page.value = {
    ...controller.page.value!,
    sections: [
      {
        ...secondary,
        fields: secondary.fields.filter((item) => item.key !== 'addresses'),
      },
    ],
  };
  await vi.waitFor(() =>
    expect(form.values.value['secondary.addresses']).toBeUndefined(),
  );
});

it('Auth administration user page moves the exact table selection into the native bulk review', async () => {
  const bulk = section('bulk_sessions', [
    { ...field('public_ids', false), type: 'uuid', multiple: true },
    { ...field('trust', false), type: 'boolean' },
    field('summary'),
  ]);
  bulk.commands = [
    {
      key: 'revoke',
      title: 'Revoke selected sessions and trust',
      mode: 'independent',
      destructive: true,
      requirement: 'none',
      preview_required: true,
      inputs: ['public_ids', 'trust'],
      bulk: false,
      action: null,
      available: true,
      intent: 'action',
    },
  ];
  const controller = page([
    section('directory', [field('display_name'), field('state')], 'table'),
    bulk,
  ]);
  const calls: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    calls.push(url.pathname);
    if (url.pathname.endsWith('/directory'))
      return response({
        items: [
          {
            public_id: target,
            revision: '1',
            values: { display_name: 'Bulk target', state: 'current' },
          },
        ],
        next: null,
        total: 1,
      });
    return response({
      public_id: null,
      revision: '0',
      values: { public_ids: [], trust: false, summary: null },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          autoRead: false,
          confirmLeave: async () => true,
        });
        return () => h(AuthAdminUsers, { controller, form });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Bulk target'));
  const trigger = () =>
    wrapper
      .findAll('button')
      .find(
        (button) =>
          button.attributes('data-bulk-command') === 'bulk_sessions:revoke',
      )!;
  expect(trigger().attributes('disabled')).toBeDefined();
  await wrapper.get('tbody input[type=checkbox]').setValue(true);
  await trigger().trigger('click');
  await vi.waitFor(() =>
    expect(form.values.value['bulk_sessions.public_ids']).toEqual([target]),
  );
  expect(form.records.value.bulk_sessions?.public_id).toBeNull();
  expect(calls).toContain('/api/admin/runa.auth/users/sections/bulk_sessions');
  expect(calls.some((path) => path.includes('/runa.auth/bulk_sessions/'))).toBe(
    false,
  );
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')?.textContent).toContain(
      'Selected accounts: 1',
    ),
  );
  expect(document.querySelector('[role=dialog]')?.textContent).not.toContain(
    target,
  );
});

it('Auth administrative avatar uses native multipart target and version with dirty protection', async () => {
  const avatar = section('avatar', [
    { ...field('has_avatar'), type: 'boolean' },
    { ...field('avatar_max_kib'), type: 'integer' },
    { ...field('avatar_upload_pending', false), type: 'boolean' },
  ]);
  const controller = page([avatar]);
  let version = 1;
  let uploaded: FormData | undefined;
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    if (init?.method === 'POST') {
      expect(new URL(String(input), window.location.origin).pathname).toBe(
        '/api/runa/auth/users/avatar',
      );
      expect(init.body).toBeInstanceOf(FormData);
      uploaded = init.body as FormData;
      version = 2;
      return response({ updated: true, version });
    }
    return response({
      public_id: target,
      revision: String(version),
      values: {
        has_avatar: version === 2,
        avatar_max_kib: 2048,
        avatar_upload_pending: false,
      },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller);
        return () =>
          h(
            AdminForm,
            { controller: form },
            {
              default: () =>
                h(AuthAdminSection, {
                  page: controller,
                  section: avatar,
                  controller: form,
                }),
            },
          );
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('input[type=file]').exists()).toBe(true),
  );
  const transfer = new DataTransfer();
  transfer.items.add(
    new File(['fixture'], 'avatar.png', { type: 'image/png' }),
  );
  (wrapper.get('input[type=file]').element as HTMLInputElement).files =
    transfer.files;
  await wrapper.get('input[type=file]').trigger('change');
  await vi.waitFor(() => expect(form.dirty.value).toBe(true));
  const save = wrapper
    .findAll('button')
    .find((button) => button.text() === 'Save avatar')!;
  await save.trigger('click');
  await vi.waitFor(() => expect(uploaded).toBeDefined());
  expect(uploaded!.get('public_id')).toBe(target);
  expect(uploaded!.get('version')).toBe('1');
  expect(uploaded!.get('avatar')).toBeInstanceOf(File);
  await vi.waitFor(() => expect(form.dirty.value).toBe(false));
  expect(form.records.value.avatar?.revision).toBe('2');
});

it('Auth administration keeps filters above bulk actions and creation in the directory header', async () => {
  const directory = section(
    'directory',
    [
      { ...field('display_name'), filterable: true },
      { ...field('two_factor_enabled'), type: 'boolean', filterable: true },
      { ...field('created_at'), type: 'datetime', filterable: true },
    ],
    'table',
  );
  const creation = section('creation', [field('email', false)]);
  creation.commands = [
    {
      key: 'create',
      title: 'Create account',
      inputs: ['email'],
      mode: 'independent',
      requirement: 'none',
      destructive: false,
      preview_required: false,
      bulk: false,
      action: null,
      available: true,
      intent: 'create',
    },
  ];
  const bulk = section('bulk_users', [
    { ...field('public_ids', false), type: 'uuid', multiple: true },
    { ...field('at', false), type: 'datetime' },
    { ...field('enabled', false), type: 'boolean' },
    field('summary'),
  ]);
  bulk.commands = ['deactivate', 'password_reset', 'factor_reset'].map(
    (key) => ({
      key,
      title: key,
      inputs: ['public_ids'],
      mode: 'independent',
      requirement: 'both',
      destructive: true,
      preview_required: true,
      bulk: false,
      action: 'runa.auth.users.bulk.' + key,
      available: true,
      intent: 'action',
    }),
  );
  const controller = page([directory, creation, bulk]);
  const calls: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    calls.push(url.pathname);
    return url.pathname.endsWith('/directory')
      ? response({
          items: [
            {
              public_id: target,
              revision: '1',
              values: {
                display_name: 'Bulk fact target',
                two_factor_enabled: true,
                created_at: '2026-11-02T12:00:00Z',
              },
            },
          ],
          next: null,
          total: 1,
        })
      : response({
          public_id: null,
          revision: '0',
          values: { public_ids: [], at: null, enabled: true, summary: null },
        });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          autoRead: false,
          confirmLeave: async () => true,
        });
        return () => h(AuthAdminUsers, { controller, form });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Bulk fact target'));
  const filters = wrapper.get('[data-table-filters]').element;
  const actions = wrapper.get('[data-auth-bulk-actions]').element;
  expect(
    filters.compareDocumentPosition(actions) & Node.DOCUMENT_POSITION_FOLLOWING,
  ).toBeTruthy();
  expect(
    wrapper.get('[data-auth-bulk-actions]').findAll('button'),
  ).toHaveLength(3);
  expect(
    wrapper.get('[data-auth-bulk-actions]').findAllComponents(Select),
  ).toHaveLength(0);
  const header = wrapper
    .findAll('header')
    .find((node) => node.text().includes('User directory'));
  expect(header?.text()).toContain('Create account');
  await wrapper.get('tbody input[type=checkbox]').setValue(true);
  await wrapper
    .findAll('button')
    .find((b) => b.attributes('data-bulk-command') === 'bulk_users:activity')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(form.values.value['bulk_users.public_ids']).toEqual([target]),
  );
  const dialog = document.querySelector('[role=dialog]')!;
  expect(dialog.textContent).toContain('deactivate');
  expect(dialog.textContent).not.toContain('password_reset');
  expect(calls).toContain('/api/admin/runa.auth/users/sections/bulk_users');
});

it('Auth administration range filters map compact ranges and reset them with shared filter validity', async () => {
  const query = ref<TableQuery>({
    page: 3,
    perPage: 25,
    sort: null,
    filters: {},
  });
  const resetKey = ref(0);
  const validity = vi.fn();
  const update = vi.fn((value: TableQuery) => {
    query.value = value;
  });
  const fields = [
    { ...field('created_at'), type: 'datetime', filterable: true },
    { ...field('active'), type: 'boolean', filterable: true },
    { ...field('session_count'), type: 'integer', filterable: true },
  ];
  const wrapper = mount(
    defineComponent({
      setup() {
        return () =>
          h(DirectoryFilters, {
            fields,
            query: query.value,
            update,
            resetKey: resetKey.value,
            setValidity: validity,
          });
      },
    }),
  );
  wrapper.findComponent(Select).vm.$emit('update:modelValue', 'false');
  await vi.waitFor(() => expect(query.value.filters.active).toBe('false'));
  wrapper.findComponent(NumberInput).vm.$emit('update:modelValue', '0');
  await vi.waitFor(() => expect(query.value.filters.session_count).toBe('0'));
  expect(wrapper.findComponent(DateTimeRangeInput).props('size')).toBe('sm');
  expect((wrapper.findComponent(Select).props() as { size: string }).size).toBe(
    'sm',
  );
  expect(wrapper.findComponent(NumberInput).props('size')).toBe('sm');
  const range = wrapper.findComponent(DateTimeRangeInput);
  range.vm.$emit('update:modelValue', {
    start: '2026-11-02T12:00:00Z',
    end: null,
  });
  await vi.waitFor(() =>
    expect(JSON.parse(query.value.filters.created_at!)).toEqual({
      from: '2026-11-02T12:00:00Z',
    }),
  );
  query.value = { ...query.value, filters: {} };
  resetKey.value++;
  await vi.waitFor(() =>
    expect(
      wrapper.findComponent(DateTimeRangeInput).props('modelValue'),
    ).toEqual({ start: null, end: null }),
  );
  expect(wrapper.findAll('fieldset')).toHaveLength(0);
});

it('grouped bulk operations choose one native command and invalidate the previous review', async () => {
  const bulk = section('bulk_users', [
    { ...field('public_ids', false), type: 'uuid', multiple: true },
    field('summary'),
  ]);
  bulk.commands = ['activate', 'deactivate', 'toggle_activation'].map(
    (key) => ({
      key,
      title: key,
      inputs: ['public_ids'],
      mode: 'independent',
      requirement: 'none',
      destructive: true,
      preview_required: true,
      bulk: false,
      action: null,
      available: true,
      intent: 'action',
    }),
  );
  const controller = page([bulk]);
  const previews: string[] = [];
  const writes: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    const path = new URL(String(input), window.location.origin).pathname;
    const body = init?.body
      ? (JSON.parse(String(init.body)) as { command: string })
      : null;
    if (path.endsWith('/preview')) {
      previews.push(body!.command);
      return response({
        fingerprint: 'a'.repeat(64),
        changes: { summary: { accounts: 2, affected_accounts: 2 } },
        requirement: 'none',
        every_action: true,
      });
    }
    if (path.endsWith('/commands')) {
      writes.push(body!.command);
      return response({
        status: 'committed',
        record: null,
        operation_id: null,
      });
    }
    return response({
      public_id: null,
      revision: '0',
      values: { public_ids: [target], summary: null },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller);
        return () =>
          h(
            AdminForm,
            { controller: form },
            {
              default: () =>
                h(AuthAdminBulkAction, {
                  page: controller,
                  section: bulk,
                  controller: form,
                }),
            },
          );
      },
    }),
  );
  await vi.waitFor(() => expect(previews).toEqual(['activate']));
  await vi.waitFor(() => expect(form.busy.value).toBe(false));
  wrapper
    .getComponent(Select)
    .vm.$emit('update:modelValue', 'toggle_activation');
  await vi.waitFor(() =>
    expect(previews).toEqual(['activate', 'toggle_activation']),
  );
  await vi.waitFor(() => expect(form.busy.value).toBe(false));
  expect(writes).toEqual([]);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Confirm')!
    .trigger('click');
  await vi.waitFor(() => expect(writes).toEqual(['toggle_activation']));
});
