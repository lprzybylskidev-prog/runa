import PasswordConfirmationHost from './fixtures/PasswordConfirmationHost.vue';
/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { savePreference } from '../../../../src/runa/resources/js/lib/preferences';
import { beforeEach, afterEach, expect, it, vi } from '@runa/testing/vitest';
import { DOMWrapper, enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, nextTick, ref, shallowRef } from 'vue';
import DirectoryFilters from '../../../../src/runa/modules/auth/frontend/AuthDirectoryFilters.vue';
import AuthAdminBulkAction from '../../../../src/runa/modules/auth/frontend/AuthAdminBulkAction.vue';
import { DateTimeRangeInput, Select, NumberInput } from '@/runa/ui';
import type { TableQuery, SelectionProvider } from '@/runa/ui';
import { AuthAdminUsers, AuthAdminSection } from '@/runa/auth';
import {
  AuthOperationProofPage,
  providePasswordConfirmation,
} from '@/runa/auth';
import { providePresentationVisibility } from '@/runa/ui';
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
afterEach(() =>
  localStorage.removeItem('runa.preferences:runa:display:global:runa.locale'),
);
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
    const Child = defineComponent({
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
    });
    const wrapper = mount(PasswordConfirmationHost, {
      slots: { default: () => h(Child) },
    });
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
      await wrapper.findAll('form').at(-1)!.trigger('submit');
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
      document.querySelector<HTMLInputElement>('[role=dialog] input[name=name]')
        ?.value,
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

async function avatarFixture(): Promise<File> {
  const canvas = document.createElement('canvas');
  canvas.width = canvas.height = 32;
  canvas.getContext('2d')!.fillRect(0, 0, 32, 32);
  const blob = await new Promise<Blob>((resolve) =>
    canvas.toBlob((value) => resolve(value!), 'image/png'),
  );
  return new File([blob], 'avatar.png', { type: 'image/png' });
}

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
  transfer.items.add(await avatarFixture());
  (wrapper.get('input[type=file]').element as HTMLInputElement).files =
    transfer.files;
  await wrapper.get('input[type=file]').trigger('change');
  await vi.waitFor(() =>
    expect(document.querySelector('canvas')).not.toBeNull(),
  );
  const crop = new DOMWrapper(document.body)
    .findAll('button')
    .find((button) => button.text() === 'Apply crop')!;
  await vi.waitFor(() => expect(crop.attributes('disabled')).toBeUndefined());
  await crop.trigger('click');
  await vi.waitFor(() => expect(form.dirty.value).toBe(true));
  await vi.waitFor(() =>
    expect(
      wrapper
        .findAll('button')
        .some((button) => button.text() === 'Save avatar'),
    ).toBe(true),
  );
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
      requirement: 'password',
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
    ).toContain('Review the operation effects again');
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
  await vi.waitFor(() => expect(wrapper.findAll('[role=tab]')).toHaveLength(2));
  expect(wrapper.findAll('[role=tab]').map((tab) => tab.text())).toEqual([
    'User',
    'Security',
  ]);
  expect(wrapper.find('input[name=preference_revision]').exists()).toBe(false);
  expect(wrapper.text()).not.toContain('preference_revision');
  await vi.waitFor(() =>
    expect(wrapper.find('[data-user-section=exceptions]').exists()).toBe(true),
  );
  expect(wrapper.text()).not.toContain('exception_version');
  expect(wrapper.text()).not.toContain('policy_revision');
});

it('named account rules open focused editors and status uses authoritative active rules beyond history', async () => {
  const { default: Details } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserDetails.vue');
  const exceptions = section('exceptions', [
    field('exceptions'),
    field('active_exceptions'),
    field('exception_versions'),
    {
      ...field('key', false),
      type: 'dictionary',
      choices: ['session_idle_minutes'],
    },
    { ...field('value', false), type: 'integer' },
    field('reason', false),
    { ...field('expires_at', false), type: 'datetime' },
    { ...field('exception_version', false), type: 'integer' },
    { ...field('policy_revision', false), type: 'integer' },
  ]);
  exceptions.commands = [
    {
      key: 'set',
      title: 'Set or revoke exception',
      mode: 'independent',
      intent: 'action',
      inputs: [
        'key',
        'value',
        'reason',
        'expires_at',
        'exception_version',
        'policy_revision',
      ],
      requirement: 'password',
      action: 'runa.auth.policy.exception',
      destructive: true,
      bulk: false,
      preview_required: false,
      available: true,
    },
  ];
  const controller = page([
    section('lifecycle', [field('active')]),
    exceptions,
  ]);
  const activeRule = {
    public_id: target,
    key: 'session_idle_minutes',
    value: 240,
    reason: 'Approved extended session',
    expires_at: null,
    revoked_at: null,
    policy_revision: 8,
    version: 2,
  };
  let activeRules = [activeRule];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    return response({
      public_id: target,
      revision: '1',
      values: url.pathname.endsWith('/exceptions')
        ? {
            exceptions: JSON.stringify([
              {
                ...activeRule,
                public_id: actor,
                value: 120,
                revoked_at: new Date().toISOString(),
                reason: 'Old historical value',
              },
            ]),
            active_exceptions: JSON.stringify(activeRules),
            key: 'session_idle_minutes',
            value: null,
            reason: null,
            expires_at: null,
            exception_versions: '{"session_idle_minutes":2}',
            exception_version: 0,
            policy_revision: 8,
          }
        : { active: true },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () => h(Details, { controller, form });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(
      wrapper.find('[data-status-rule=session_idle_minutes]').text(),
    ).toContain('240 min'),
  );
  expect(wrapper.find('[data-status-group=custom_rules]').text()).toContain(
    'Approved extended session',
  );
  expect(wrapper.find('[data-status-group=custom_rules]').text()).not.toContain(
    'Old historical value',
  );
  expect(wrapper.findAll('[role=tab]').map((tab) => tab.text())).toEqual([
    'User',
  ]);
  const rules = wrapper.find('[data-user-section=exceptions]');
  expect(rules.find('select').exists()).toBe(false);
  expect(rules.text()).toContain('Indefinitely');
  expect(wrapper.find('[data-status-group=custom_rules]').text()).toContain(
    'Indefinitely',
  );
  expect(rules.text()).not.toContain('password_expiry_days');
  await rules
    .findAll('button')
    .find((button) => button.text() === 'Edit')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(
      document.querySelector('[role=dialog] input[inputmode=numeric]'),
    ).not.toBeNull(),
  );
  expect(document.querySelector('[role=dialog] select')).toBeNull();
  expect(
    document.querySelector('[data-operation-field=expires_at]')?.textContent,
  ).toContain('Leave empty for an indefinite rule.');
  expect(
    document
      .querySelector('[data-operation-field=expires_at] input')
      ?.hasAttribute('required'),
  ).toBe(false);
  expect(
    form.values.value[form.fieldPath('exceptions', 'exception_version')],
  ).toBe(2);
  expect(
    document.querySelector('[role=dialog] input[type=password]'),
  ).toBeNull();
  // Bad bounds are rejected before proof acquisition or a native write.
  form.set(form.fieldPath('exceptions', 'value'), 0);
  document
    .querySelector<HTMLButtonElement>('[role=dialog] button[type=submit]')!
    .click();
  await vi.waitFor(() =>
    expect(document.body.textContent).toContain(
      'Enter a value between 1 and 43200.',
    ),
  );
  expect(
    document.querySelector('[role=dialog] input[type=password]'),
  ).toBeNull();
  const cancel = [
    ...document.querySelectorAll<HTMLButtonElement>('[role=dialog] button'),
  ].find((button) => button.textContent?.trim() === 'Cancel');
  cancel!.click();
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')).toBeNull(),
  );
  expect(form.values.value[form.fieldPath('exceptions', 'value')]).toBeNull();
  await rules
    .findAll('button')
    .find((button) => button.text() === 'Restore global')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')).not.toBeNull(),
  );
  expect(
    document.querySelector('[role=dialog] input[inputmode=numeric]'),
  ).toBeNull();
  expect(form.values.value[form.fieldPath('exceptions', 'reason')]).toBe('');
  [...document.querySelectorAll<HTMLButtonElement>('[role=dialog] button')]
    .find((button) => button.textContent?.trim() === 'Cancel')!
    .click();
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')).toBeNull(),
  );
  activeRules = [];
  await form.refresh();
  await vi.waitFor(() =>
    expect(wrapper.find('[data-status-group=custom_rules]').exists()).toBe(
      false,
    ),
  );
  expect(rules.text()).toContain('Global rule');
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
  let operationForm!: AdminFormController;
  const Child = defineComponent({
    setup() {
      const form = useAdminForm(controller, {
        publicId: ref(target),
        autoRead: false,
      });
      operationForm = form;
      return () => h(Details, { controller, form });
    },
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const host = providePasswordConfirmation();
        providePresentationVisibility(() => !host.active.value);
        return () =>
          h('div', [
            h(Child),
            host.active.value
              ? h(AuthOperationProofPage, {
                  controller: host.active.value,
                  locale: 'en',
                  onConfirmed: () => host.complete(),
                  onCancel: () => host.cancel(),
                })
              : null,
          ]);
      },
    }),
    { attachTo: document.body },
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
  await vi.waitFor(() =>
    expect(operationForm.records.value.security?.revision).toBe('1'),
  );
  submit!.click();
  await vi.waitFor(() =>
    expect(document.querySelector('input[type=password]')).not.toBeNull(),
  );
  expect(wrapper.text()).toContain('Confirm your password');
  expect(
    document.querySelector<HTMLElement>('.dialog-backdrop')!.style.display,
  ).toBe('none');
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
  expect(wrapper.text()).not.toContain('Required security messages');
  expect(wrapper.text()).not.toContain('Optional security messages');
});

it('assignment catalogue searches on the server without duplicating off-page selections', async () => {
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
      requirement: 'password',
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
  await roles.get('input[type=search]').setValue('finance');
  await vi.waitFor(() => expect(searched).toContain('finance'));
  await vi.waitFor(() => expect(roles.text()).toContain('Finance role'));
  expect(roles.text()).not.toContain('Assigned role');
  await roles.get('[data-assignment-rows] input[type=checkbox]').setValue(true);
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
    { ...field('secondary_email_limit'), type: 'integer' },
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
            secondary_email_limit: 5,
            addresses: JSON.stringify([
              {
                public_id: actor,
                email: 'other@example.test',
                verified: false,
              },
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
  const { TextInput, Form } = await import('@/runa/ui');
  const { AuthSecondaryEmailFields } = await import('@/runa/auth');
  const editor = wrapper.getComponent(AuthSecondaryEmailFields);
  expect(editor.text()).toContain('1 / 5');
  expect(document.querySelector('[role=dialog]')).toBeNull();
  expect(editor.getComponent(TextInput).props('modelValue')).toBe(
    'other@example.test',
  );
  expect(document.querySelector('input[name=address_id]')).toBeNull();
  editor.getComponent(TextInput).vm.$emit('update:modelValue', 'invalid');
  await nextTick();
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
  await editor
    .findAll('button')
    .find((button) => button.text() === 'Resend')!
    .trigger('click');
  expect(operation).toHaveBeenLastCalledWith('secondary', 'change', {
    address_id: actor,
    email: 'other@example.test',
  });
  expect(form.values.value[form.fieldPath('secondary', 'email')]).toBe(
    'updated@example.test',
  );
  form.errors.value[form.fieldPath('secondary', 'email')] = [
    'This email address is already in use. Choose another.',
  ];
  savePreference('runa.locale', 'pl');
  await nextTick();
  expect(editor.text()).toContain(
    'Ten adres e-mail jest już zajęty. Wybierz inny.',
  );
  expect(editor.text()).not.toContain('This email address is already in use');
});

it.each([
  ['identifier_conflict', 'secondary.email', 'already in use'],
  ['access_definition_conflict', 'definition.code', 'already in use'],
  ['last_superadmin_required', '', 'superadministrator must remain'],
  ['secondary_limit', '', 'limit has been reached'],
  ['access_role_referenced', '', 'used by other roles'],
  ['recipient_unavailable', '', 'current verified address'],
])(
  'administrative refusal %s survives both envelopes and scopes field errors',
  (reason, field, copy) => {
    for (const payload of [{ reason }, { error: { code: reason } }]) {
      const result = writeFailure(
        new ApiError(409, payload),
        field ? [field, 'unrelated.name'] : [],
      );
      expect(result.message).toContain(copy);
      if (field) expect(result.errors).toEqual({ [field]: [result.message] });
      else expect(result.errors).toBeUndefined();
    }
    expect(
      writeFailure(
        new ApiError(500, { reason, message: 'private failure' }),
        [],
      ).message,
    ).not.toContain(copy);
  },
);

it('unknown refusals never echo payloads or claim nonexistent highlighted fields', () => {
  for (const reason of [
    'private failure',
    'unrecognized_reason',
    'constructor',
    '__proto__',
  ]) {
    const result = writeFailure(
      new ApiError(422, { reason, message: 'secret value' }),
      ['email'],
    );
    expect(result.message).toBe(
      'Invalid operation data. Check the entered values.',
    );
    expect(result.errors).toEqual({});
  }
});

async function compactAssignmentFixture(readonly = false, imports = false) {
  const { PermissionsAssignments } = await import('@/runa/permissions');
  const id = (index: number) =>
    `01900000-0000-7000-8000-${String(index).padStart(12, '0')}`;
  const roles = Array.from({ length: 70 }, (_, index) => ({
    value: id(index + 1),
    label: `Role ${String(index + 1).padStart(3, '0')}`,
  }));
  const permissions = Array.from({ length: 350 }, (_, index) => ({
    value: id(index + 101),
    label: `Permission ${String(index + 1).padStart(3, '0')}`,
  }));
  const source = section('runa.permissions.user_access.assignments', [
    { ...field('roles', readonly), multiple: true, type: 'uuid', lookup: true },
    {
      ...field('permissions', readonly),
      multiple: true,
      type: 'uuid',
      lookup: true,
    },
  ]);
  source.readonly = readonly;
  source.commands = [
    {
      key: 'save',
      title: 'Save access',
      mode: 'atomic',
      intent: 'save',
      inputs: ['roles', 'permissions'],
      requirement: 'password',
      action: 'runa.permissions.access.manage',
      destructive: true,
      bulk: false,
      preview_required: true,
      available: !readonly,
    },
  ];
  if (imports) {
    source.fields.push(
      {
        ...field('source_user', readonly),
        label: 'Source user',
        type: 'uuid',
        lookup: true,
      },
      {
        ...field('preset', readonly),
        label: 'Preset',
        type: 'uuid',
        lookup: true,
      },
      {
        ...field('mode', readonly),
        label: 'Application mode',
        type: 'dictionary',
        choices: ['add', 'replace'],
      },
    );
    source.commands.push(
      ...(['copy', 'preset'] as const).map((key) => ({
        ...source.commands[0]!,
        key,
        title: key,
        mode: 'independent' as const,
        intent: 'action' as const,
        inputs: [key === 'copy' ? 'source_user' : 'preset', 'mode'],
      })),
    );
  }
  const owner = page([source]);
  const requests: { field: string; search: string; limit: number }[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    if (init?.method && init.method !== 'GET')
      throw new Error('Unexpected write.');
    const url = new URL(String(input), window.location.origin);
    if (url.searchParams.get('kind') !== 'options')
      return response({
        public_id: target,
        revision: '1',
        values: {
          roles: [id(61)],
          permissions: [],
          ...(imports ? { source_user: null, preset: null, mode: 'add' } : {}),
        },
      });
    const filters = JSON.parse(url.searchParams.get('filters') ?? '{}') as {
      search?: string;
      selected?: string;
    };
    const key = url.searchParams.get('field')!;
    const limit = Number(url.searchParams.get('limit'));
    requests.push({ field: key, search: filters.search ?? '', limit });
    let items =
      key === 'roles'
        ? roles
        : key === 'source_user'
          ? [{ value: id(900), label: 'Source account' }]
          : key === 'preset'
            ? [{ value: id(901), label: 'Finance preset' }]
            : permissions;
    if (filters.selected) {
      const selected = JSON.parse(filters.selected) as string[];
      items = items.filter((item) => selected.includes(item.value));
    }
    items = items.filter((item) =>
      item.label.toLowerCase().includes((filters.search ?? '').toLowerCase()),
    );
    const cursor = url.searchParams.get('cursor');
    if (cursor) items = items.filter((item) => item.value > cursor);
    const visible = items.slice(0, limit);
    return response({
      field: key,
      items: visible,
      next: items.length > visible.length ? visible.at(-1)!.value : null,
    });
  });
  let form!: AdminFormController;
  const submitted = vi.fn();
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(owner, { publicId: ref(target), autoRead: false });
        return () =>
          h(PermissionsAssignments, {
            page: owner,
            section: source,
            controller: form,
            onSubmit: submitted,
          });
      },
    }),
    { attachTo: document.body, global: { stubs: { Teleport: true } } },
  );
  await vi.waitFor(() =>
    expect(
      wrapper.findAll('[data-assignment-field=roles] [data-assignment-option]'),
    ).toHaveLength(25),
  );
  return { wrapper, form, source, id, requests, submitted };
}

it('compact access editor handles 70 roles and 350 permissions with bounded pages and independent retained searches', async () => {
  const { wrapper, form, source, id, requests, submitted } =
    await compactAssignmentFixture();
  const roles = wrapper.get('[data-assignment-field=roles]');
  const permissions = wrapper.get('[data-assignment-field=permissions]');
  expect(permissions.isVisible()).toBe(false);
  expect(requests.some((request) => request.field === 'permissions')).toBe(
    false,
  );
  await roles.get('input[type=search]').setValue('Role 070');
  await roles.get('input[type=search]').trigger('keydown', { key: 'Enter' });
  await vi.waitFor(() =>
    expect(roles.findAll('[data-assignment-option]')).toHaveLength(1),
  );
  expect(submitted).not.toHaveBeenCalled();
  await roles.get('[data-assignment-rows] input').setValue(true);
  expect(form.values.value[form.fieldPath(source.key, 'roles')]).toEqual([
    id(61),
    id(70),
  ]);
  await wrapper.get('[data-access-kind=permissions]').trigger('click');
  await vi.waitFor(() =>
    expect(permissions.findAll('[data-assignment-option]')).toHaveLength(25),
  );
  await permissions.get('[aria-label="Next page"]').trigger('click');
  await vi.waitFor(() =>
    expect(permissions.text()).toContain('Permission 026'),
  );
  await permissions.get('[data-assignment-rows] input').setValue(true);
  await permissions.get('input[type=search]').setValue('Permission 350');
  await vi.waitFor(() =>
    expect(permissions.findAll('[data-assignment-option]')).toHaveLength(1),
  );
  await permissions.get('[data-assignment-rows] input').setValue(true);
  expect(form.values.value[form.fieldPath(source.key, 'permissions')]).toEqual([
    id(126),
    id(450),
  ]);
  await wrapper.get('[data-access-kind=roles]').trigger('click');
  expect(
    (roles.get('input[type=search]').element as HTMLInputElement).value,
  ).toBe('Role 070');
  await roles.get('input[type=search]').setValue('');
  await roles
    .findAll('button')
    .find((button) => button.text() === 'Assigned')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(roles.findAll('[data-assignment-option]')).toHaveLength(2),
  );
  expect(roles.text()).toContain('Role 061');
  expect(roles.text()).toContain('Role 070');
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Revert changes')!
    .trigger('click');
  await vi.waitFor(() => expect(form.dirty.value).toBe(false));
  expect(form.values.value[form.fieldPath(source.key, 'roles')]).toEqual([
    id(61),
  ]);
  expect(form.values.value[form.fieldPath(source.key, 'permissions')]).toEqual(
    [],
  );
  expect(requests.every((request) => request.limit <= 100)).toBe(true);
  expect(
    requests
      .filter((request) => request.search)
      .every((request) => request.limit === 25),
  ).toBe(true);
});

it('compact access editor keeps readonly catalogues searchable without permitting assignment edits', async () => {
  const { wrapper, form, source, id, submitted } =
    await compactAssignmentFixture(true);
  const roles = wrapper.get('[data-assignment-field=roles]');
  expect(
    roles.get('[data-assignment-rows] input').attributes('disabled'),
  ).toBeDefined();
  await roles.get('input[type=search]').setValue('Role 061');
  await vi.waitFor(() => expect(roles.text()).toContain('Role 061'));
  await roles.get('input[type=search]').trigger('keydown', { key: 'Enter' });
  expect(submitted).not.toHaveBeenCalled();
  expect(form.values.value[form.fieldPath(source.key, 'roles')]).toEqual([
    id(61),
  ]);
  expect(
    wrapper
      .findAll('button')
      .some((button) => button.text() === 'Review changes'),
  ).toBe(false);
});

it('compact access editor can remove unavailable assignments and bulk selection retains off-page choices', async () => {
  const { wrapper, form, source, id } = await compactAssignmentFixture();
  const roles = wrapper.get('[data-assignment-field=roles]');
  await roles.get('input[aria-label="Select visible"]').setValue(true);
  const chosen = form.values.value[
    form.fieldPath(source.key, 'roles')
  ] as string[];
  expect(chosen).toHaveLength(26);
  expect(chosen).toContain(id(61));
  await roles.get('[aria-label="Next page"]').trigger('click');
  await vi.waitFor(() => expect(roles.text()).toContain('Role 026'));
  expect(
    (roles.get('[data-assignment-rows] input').element as HTMLInputElement)
      .checked,
  ).toBe(false);
  form.set(form.fieldPath(source.key, 'roles'), [id(61), id(999)]);
  await roles
    .findAll('button')
    .find((button) => button.text() === 'Assigned')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(roles.findAll('[data-assignment-option]')).toHaveLength(2),
  );
  const unavailable = roles.get(`[data-assignment-option="${id(999)}"] input`);
  expect((unavailable.element as HTMLInputElement).checked).toBe(true);
  await unavailable.setValue(false);
  await vi.waitFor(() =>
    expect(roles.text()).not.toContain('Unavailable assignment'),
  );
  expect(form.values.value[form.fieldPath(source.key, 'roles')]).toEqual([
    id(61),
  ]);
});

it('groups account actions consistently regardless of input order and preserves unknown actions', async () => {
  const { groupAuthActions } =
    await import('../../../../src/runa/modules/auth/frontend/bulkGroups');
  const commands = [
    'delete',
    'revoke',
    'factor_reset',
    'invite',
    'set_security_lock',
    'activate',
    'schedule_activation',
    'custom',
    'grant_superadmin',
    'revoke_superadmin',
  ];
  const single = groupAuthActions(commands, (command) => command);
  const bulk = groupAuthActions([...commands].reverse(), (command) => command);
  expect(single).toEqual(bulk);
  expect(single.map((group) => group.key)).toEqual([
    'activity',
    'locks',
    'email',
    'credentials',
    'sessions',
    'deletion',
    'other',
    'authority',
  ]);
});

it('places account status facts in thematic groups without dropping unknown projected facts', async () => {
  const { default: Status } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserStatus.vue');
  const lifecycle = section('lifecycle', [
    { ...field('deleted'), type: 'boolean' },
    { ...field('invitation_pending'), type: 'boolean' },
    { ...field('password_set'), type: 'boolean' },
    { ...field('active'), type: 'boolean' },
    { ...field('verified'), type: 'boolean' },
    { ...field('session_count'), type: 'integer' },
    field('custom_fact'),
  ]);
  const controller = page([lifecycle]);
  vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      public_id: target,
      revision: '1',
      values: {
        active: true,
        deleted: false,
        verified: false,
        password_set: true,
        session_count: 1,
        invitation_pending: false,
        custom_fact: 'Extra',
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
          h(Status, {
            page: controller,
            controller: form,
            section: lifecycle,
            sources: [lifecycle],
          });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Extra'));
  expect(
    wrapper
      .findAll('[data-status-group]')
      .map((group) => group.attributes('data-status-group')),
  ).toEqual([
    'lifecycle',
    'email',
    'credentials',
    'sessions',
    'invitations',
    'other',
  ]);
  expect(
    wrapper.get('[data-status-group=lifecycle]').text().indexOf('active'),
  ).toBeLessThan(
    wrapper.get('[data-status-group=lifecycle]').text().indexOf('deleted'),
  );
});

it('offers explicit administrative verification for pending addresses without overwriting email drafts', async () => {
  const { default: Emails } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminEmails.vue');
  const primary = section('primary_email', [
    field('current_email'),
    { ...field('verified'), type: 'boolean' },
  ]);
  const secondary = section('secondary', [
    field('addresses'),
    { ...field('secondary_email_limit'), type: 'integer' },
    field('address_id', false),
    field('email', false),
  ]);
  for (const source of [primary, secondary])
    source.commands = [
      {
        key: 'verify',
        title: 'Verify',
        mode: 'independent',
        intent: 'action',
        inputs: source === primary ? [] : ['address_id'],
        requirement: 'password',
        action: 'runa.auth.email.admin_bypass',
        destructive: true,
        bulk: false,
        preview_required: false,
        available: true,
      },
    ];
  const controller = page([primary, secondary]);
  const sources = ref([primary, secondary]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    response({
      public_id: target,
      revision: '1',
      values: new URL(String(input), window.location.origin).pathname.endsWith(
        '/primary_email',
      )
        ? { current_email: 'primary@example.test', verified: false }
        : {
            addresses: JSON.stringify([
              {
                public_id: target,
                email: 'pending@example.test',
                verified: false,
              },
              {
                public_id: actor,
                email: 'verified@example.test',
                verified: true,
              },
            ]),
            secondary_email_limit: 5,
            address_id: null,
            email: 'unsaved@example.test',
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
          h(Emails, {
            page: controller,
            controller: form,
            section: primary,
            sources: sources.value,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('pending@example.test'),
  );
  const component = wrapper.findComponent(Emails);
  await wrapper
    .get('button[aria-label="Verify as administrator: pending@example.test"]')
    .trigger('click');
  expect(component.emitted('operation')?.[0]).toEqual([
    'secondary',
    'verify',
    { address_id: target },
  ]);
  expect(
    wrapper
      .find(
        'button[aria-label="Verify as administrator: verified@example.test"]',
      )
      .exists(),
  ).toBe(false);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Verify as administrator')!
    .trigger('click');
  expect(component.emitted('operation')?.[1]).toEqual([
    'primary_email',
    'verify',
    {},
  ]);
  secondary.commands[0]!.available = false;
  primary.commands[0]!.available = false;
  sources.value = [{ ...primary }, { ...secondary }];
  await nextTick();
  expect(
    wrapper.find('button[aria-label^="Verify as administrator:"]').exists(),
  ).toBe(false);
});

it('explains session and device revocation before confirming an existing primary address', async () => {
  const { default: Details } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserDetails.vue');
  const primary = section('primary_email', [
    field('current_email'),
    { ...field('verified'), type: 'boolean' },
  ]);
  primary.commands = [
    {
      key: 'verify',
      title: 'Verify primary email',
      mode: 'independent',
      intent: 'action',
      inputs: [],
      requirement: 'password',
      action: 'runa.auth.email.admin_bypass',
      destructive: true,
      bulk: false,
      preview_required: false,
      available: true,
    },
  ];
  const controller = page([primary]);
  vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      public_id: target,
      revision: '1',
      values: { current_email: 'pending@example.test', verified: false },
    }),
  );
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
    expect(wrapper.text()).toContain('Verify as administrator'),
  );
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Verify as administrator')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(document.querySelector('[role=dialog]')?.textContent).toContain(
      'signs the user out of all sessions and revokes trusted devices',
    ),
  );
  expect(document.querySelector('input[type=password]')).toBeNull();
});

it.each(['sessions', 'trust'])(
  'security table %s supports sorting, local filters and separately authorized export',
  async (kind) => {
    const { default: UserSection } =
      await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserSection.vue');
    const { TextInput } = await import('@/runa/ui');
    const key = kind === 'sessions' ? 'sessions' : 'devices';
    const spec = section(kind, [field(key)]);
    spec.export_allowed = true;
    const controller = page([spec]);
    const entries = [
      {
        public_id: target,
        authenticated_at: '2026-01-02T10:00:00Z',
        activity_at: '2026-01-02T10:00:00Z',
        created_at: '2026-01-02T10:00:00Z',
        expires_at: '2027-01-02T10:00:00Z',
        remembered: false,
        browser: 'Zulu',
        platform: 'Linux',
        current: false,
      },
      {
        public_id: '01900000-0000-7000-8000-000000000003',
        authenticated_at: '2025-12-20T10:00:00Z',
        activity_at: '2025-12-20T10:00:00Z',
        created_at: '2025-12-20T10:00:00Z',
        expires_at: '2027-01-01T10:00:00Z',
        remembered: true,
        browser: 'Alpha',
        platform: 'Linux',
        current: true,
      },
    ];
    const reads: URL[] = [];
    vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
      reads.push(new URL(String(input), window.location.origin));
      return response({
        public_id: target,
        revision: '1',
        values: { [key]: JSON.stringify(entries) },
      });
    });
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
              section: spec,
              controller: form,
            });
        },
      }),
      { attachTo: document.body },
    );
    await vi.waitFor(() => expect(wrapper.findAll('tbody tr')).toHaveLength(2));
    const sortKey = kind === 'sessions' ? 'authenticated_at' : 'device';
    const header = wrapper.get(`th[data-column-key=${sortKey}] button`);
    await header.trigger('click');
    await vi.waitFor(() =>
      expect(
        wrapper.get(`th[data-column-key=${sortKey}]`).attributes('aria-sort'),
      ).toBe('ascending'),
    );
    const firstText = wrapper.findAll('tbody tr')[0]!.text();
    expect(firstText).toContain(kind === 'sessions' ? '20' : 'Alpha');
    const filter = wrapper
      .findAllComponents(TextInput)
      .find(
        (input) =>
          input.props('label') ===
          (kind === 'sessions' ? 'Signed in' : 'Device'),
      )!;
    expect(filter).toBeTruthy();
    await filter.get('input').setValue(kind === 'sessions' ? '2025' : 'alpha');
    await vi.waitFor(() => expect(wrapper.findAll('tbody tr')).toHaveLength(1));
    expect(
      wrapper
        .findAllComponents(TextInput)
        .some((input) => input.props('label') === 'Search'),
    ).toBe(true);
    await wrapper.get('button[aria-label=CSV]').trigger('click');
    await vi.waitFor(() =>
      expect(reads.some((url) => url.searchParams.get('export') === '1')).toBe(
        true,
      ),
    );
  },
);

it.each(['copy', 'preset'] as const)(
  'selected-user access %s uses searchable guarded choices and the shared review flow',
  async (method) => {
    const { wrapper, form, source, id, requests, submitted } =
      await compactAssignmentFixture(false, true);
    const fieldKey = method === 'copy' ? 'source_user' : 'preset';
    const tools = wrapper.get('[data-access-import]');
    await tools
      .findAll('button')
      .find(
        (button) =>
          button.text() ===
          (method === 'copy' ? 'Copy from user' : 'Use preset'),
      )!
      .trigger('click');
    expect(tools.get('[role=dialog]').attributes('aria-modal')).toBe('true');
    const select = tools
      .findAllComponents(Select)
      .find(
        (select: { props(): { label: string } }) =>
          select.props().label ===
          (method === 'copy' ? 'Source user' : 'Preset'),
      )!;
    const modeSelect = tools
      .findAllComponents(Select)
      .find(
        (entry: { props(): { label: string } }) =>
          entry.props().label === 'Application mode',
      )!;
    expect(modeSelect.props('clearable')).toBe(false);
    expect(select.props('clearable')).toBe(true);
    const provider = select.props('provider') as SelectionProvider;
    const result = await provider({
      cursor: null,
      query: method === 'copy' ? 'Source' : 'Finance',
      limit: 25,
      signal: new AbortController().signal,
    });
    expect(result.options).toEqual([
      {
        value: id(method === 'copy' ? 900 : 901),
        label: method === 'copy' ? 'Source account' : 'Finance preset',
      },
    ]);
    expect(
      requests.some(
        (request) =>
          request.field === fieldKey &&
          request.search === (method === 'copy' ? 'Source' : 'Finance'),
      ),
    ).toBe(true);
    select.vm.$emit('update:modelValue', id(method === 'copy' ? 900 : 901));
    await nextTick();
    expect(form.values.value[form.fieldPath(source.key, fieldKey)]).toBe(
      id(method === 'copy' ? 900 : 901),
    );
    await tools
      .findAll('button')
      .find((button) => button.text() === 'Apply')!
      .trigger('click');
    expect(tools.find('[role=dialog]').exists()).toBe(false);
    expect(submitted).toHaveBeenCalledWith(source.key, method, []);
    expect(form.values.value[form.fieldPath(source.key, 'roles')]).toEqual([
      id(61),
    ]);
    expect(
      form.values.value[form.fieldPath(source.key, 'permissions')],
    ).toEqual([]);
  },
);

it('selected-user copy and preset tools preserve dirty assignments and omit readonly capabilities', async () => {
  const { wrapper, form, source, id } = await compactAssignmentFixture(
    false,
    true,
  );
  form.set(form.fieldPath(source.key, 'roles'), [id(1)]);
  await nextTick();
  expect(wrapper.get('[data-access-import]').text()).toContain(
    'Save or revert',
  );
  for (const button of wrapper.get('[data-access-import]').findAll('button'))
    expect(button.attributes('disabled')).toBeDefined();
  wrapper.unmount();
  const readonly = await compactAssignmentFixture(true, true);
  expect(readonly.wrapper.find('[data-access-import]').exists()).toBe(false);
});

it('selected-user import dialog cancellation clears only import parameters and keeps memberships', async () => {
  const { wrapper, form, source, id, submitted } =
    await compactAssignmentFixture(false, true);
  const tools = wrapper.get('[data-access-import]');
  await tools
    .findAll('button')
    .find((button) => button.text() === 'Copy from user')!
    .trigger('click');
  form.set(form.fieldPath(source.key, 'source_user'), id(900));
  form.set(form.fieldPath(source.key, 'mode'), 'replace');
  await nextTick();
  await tools
    .get('[role=dialog]')
    .findAll('button')
    .find((button) => button.text() === 'Cancel')!
    .trigger('click');
  expect(tools.find('[role=dialog]').exists()).toBe(false);
  expect(
    form.values.value[form.fieldPath(source.key, 'source_user')],
  ).toBeNull();
  expect(form.values.value[form.fieldPath(source.key, 'mode')]).toBe('add');
  expect(form.values.value[form.fieldPath(source.key, 'roles')]).toEqual([
    id(61),
  ]);
  expect(form.dirty.value).toBe(false);
  expect(submitted).not.toHaveBeenCalled();
});

it('protected authority uses a grouped individual action without rendering its checkbox card', async () => {
  const { default: Details } =
    await import('../../../../src/runa/modules/auth/frontend/AuthAdminUserDetails.vue');
  const authority = section('runa.permissions.user_authority.authority', [
    { ...field('grant', false), type: 'boolean' },
  ]);
  authority.commands = [
    {
      key: 'change',
      title: 'Change authority',
      inputs: ['grant'],
      mode: 'independent',
      intent: 'action',
      requirement: 'password',
      action: 'runa.permissions.authority.manage',
      destructive: true,
      bulk: false,
      preview_required: true,
      available: true,
    },
  ];
  const controller = page([
    section('profile', [field('name', false)]),
    authority,
  ]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    response({
      public_id: target,
      revision: '0',
      values: String(input).includes('user_authority')
        ? { grant: false }
        : { name: 'Target' },
    }),
  );
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          publicId: ref(target),
          autoRead: false,
        });
        return () => h(Details, { controller, form });
      },
    }),
    { attachTo: document.body },
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain('Super administrator'),
  );
  expect(
    wrapper
      .find('[data-user-section="runa.permissions.user_authority.authority"]')
      .exists(),
  ).toBe(false);
  expect(wrapper.find('input[type=checkbox]').exists()).toBe(false);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Super administrator')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(form.values.value[authority.key + '.grant']).toBe(true),
  );
  const select = wrapper
    .findAllComponents(Select)
    .find((select) =>
      (select.props() as { options: { label: string }[] }).options.some(
        (option: { label: string }) =>
          option.label === 'Revoke super administrator',
      ),
    )!;
  select.vm.$emit('update:modelValue', '1');
  await vi.waitFor(() =>
    expect(form.values.value[authority.key + '.grant']).toBe(false),
  );
  expect(
    document.querySelector('[role=dialog] input[type=checkbox]'),
  ).toBeNull();
});

it('protected authority bulk reuses omitted-account warnings and refreshes grant versus revoke impact', async () => {
  const bulk = section('runa.permissions.user_authority_bulk.bulk_authority', [
    { ...field('public_ids', false), type: 'uuid', multiple: true },
    field('summary'),
  ]);
  bulk.commands = ['grant_superadmin', 'revoke_superadmin'].map((key) => ({
    key,
    title: key,
    inputs: ['public_ids'],
    mode: 'independent',
    intent: 'action',
    requirement: 'none',
    action: null,
    destructive: true,
    bulk: true,
    preview_required: true,
    available: true,
  }));
  const controller = page([bulk]);
  const previews: string[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input, init) => {
    if (String(input).endsWith('/preview')) {
      const body = JSON.parse(String(init?.body)) as {
        command: string;
        values: { public_ids: string[] };
      };
      previews.push(body.command);
      expect(body.values.public_ids).toEqual([actor, target]);
      return response({
        fingerprint: 'a'.repeat(64),
        changes: {
          summary: {
            accounts: 2,
            affected_accounts: 1,
            unchanged_accounts: 1,
            skipped_accounts: 0,
            ineligible_accounts: 0,
          },
        },
        requirement: 'none',
        every_action: true,
      });
    }
    return response({
      public_id: null,
      revision: '0',
      values: { public_ids: [actor, target], summary: null },
    });
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller);
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
    expect(wrapper.get('[data-bulk-ineligible]').text()).toContain(
      'Accounts that are already superadministrators will be skipped.',
    ),
  );
  expect(wrapper.get('[data-bulk-ineligible]').text()).toContain(
    'Skipped accounts: 1',
  );
  expect(wrapper.text()).not.toContain(actor);
  expect(wrapper.text()).not.toContain(target);
  expect(wrapper.find('[data-bulk-skipped]').exists()).toBe(false);
  wrapper
    .getComponent(Select)
    .vm.$emit('update:modelValue', 'revoke_superadmin');
  await vi.waitFor(() =>
    expect(previews).toEqual(['grant_superadmin', 'revoke_superadmin']),
  );
  await vi.waitFor(() =>
    expect(wrapper.get('[data-bulk-ineligible]').text()).toContain(
      'Accounts that are not superadministrators will be skipped.',
    ),
  );
});

it('Auth user dialogs end creation and detail reads on close and isolate the next bulk editor', async () => {
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
  const profile = section('profile', [field('name', false)]);
  const bulk = section('bulk_sessions', [
    { ...field('public_ids', false), type: 'uuid', multiple: true },
    field('summary'),
  ]);
  bulk.commands = [
    {
      key: 'revoke',
      title: 'Revoke sessions',
      inputs: ['public_ids'],
      mode: 'independent',
      requirement: 'none',
      destructive: true,
      preview_required: true,
      bulk: false,
      action: null,
      available: true,
      intent: 'action',
    },
  ];
  const controller = page([
    section('directory', [field('display_name')], 'table'),
    creation,
    profile,
    bulk,
  ]);
  const reads: URL[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    reads.push(url);
    if (url.pathname.endsWith('/directory'))
      return response({
        items: [
          {
            public_id: target,
            revision: '1',
            values: { display_name: 'Dialog target' },
          },
        ],
        next: null,
        total: 1,
      });
    const selected = url.searchParams.get('public_id');
    return response({
      public_id: selected,
      revision: '1',
      values: url.pathname.endsWith('/creation')
        ? { email: null }
        : url.pathname.endsWith('/profile')
          ? { name: 'Existing name' }
          : { public_ids: [], summary: null },
    });
  });
  let form!: AdminFormController;
  let leave = false;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, {
          autoRead: false,
          confirmLeave: async () => leave,
        });
        return () => h(AuthAdminUsers, { controller, form });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Dialog target'));
  const click = async (label: string) =>
    wrapper
      .findAll('button')
      .find((button) => button.text() === label)!
      .trigger('click');
  const close = async () => {
    const dialog = document.querySelector('[role=dialog]')!;
    await new DOMWrapper(dialog)
      .get('button[aria-label="Close dialog"]')
      .trigger('click');
  };
  await click('Create account');
  await vi.waitFor(() => expect(form.records.value.creation).toBeDefined());
  form.set('creation.email', 'draft@example.test');
  await close();
  expect(form.values.value['creation.email']).toBe('draft@example.test');
  leave = true;
  await close();
  await vi.waitFor(() => expect(form.records.value.creation).toBeUndefined());
  const stopped = reads.length;
  await form.refresh();
  expect(reads).toHaveLength(stopped);
  await click('Details');
  await vi.waitFor(() =>
    expect(form.records.value.profile?.public_id).toBe(target),
  );
  expect(
    reads.filter((url) => url.pathname.endsWith('/creation')),
  ).toHaveLength(1);
  await close();
  await vi.waitFor(() => expect(form.records.value.profile).toBeUndefined());
  await wrapper.get('tbody input[type=checkbox]').setValue(true);
  await wrapper
    .get('[data-bulk-command="bulk_sessions:revoke"]')
    .trigger('click');
  await vi.waitFor(() =>
    expect(form.values.value['bulk_sessions.public_ids']).toEqual([target]),
  );
  const beforeRefresh = reads.length;
  await form.refresh();
  expect(reads.slice(beforeRefresh).map((url) => url.pathname)).toEqual([
    '/api/admin/runa.auth/users/sections/bulk_sessions',
  ]);
  expect(
    reads
      .slice(beforeRefresh)
      .every((url) => !url.searchParams.has('public_id')),
  ).toBe(true);
});
