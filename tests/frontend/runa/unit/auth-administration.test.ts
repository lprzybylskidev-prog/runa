/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { savePreference } from '../../../../src/runa/resources/js/lib/preferences';
import { beforeEach, afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, nextTick, ref, shallowRef } from 'vue';
import DirectoryFilters from '../../../../src/runa/modules/auth/frontend/AuthDirectoryFilters.vue';
import AuthAdminBulkAction from '../../../../src/runa/modules/auth/frontend/AuthAdminBulkAction.vue';
import { DateTimeRangeInput, Select, NumberInput } from '@/runa/ui';
import type { TableQuery } from '@/runa/ui';
import { AuthAdminUsers, AuthAdminSection } from '@/runa/auth';
import { AdminForm, useAdminForm } from '@/runa/admin';
import { ApiError } from '@/runa/http';
import { writeFailure } from '../../../../src/runa/modules/admin/frontend/writeFailure';
import type {
  AdminFieldData,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminSectionData,
} from '@/runa/admin';
enableAutoUnmount(afterEach);
beforeEach(() => savePreference('runa.locale', 'en'));
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
    await vi.waitFor(() =>
      expect(
        form.commandReviews.value[`bulk_users:${operation}`],
      ).toBeDefined(),
    );
    await vi.waitFor(() => expect(form.busy.value).toBe(false));
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
      'Revoke selected sessions and trust',
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

it.each([
  [false, 0],
  [true, 0],
  [false, 2],
  [true, 2],
])(
  'grouped bulk operations refresh impact and allow writes while skipping protected accounts (%s, %s)',
  async (protectedAccounts, ineligibleAccounts) => {
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
          changes: {
            summary: {
              accounts: 2,
              affected_accounts: 2,
              skipped_accounts:
                (protectedAccounts ? 1 : 0) + Number(ineligibleAccounts),
              ineligible_accounts: Number(ineligibleAccounts),
            },
          },
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
    const initialReview = form.commandReviews.value['bulk_users:activate'];
    wrapper.getComponent(Select).vm.$emit('update:modelValue', 'activate');
    await vi.waitFor(() =>
      expect(form.commandReviews.value['bulk_users:activate']).toBe(
        initialReview,
      ),
    );
    expect(previews).toEqual(['activate']);

    wrapper
      .getComponent(Select)
      .vm.$emit('update:modelValue', 'toggle_activation');
    await vi.waitFor(() =>
      expect(previews).toEqual(['activate', 'toggle_activation']),
    );
    await vi.waitFor(() => expect(form.busy.value).toBe(false));
    expect(writes).toEqual([]);
    if (protectedAccounts) {
      expect(wrapper.get('[data-bulk-skipped]').text()).toContain(
        'Superadministrator',
      );
      expect(wrapper.get('[data-bulk-impact]').text()).toContain('2');
    }
    expect(wrapper.find('[data-bulk-ineligible]').exists()).toBe(
      !!ineligibleAccounts,
    );
    if (ineligibleAccounts)
      expect(wrapper.get('[data-bulk-ineligible]').text()).toContain(
        'Skipped accounts: 2',
      );
    expect(wrapper.find('[data-bulk-skipped]').exists()).toBe(
      !!protectedAccounts,
    );
    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Confirm')!
      .trigger('click');
    await vi.waitFor(() => expect(writes).toEqual(['toggle_activation']));
  },
);

it.each([
  'identifier_conflict',
  'schedule_must_be_future',
  'invalid_schedule_order',
  'identity_deleted',
  'invitation_unavailable',
  'password_reset_unavailable',
  'user_change_refused',
])(
  'bulk business refusal %s keeps its safe reason instead of claiming a stale write',
  (reason) => {
    for (const payload of [{ error: { code: reason } }, { reason }]) {
      const failure = writeFailure(new ApiError(409, payload), []);
      expect(failure.reason).toBe(reason);
      expect(failure.message).not.toContain('Check the current values');
      expect(failure.message).not.toContain('currently unavailable');
    }
    expect(
      writeFailure(
        new ApiError(409, { error: { code: 'user_preview_stale' } }),
        [],
      ).message,
    ).toContain('Check the current values');
  },
);

it('rebuilt user details groups capabilities and keeps revision tokens out of operator inputs', async () => {
  const { default: Details } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserDetails.vue');
  const controller = page([
    section('profile', [field('name', false)]),
    section('preferences', [
      field('locale', false),
      field('theme', false),
      field('delivery', false),
      field('categories'),
      field('recipients'),
      { ...field('preference_revision', false), type: 'integer' },
    ]),
    section('security', [field('password_set')]),
    section('sessions', [field('sessions')]),
    section('exceptions', [
      field('exceptions'),
      field('key', false),
      { ...field('exception_version', false), type: 'integer' },
      { ...field('policy_revision', false), type: 'integer' },
    ]),
  ]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    const key = url.pathname.split('/').at(-1);
    const values =
      key === 'preferences'
        ? {
            locale: 'pl',
            theme: 'system',
            delivery: '[]',
            categories: '[]',
            recipients: '[]',
            preference_revision: 7,
          }
        : key === 'exceptions'
          ? {
              exceptions: '[]',
              key: 'session_idle_minutes',
              exception_version: 0,
              policy_revision: 8,
            }
          : key === 'security'
            ? { password_set: 'Yes' }
            : { name: 'Target account' };
    return response({ public_id: target, revision: '1', values });
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () => h(Details, { controller, form });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.findAll('[role=tab]')).toHaveLength(3));
  expect(wrapper.findAll('[role=tab]').map((tab) => tab.text())).toEqual([
    'User',
    'Security',
    'Rules and exceptions',
  ]);
  expect(wrapper.find('input[name=preference_revision]').exists()).toBe(false);
  expect(wrapper.text()).not.toContain('preference_revision');
  await wrapper.findAll('[role=tab]')[2]!.trigger('click');
  await vi.waitFor(() =>
    expect(wrapper.find('[data-user-section=exceptions]').exists()).toBe(true),
  );
  expect(wrapper.text()).not.toContain('exception_version');
  expect(wrapper.text()).not.toContain('policy_revision');
});

it('rebuilt user detail actions request identity proof only after operation submission', async () => {
  const { default: Details } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserDetails.vue');
  const security = section('security', [field('password_set')]);
  security.commands = [
    {
      key: 'password_reset',
      title: 'Request password reset',
      mode: 'independent',
      intent: 'action',
      inputs: [],
      requirement: 'password',
      action: 'runa.auth.password.admin_reset',
      destructive: true,
      bulk: false,
      preview_required: false,
      available: true,
    },
  ];
  const controller = page([
    section('profile', [field('name', false)]),
    security,
  ]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    return response({
      public_id: target,
      revision: '1',
      values: url.pathname.endsWith('/security')
        ? { password_set: 'Yes' }
        : { name: 'Target account' },
    });
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () => h(Details, { controller, form });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('Request password reset'),
  );
  expect(
    wrapper
      .findAll('button')
      .find((button) => button.text() === 'Request password reset')!
      .attributes('data-tone'),
  ).toBe('warning');
  expect(document.querySelector('input[type=password]')).toBeNull();
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Request password reset')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')).not.toBeNull(),
  );
  expect(document.querySelector('input[type=password]')).toBeNull();
  expect(document.querySelector('[role=dialog]')?.textContent).not.toContain(
    'This operation applies only to this user.',
  );
  const submit = document.querySelector<HTMLButtonElement>(
    '[role=dialog] button[type=submit]',
  );
  submit!.click();
  await vi.waitFor(() =>
    expect(document.querySelector('input[type=password]')).not.toBeNull(),
  );
  expect(document.body.textContent).toContain('Confirm your identity');
});

it('user detail identity uses creation fields and reverts only its own draft', async () => {
  const { default: UserSection } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserSection.vue');
  const profile = section('profile', [
    field('name', false),
    field('first_name', false),
    field('surname', false),
    field('login', false),
    field('identifier_mode'),
  ]);
  profile.commands = [
    {
      key: 'save',
      title: 'Save details',
      mode: 'atomic',
      intent: 'save',
      inputs: ['name', 'first_name', 'surname', 'login'],
      requirement: 'none',
      action: null,
      destructive: false,
      bulk: false,
      preview_required: false,
      available: true,
    },
  ];
  const preferences = section('preferences', [field('theme', false)]);
  const controller = page([profile, preferences]);
  const submitted = vi.fn();
  let form!: AdminFormController;
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    response({
      public_id: target,
      revision: '1',
      values: String(input).includes('/preferences')
        ? { theme: 'system' }
        : {
            name: 'Target account',
            first_name: null,
            surname: null,
            login: null,
            identifier_mode: 'email',
          },
    }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () =>
          h(UserSection, {
            page: controller,
            section: profile,
            controller: form,
            onSubmit: submitted,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('input[name=name]').exists()).toBe(true),
  );
  expect(wrapper.text()).toContain('User details');
  expect(wrapper.text()).toContain('Name format');
  expect(wrapper.find('input[name=login]').exists()).toBe(false);
  expect(wrapper.find('input[name=name]').attributes('maxlength')).toBe('160');
  await form.activateSection('preferences');
  form.set(form.fieldPath('preferences', 'theme'), 'dark');
  await wrapper.get('input[type=radio][value=full]').setValue();
  expect(form.values.value[form.fieldPath('profile', 'name')]).toBeNull();
  await wrapper.get('form').trigger('submit');
  expect(submitted).not.toHaveBeenCalled();
  expect(wrapper.text()).toContain('Complete this field.');
  await wrapper.get('input[name=first_name]').setValue('New');
  await wrapper.get('input[name=surname]').setValue('Name');
  await wrapper.get('input[type=radio][value=single]').setValue();
  expect(wrapper.get<HTMLInputElement>('input[name=name]').element.value).toBe(
    'Target account',
  );
  await wrapper.get('input[type=radio][value=full]').setValue();
  expect(
    wrapper.get<HTMLInputElement>('input[name=first_name]').element.value,
  ).toBe('New');
  expect(
    wrapper.get<HTMLInputElement>('input[name=surname]').element.value,
  ).toBe('Name');
  await wrapper.get('form').trigger('submit');
  expect(submitted).toHaveBeenCalledWith('profile', 'save', []);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Revert changes')!
    .trigger('click');
  expect(wrapper.get<HTMLInputElement>('input[name=name]').element.value).toBe(
    'Target account',
  );
  expect(form.values.value[form.fieldPath('preferences', 'theme')]).toBe(
    'dark',
  );
});

it('user email form validates and submits the selected native workflow on Enter', async () => {
  const { default: UserSection } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserSection.vue');
  const email = section('primary_email', [
    field('current_email'),
    { ...field('email', false), required: true },
    { ...field('replacement_email', false), required: true },
  ]);
  email.commands = ['change', 'replace'].map((key) => ({
    key,
    title: key === 'change' ? 'Change primary email' : 'Replace verified email',
    mode: 'independent',
    intent: 'action',
    inputs: [key === 'change' ? 'email' : 'replacement_email'],
    requirement: 'password',
    action: 'runa.auth.email.admin_change',
    destructive: true,
    bulk: false,
    preview_required: false,
    available: true,
  }));
  const controller = page([email]);
  const submitted = vi.fn();
  vi.spyOn(window, 'fetch').mockImplementation(async () =>
    response({
      public_id: target,
      revision: '1',
      values: {
        current_email: 'target@example.test',
        email: null,
        replacement_email: null,
      },
    }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () =>
          h(UserSection, {
            page: controller,
            section: email,
            controller: form,
            onSubmit: submitted,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('input[name=email]').exists()).toBe(true),
  );
  expect(wrapper.find('input[name=replacement_email]').exists()).toBe(false);
  await wrapper.get('input[name=email]').setValue('invalid');
  await wrapper.get('form').trigger('submit');
  expect(submitted).not.toHaveBeenCalled();
  expect(wrapper.text()).toContain('Enter a valid email address.');
  await wrapper.get('input[name=email]').setValue('new@example.test');
  await wrapper.get('form').trigger('submit');
  expect(submitted).toHaveBeenLastCalledWith('primary_email', 'change', []);
  await wrapper.get('input[type=radio][value=replace]').setValue();
  expect(wrapper.find('input[name=email]').exists()).toBe(false);
  await wrapper
    .get('input[name=replacement_email]')
    .setValue('verified@example.test');
  await wrapper.get('form').trigger('submit');
  expect(submitted).toHaveBeenLastCalledWith('primary_email', 'replace', []);
});

it('hides admin recipient selectors when the owning module disables secondary emails', async () => {
  const { default: UserSection } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserSection.vue');
  const preferences = section(
    'preferences',
    [
      'locale',
      'theme',
      'delivery',
      'categories',
      'recipients',
      'recipient_selection_enabled',
    ].map((key) => ({
      ...field(key, !['locale', 'theme', 'delivery'].includes(key)),
      type:
        key === 'recipient_selection_enabled'
          ? ('boolean' as const)
          : ('text' as const),
    })),
  );
  const controller = page([preferences]);
  vi.spyOn(window, 'fetch').mockImplementation(async () =>
    response({
      public_id: target,
      revision: '1',
      values: {
        locale: 'en',
        theme: 'system',
        recipient_selection_enabled: false,
        categories: JSON.stringify([
          {
            module: 'runa.auth',
            key: 'optional',
            names: { pl: 'Nowa przeglądarka', en: 'New browser' },
            mandatory: false,
            sensitive: false,
          },
        ]),
        delivery: JSON.stringify([
          {
            module: 'runa.auth',
            key: 'optional',
            enabled: true,
            recipients: ['primary'],
          },
        ]),
        recipients: JSON.stringify([
          { value: 'primary', label: 'target@example.test' },
          { value: actor, label: 'secondary@example.test' },
        ]),
      },
    }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () =>
          h(UserSection, {
            page: controller,
            section: preferences,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(
      wrapper.find('[data-auth-email-preference-fields]').exists(),
      wrapper.html(),
    ).toBe(true),
  );
  expect(wrapper.findComponent(Select).exists()).toBe(false);
  expect(wrapper.text()).not.toContain('Required security messages');
});

it('assignment catalogue searches on the server while retaining named selections outside the current page', async () => {
  const { PermissionsAssignments } = await import('@/runa/permissions');
  const assignments = section('runa.permissions.user_access.assignments', [
    { ...field('roles', false), type: 'uuid', multiple: true, lookup: true },
    {
      ...field('permissions', false),
      type: 'uuid',
      multiple: true,
      lookup: true,
    },
  ]);
  assignments.commands = [
    {
      key: 'save',
      title: 'Save access',
      mode: 'atomic',
      intent: 'save',
      inputs: ['roles', 'permissions'],
      requirement: 'both',
      action: 'runa.permissions.access.manage',
      destructive: true,
      bulk: false,
      preview_required: true,
      available: true,
    },
  ];
  const controller = page([assignments]);
  const searched: string[] = [];
  const secondRole = '01900000-0000-7000-8000-000000000003';
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    if (init?.method && init.method !== 'GET')
      throw new Error('Unexpected write.');
    const url = new URL(String(input), window.location.origin);
    if (url.searchParams.get('kind') === 'options') {
      const filters = JSON.parse(url.searchParams.get('filters') ?? '{}') as {
        search?: string;
        selected?: string;
      };
      const key = url.searchParams.get('field');
      if (filters.search) searched.push(filters.search);
      return response({
        field: key,
        items:
          key === 'permissions'
            ? []
            : filters.selected
              ? [{ value: actor, label: 'Assigned role' }]
              : filters.search === 'finance'
                ? [{ value: secondRole, label: 'Finance role' }]
                : [{ value: actor, label: 'Assigned role' }],
        next: null,
      });
    }
    return response({
      public_id: target,
      revision: '1',
      values: { roles: [actor], permissions: [] },
    });
  });
  let form!: AdminFormController;
  const submitted = vi.fn();
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () =>
          h(PermissionsAssignments, {
            page: controller,
            section: assignments,
            controller: form,
            onSubmit: submitted,
          });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Assigned role'));
  const roles = wrapper.get('[data-assignment-field=roles]');
  await roles.get('input[type=text]').setValue('finance');
  await vi.waitFor(() => expect(searched).toContain('finance'));
  await vi.waitFor(() => expect(roles.text()).toContain('Finance role'));
  expect(roles.text()).toContain('Assigned role');
  await roles.get('input[type=checkbox]:not([role=switch])').setValue(true);
  expect(form.values.value[form.fieldPath(assignments.key, 'roles')]).toEqual([
    actor,
    secondRole,
  ]);
  await wrapper.get('form').trigger('submit');
  expect(submitted).toHaveBeenCalledWith(assignments.key, 'save', []);
});

it('email management validates native commands and keeps secondary row identity out of visible inputs', async () => {
  const { default: Emails } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminEmails.vue');
  const primary = section('primary_email', [
    field('current_email'),
    field('email', false),
  ]);
  const secondary = section('secondary', [
    field('addresses'),
    { ...field('address_id', false), type: 'uuid' },
    field('email', false),
  ]);
  for (const [source, keys] of [
    [primary, ['change']],
    [secondary, ['add', 'change', 'remove']],
  ] as const) {
    source.commands = keys.map((key) => ({
      key,
      title: key,
      mode: 'independent',
      intent: 'action',
      inputs: ['email'],
      requirement: 'password',
      action: key,
      destructive: key === 'remove',
      bulk: false,
      preview_required: false,
      available: true,
    }));
  }
  const controller = page([primary, secondary]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    response({
      public_id: target,
      revision: '1',
      values: String(input).includes('/secondary')
        ? {
            addresses: JSON.stringify([
              { public_id: actor, email: 'other@example.test', verified: true },
            ]),
            address_id: null,
            email: '',
          }
        : { current_email: 'target@example.test', email: '' },
    }),
  );
  let form!: AdminFormController;
  const submit = vi.fn();
  const operation = vi.fn();
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () =>
          h(Emails, {
            page: controller,
            controller: form,
            section: primary,
            sources: [primary, secondary],
            onSubmit: submit,
            onOperation: operation,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('other@example.test'),
  );
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Edit')!
    .trigger('click');
  const { TextInput, Form, Dialog } = await import('@/runa/ui');
  const editor = wrapper.getComponent(Dialog);
  expect(editor.getComponent(TextInput).props('modelValue')).toBe(
    'other@example.test',
  );
  expect(document.querySelector('input[name=address_id]')).toBeNull();
  editor.getComponent(TextInput).vm.$emit('update:modelValue', 'invalid');
  await editor.getComponent(Form).trigger('submit');
  expect(submit).not.toHaveBeenCalled();
  editor
    .getComponent(TextInput)
    .vm.$emit('update:modelValue', 'updated@example.test');
  await nextTick();
  await editor.getComponent(Form).trigger('submit');
  expect(submit).toHaveBeenCalledWith('secondary', 'change', []);
  expect(form.values.value[form.fieldPath('secondary', 'address_id')]).toBe(
    actor,
  );
  expect(form.values.value[form.fieldPath('secondary', 'email')]).toBe(
    'updated@example.test',
  );
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Remove')!
    .trigger('click');
  expect(operation).toHaveBeenCalledWith('secondary', 'remove', {
    address_id: actor,
  });
});
