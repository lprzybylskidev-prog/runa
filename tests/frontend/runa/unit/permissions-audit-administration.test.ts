/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  PermissionsAdminDefinition,
  PermissionsAdminDefinitions,
  PermissionsAdminEffective,
} from '@/runa/permissions';
import { AuditAdminDetail, AuditAdminHistory } from '@/runa/audit';
import { useAdminForm } from '@/runa/admin';
import type {
  AdminFieldData,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminSectionData,
} from '@/runa/admin';
enableAutoUnmount(afterEach);
const id = '01900000-0000-7000-8000-000000000001';
const other = '01900000-0000-7000-8000-000000000002';
const field = (key: string, readonly = true): AdminFieldData => ({
  key,
  label: key,
  type: 'text',
  readonly,
  sensitive: false,
  filterable: false,
  sortable: false,
  lookup: false,
  required: false,
  max_length: null,
  choices: [],
  multiple: false,
  component: null,
});
const section = (
  key: string,
  fields: AdminFieldData[],
  view: 'record' | 'table' = 'record',
): AdminSectionData => ({
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
});
function page(
  owner: string,
  key: string,
  sections: AdminSectionData[],
): AdminPageController {
  return {
    page: shallowRef<AdminPageData>({
      schema_version: 1,
      owner,
      key,
      title: key,
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
afterEach(() => {
  vi.restoreAllMocks();
  window.history.replaceState(null, '', '/');
});
it('Permissions Admin source definitions stay read only and immutable codes cannot be edited', async () => {
  const descriptor = section('editor', [
    field('code', false),
    field('name_en', false),
    field('module'),
    { ...field('system'), type: 'boolean' },
  ]);
  const controller = page('runa.permissions', 'roles', [descriptor]);
  vi.spyOn(window, 'fetch').mockImplementation(async () =>
    response({
      public_id: id,
      revision: '1',
      values: {
        code: 'runa.auth.reader',
        name_en: 'Reader',
        module: 'runa.auth',
        system: false,
      },
    }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, { autoRead: false });
        return () =>
          h(PermissionsAdminDefinition, {
            page: controller,
            section: descriptor,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain(
      'Source and system definitions are read-only.',
    ),
  );
  expect(wrapper.find('input:not([readonly]):not([disabled])').exists()).toBe(
    false,
  );
  expect(
    wrapper
      .findAll('button')
      .filter((button) => button.text().includes('Save')),
  ).toHaveLength(0);
});
it('Permissions Admin definition browser preserves dirty drafts during record selection', async () => {
  const descriptor = section('editor', [field('name_en', false)]);
  const controller = page('runa.permissions', 'roles', [
    section('catalogue', [field('code'), field('name_en')], 'table'),
    descriptor,
  ]);
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.pathname.endsWith('/catalogue'))
      return response({
        items: [
          {
            public_id: other,
            revision: '1',
            values: { code: 'custom.other', name_en: 'Other role' },
          },
        ],
        next: null,
        total: null,
      });
    return response({
      public_id: id,
      revision: '1',
      values: { name_en: 'Current role' },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, { confirmLeave: async () => false });
        return () => h(PermissionsAdminDefinitions, { controller, form });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Other role'));
  await vi.waitFor(() => expect(form.records.value.editor).toBeDefined());
  form.set(form.fieldPath('editor', 'name_en'), 'Unsaved role');
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Open record')!
    .trigger('click');
  expect(form.values.value[form.fieldPath('editor', 'name_en')]).toBe(
    'Unsaved role',
  );
  expect(form.records.value.editor!.public_id).toBe(id);
});
it('Audit Admin detail exposes escaped immutable values and bounded entity history links', async () => {
  const descriptor = section('detail', [
    field('action'),
    field('changes'),
    field('entities'),
  ]);
  const controller = page('runa.audit', 'history', [descriptor]);
  vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      public_id: id,
      revision: id,
      values: {
        action: 'example.changed',
        changes: '<script>privateMarkup()</script>',
        entities: JSON.stringify([
          { module: 'runa.auth', type: 'user', public_id: other },
        ]),
      },
    }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, { autoRead: false });
        return () =>
          h(AuditAdminDetail, {
            page: controller,
            section: descriptor,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.find('a').exists()).toBe(true));
  const link = new URL(
    wrapper.get('a').attributes('href')!,
    window.location.origin,
  );
  expect(link.pathname).toBe('/admin/runa.audit/history');
  expect(link.searchParams.get('entity_public_id')).toBe(other);
  expect(wrapper.find('script').exists()).toBe(false);
  expect(
    wrapper
      .findAll('button')
      .filter((button) => /Save|Delete|Undo/.test(button.text())),
  ).toHaveLength(0);
});
it('Audit Admin entity history links initialize the exact bounded filter query', async () => {
  window.history.replaceState(
    null,
    '',
    `/admin/runa.audit/history?entity_module=runa.auth&entity_type=user&entity_public_id=${other}`,
  );
  const descriptor = section(
    'catalogue',
    ['entity_module', 'entity_type', 'entity_public_id'].map((key) => ({
      ...field(key),
      filterable: true,
    })),
    'table',
  );
  const controller = page('runa.audit', 'history', [descriptor]);
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(response({ items: [], next: null, total: null }));
  mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, { autoRead: false });
        return () => h(AuditAdminHistory, { controller, form });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(
      fetch.mock.calls.some(([input]) => {
        const filters = new URL(
          String(input),
          window.location.origin,
        ).searchParams.get('filters');
        return filters && JSON.parse(filters).entity_public_id === other;
      }),
    ).toBe(true),
  );
});
it('Permissions Admin effective cursor preserves the selected account on older reads', async () => {
  const descriptor = section('effective', [field('entries'), field('next')]);
  const controller = page('runa.permissions', 'access', [descriptor]);
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    return response({
      public_id: other,
      revision: 'r1',
      values: {
        entries: JSON.stringify([
          {
            code: url.searchParams.has('cursor')
              ? 'app.example.write'
              : 'app.example.read',
            direct: true,
            roles: [],
            roles_truncated: false,
            available: true,
            effective: true,
          },
        ]),
        next: url.searchParams.has('cursor') ? null : 'app.example.read',
      },
    });
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, {
          autoRead: false,
          publicId: ref(other),
        });
        return () =>
          h(PermissionsAdminEffective, {
            page: controller,
            section: descriptor,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('app.example.read'));
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Next permissions')!
    .trigger('click');
  await vi.waitFor(() => expect(wrapper.text()).toContain('app.example.write'));
  const last = new URL(
    String(fetch.mock.calls.at(-1)![0]),
    window.location.origin,
  );
  expect(last.searchParams.get('public_id')).toBe(other);
  expect(last.searchParams.get('cursor')).toBe('app.example.read');
});

it('Audit Admin payload continuation keeps the immutable event and exposes every fragment', async () => {
  const descriptor = section(
    'detail',
    ['changes', 'entities', 'payload_part', 'payload_next'].map((key) =>
      field(key),
    ),
  );
  const controller = page('runa.audit', 'history', [descriptor]);
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    const next = url.searchParams.has('cursor');
    return response({
      public_id: id,
      revision: id,
      values: {
        changes: next ? 'second fragment' : 'first fragment',
        entities: '[]',
        payload_part: next ? '2 / 2' : '1 / 2',
        payload_next: next ? null : '1',
      },
    });
  });
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, {
          autoRead: false,
          publicId: ref(id),
        });
        return () =>
          h(AuditAdminDetail, {
            page: controller,
            section: descriptor,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('first fragment'));
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Next payload part')!
    .trigger('click');
  await vi.waitFor(() => expect(wrapper.text()).toContain('second fragment'));
  const last = new URL(
    String(fetch.mock.calls.at(-1)![0]),
    window.location.origin,
  );
  expect(last.searchParams.get('public_id')).toBe(id);
  expect(last.searchParams.get('cursor')).toBe('1');
  expect(
    wrapper
      .findAll('button')
      .find((button) => button.text() === 'Next payload part')!
      .attributes('disabled'),
  ).toBeDefined();
});
