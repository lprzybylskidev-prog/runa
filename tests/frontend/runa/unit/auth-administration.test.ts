/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import { AuthAdminUsers, AuthAdminSection } from '@/runa/auth';
import { useAdminForm } from '@/runa/admin';
import type {
  AdminFieldData,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminSectionData,
} from '@/runa/admin';
enableAutoUnmount(afterEach);
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
    .find((button) => button.text() === 'Open account');
  expect(open).toBeDefined();
  await open!.trigger('click');
  await vi.waitFor(() =>
    expect(form.records.value.profile?.public_id).toBe(target),
  );
  expect(form.values.value['profile.name']).toBe('Selected target');
  expect(wrapper.text()).toContain(`Selected account: ${target}`);
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
