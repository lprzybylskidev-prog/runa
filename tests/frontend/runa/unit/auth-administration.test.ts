/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { beforeEach, afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
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
      .find((button) => button.text().startsWith('Revoke sessions'))!;
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
