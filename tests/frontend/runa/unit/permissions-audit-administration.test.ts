/* eslint-disable vue/one-component-per-file -- Independent administrative presentation fixtures. */
import { beforeEach, afterEach, expect, it, vi } from '@runa/testing/vitest';
import { savePreference } from '../../../../src/runa/resources/js/lib/preferences';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { computed, defineComponent, h, ref, shallowRef } from 'vue';
import {
  PermissionsAdminDefinition,
  PermissionsAdminDefinitions,
} from '@/runa/permissions';
import { AuditAdminDetail, AuditAdminHistory } from '@/runa/audit';
import { AdminSection, AdminRecordBrowser, useAdminForm } from '@/runa/admin';
import { ValuePreview, DateTimeRangeInput, Table, Select } from '@/runa/ui';
import type { TableColumn, TableQuery, SelectOption } from '@/runa/ui';
import type {
  AdminFieldData,
  AdminRecord,
  AdminFormController,
  AdminPageController,
  AdminPageData,
  AdminSectionData,
} from '@/runa/admin';
enableAutoUnmount(afterEach);
afterEach(() => savePreference('runa.locale', 'en'));
beforeEach(() => localStorage.setItem('runa.locale', 'en'));
afterEach(() => localStorage.removeItem('runa.locale'));
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
it.each([
  ['runa.audit', 'pl', 'success', 'sukces'],
  ['runa.audit', 'en', 'success', 'success'],
  ['runa.auth', 'pl', 'current', 'bieżące'],
  ['runa.auth', 'en', 'current', 'current'],
  ['runa.permissions', 'pl', 'replace', 'zastąp'],
  ['runa.permissions', 'en', 'replace', 'replace'],
])(
  '%s %s renders dictionary cells like filter choices while preserving canonical values',
  async (owner, locale, raw, label) => {
    savePreference('runa.locale', locale!);
    const controller = page(owner!, 'history', [
      section(
        'catalogue',
        [
          {
            ...field('status'),
            type: 'dictionary',
            filterable: true,
            choices: [raw!],
          },
          field('module'),
        ],
        'table',
      ),
    ]);
    const record: AdminRecord = {
      public_id: id,
      revision: '1',
      values: { status: raw!, module: 'success' },
    };
    vi.spyOn(window, 'fetch').mockResolvedValue(
      response({ items: [record], next: null, total: 1 }),
    );
    const wrapper = mount(
      defineComponent({
        setup() {
          const form = useAdminForm(controller, { autoRead: false });
          return () => h(AdminRecordBrowser, { controller, form });
        },
      }),
    );
    await vi.waitFor(() => expect(wrapper.findAll('tbody tr')).toHaveLength(1));
    const cells = wrapper.get('tbody tr').findAll('td');
    expect(cells[0]!.text()).toBe(label);
    expect(cells[1]!.text()).toBe('success');
    const filter = wrapper
      .findAllComponents(Select)
      .map(
        (select) =>
          select.props() as { label: string; options: readonly SelectOption[] },
      )
      .find((select) => select.label === 'status')!;
    expect(filter.options).toContainEqual({ value: raw, label });
    const { columns } = wrapper.getComponent(Table).props() as {
      columns: readonly TableColumn<AdminRecord>[];
    };
    expect(columns[0]!.value(record)).toBe(raw);
  },
);
it.each(['runa.auth', 'runa.permissions', 'runa.audit'])(
  '%s maps datetime collection filters to the shared picker',
  async (owner) => {
    const controller = page(owner, 'history', [
      section(
        'catalogue',
        [
          { ...field('from'), type: 'datetime', filterable: true },
          { ...field('until'), type: 'datetime', filterable: true },
        ],
        'table',
      ),
    ]);
    vi.spyOn(window, 'fetch').mockResolvedValue(
      response({ items: [], next: null, total: null }),
    );
    const wrapper = mount(
      defineComponent({
        setup() {
          const form = useAdminForm(controller, { autoRead: false });
          return () => h(AdminRecordBrowser, { controller, form });
        },
      }),
    );
    await vi.waitFor(() =>
      expect(wrapper.findAllComponents(DateTimeRangeInput)).toHaveLength(1),
    );
    const range = wrapper.findComponent(DateTimeRangeInput);
    expect(range.props('timeZone')).toBeUndefined();
    expect(range.props('label')).toBe('Date range');
    range.vm.$emit('update:modelValue', {
      start: '2026-11-02T12:00:00Z',
      end: '2026-11-03T12:00:00Z',
    });
    await vi.waitFor(() =>
      expect(
        (wrapper.getComponent(Table).props() as { query: TableQuery }).query
          .filters,
      ).toMatchObject({
        from: '2026-11-02T12:00:00Z',
        until: '2026-11-03T12:00:00Z',
      }),
    );
  },
);
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
  await vi.waitFor(() => expect(wrapper.text()).toContain('runa.auth.reader'));
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
    .find((button) => button.text() === 'Details')!
    .trigger('click');
  expect(form.values.value[form.fieldPath('editor', 'name_en')]).toBe(
    'Unsaved role',
  );
  expect(form.records.value.editor!.public_id).toBe(id);
});
it.each(['pl', 'en'])(
  'Audit Admin detail %s exposes escaped values and direct correlation/actor history links',
  async (locale) => {
    savePreference('runa.locale', locale);
    const descriptor = section('detail', [
      field('action'),
      field('changes'),
      { ...field('outcome'), type: 'dictionary', choices: ['success'] },
      field('entities'),
      field('actor_public_id'),
      field('correlation_id'),
    ]);
    const controller = page('runa.audit', 'history', [descriptor]);
    vi.spyOn(window, 'fetch').mockResolvedValue(
      response({
        public_id: id,
        revision: id,
        values: {
          action: 'example.changed',
          actor_public_id: other,
          correlation_id: id,
          changes: '<script>privateMarkup()</script>',
          outcome: 'success',
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
    expect(
      wrapper
        .findAll('dd')
        .some(
          (entry) => entry.text() === (locale === 'pl' ? 'sukces' : 'success'),
        ),
    ).toBe(true);
    const links = wrapper.findAll('a');
    expect(links).toHaveLength(2);
    for (const [filter, value, group] of [
      ['correlation_id', id, locale === 'pl' ? 'Zdarzenie' : 'Event'],
      ['actor_public_id', other, locale === 'pl' ? 'Wykonawca' : 'Actor'],
    ]) {
      const link = links.find((link) => link.text() === value)!;
      const url = new URL(link.attributes('href')!, window.location.origin);
      expect(url.pathname).toBe('/admin/runa.audit/history');
      expect([...url.searchParams.entries()]).toEqual([[filter, value]]);
      expect(
        link.element.closest('section')?.querySelector('h3')?.textContent,
      ).toBe(group);
    }
    expect(wrapper.text()).not.toContain('Technical identifiers');
    expect(wrapper.text()).not.toContain('Related entity history');
    expect(wrapper.find('script').exists()).toBe(false);
    expect(
      wrapper
        .findAll('button')
        .filter((button) => /Save|Delete|Undo/.test(button.text())),
    ).toHaveLength(0);
  },
);
it('Audit Admin entity history links initialize the exact bounded filter query', async () => {
  window.history.replaceState(
    null,
    '',
    `/admin/runa.audit/history?entity_module=runa.auth&entity_type=user&entity_public_id=${other}`,
  );
  const descriptor = section(
    'catalogue',
    [
      { ...field('occurred_at'), type: 'datetime' },
      ...['entity_module', 'entity_type', 'entity_public_id'].map((key) => ({
        ...field(key),
        filterable: true,
      })),
    ],
    'table',
  );
  const controller = page('runa.audit', 'history', [descriptor]);
  const fetch = vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      items: [
        {
          public_id: id,
          revision: '1',
          values: { occurred_at: '2026-10-31T18:25:00Z' },
        },
      ],
      next: null,
      total: null,
    }),
  );
  const wrapper = mount(
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
  await vi.waitFor(() =>
    expect(wrapper.text()).toContain(
      new Date('2026-10-31T18:25:00Z').toLocaleString('en-GB', {
        timeZone: 'Europe/Warsaw',
      }),
    ),
  );
  expect(wrapper.text()).not.toContain('2026-10-31T18:25:00Z');
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

it.each(['pl', 'en'])(
  'bounded value preview %s formats escaped JSON, expands, collapses and resets on replacement',
  async (locale) => {
    const raw = JSON.stringify({
      message: '<img src=x onerror=alert(1)>',
      rows: Array.from({ length: 40 }, (_, i) => ({
        index: i,
        value: 'Long diagnostic value',
      })),
    });
    const wrapper = mount(ValuePreview, {
      props: { text: raw, format: 'json', locale: locale as 'pl' | 'en' },
    });
    expect(wrapper.get('p').text()).toContain('"message":');
    expect(wrapper.get('p').text()).not.toContain('"index": 39');
    expect(wrapper.find('img').exists()).toBe(false);
    const button = wrapper.get('a');
    expect(button.text()).toBe(locale === 'pl' ? 'Pokaż wszystko' : 'Show all');
    expect(button.attributes('aria-expanded')).toBe('false');
    expect(button.attributes('aria-controls')).toBe(
      wrapper.get('p').attributes('id'),
    );
    await button.trigger('click');
    expect(wrapper.get('p').text()).toContain('"index": 39');
    expect(button.attributes('aria-expanded')).toBe('true');
    await button.trigger('click');
    expect(wrapper.get('p').text()).not.toContain('"index": 39');
    await button.trigger('click');
    await wrapper.setProps({ text: 'partial JSON: "' + 'x'.repeat(900) });
    expect(button.attributes('aria-expanded')).toBe('false');
    expect(wrapper.get('p').text()).toContain('partial JSON:');
    await wrapper.setProps({ text: '{"ok":true}' });
    expect(wrapper.find('a').exists()).toBe(false);
    expect(wrapper.get('p').text()).toContain('"ok": true');
  },
);

it('Audit detail omits filter-only placeholders and single-part controls, and pages bounded payloads explicitly', async () => {
  const descriptor = section(
    'detail',
    [
      'action',
      'from',
      'until',
      'entity_type',
      'changes',
      'context',
      'execution',
      'payload_part',
      'payload_next',
    ].map((key) => field(key)),
  );
  const controller = page('runa.audit', 'history', [descriptor]);
  const values = {
    action: 'example.changed',
    changes: '[]',
    context: '{}',
    execution: JSON.stringify({ long: 'x'.repeat(900) }),
    payload_part: '1 / 1',
    payload_next: null,
  };
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const cursor = new URL(
      String(input),
      window.location.origin,
    ).searchParams.get('cursor');
    return response({
      public_id: id,
      revision: id,
      values: cursor
        ? {
            ...values,
            execution: 'last payload fragment',
            payload_part: '2 / 2',
          }
        : values,
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, { autoRead: false });
        return () =>
          h(AuditAdminDetail, {
            page: controller,
            section: descriptor,
            controller: form,
          });
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Show all'));
  expect(wrapper.findAll('dt').map((dt) => dt.text())).toEqual(['action']);
  expect(wrapper.text()).not.toContain('Payload part');
  expect(wrapper.text()).not.toContain('Next payload part');
  expect(wrapper.text()).toContain('No data.');
  form.records.value = {
    ...form.records.value,
    detail: {
      public_id: id,
      revision: id,
      values: { ...values, payload_part: '1 / 2', payload_next: '1' },
    },
  };
  await vi.waitFor(() => expect(wrapper.text()).toContain('Next payload part'));
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Next payload part')!
    .trigger('click');
  await vi.waitFor(() => expect(wrapper.text()).toContain('2 / 2'));
  expect(
    fetch.mock.calls.some(
      ([input]) =>
        new URL(String(input), window.location.origin).searchParams.get(
          'cursor',
        ) === '1',
    ),
  ).toBe(true);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'First payload part')!
    .trigger('click');
  await vi.waitFor(() => expect(wrapper.text()).toContain('1 / 2'));
});

it('bounded value preview preserves exact JSON numeric and string tokens and duplicate keys', () => {
  const text =
    '{"id":9223372036854775807,"id":1e+30,"text":"\\u0105 \\" \\\\ [ ] : ,","empty": [ ]}';
  const wrapper = mount(ValuePreview, { props: { text, format: 'json' } });
  expect(wrapper.get('p').text()).toContain('9223372036854775807');
  expect(wrapper.get('p').text()).toContain('1e+30');
  expect(wrapper.get('p').text().match(/"id"/g)).toHaveLength(2);
  expect(JSON.parse(wrapper.get('p').text())).toEqual(JSON.parse(text));
});

it('bounded value preview colors JSON tokens while preserving escaped source text and plain mode', async () => {
  const text =
    '{"text":"<script>\\"content\\"</script>","number":-42.25e+3,"truth":true,"empty":null}';
  const wrapper = mount(ValuePreview, { props: { text, format: 'json' } });
  expect(JSON.parse(wrapper.get('p').text())).toEqual(JSON.parse(text));
  expect(wrapper.find('script').exists()).toBe(false);
  expect(
    wrapper.findAll('[data-json-token="key"]').map((token) => token.text()),
  ).toEqual(['"text"', '"number"', '"truth"', '"empty"']);
  expect(wrapper.get('[data-json-token="number"]').text()).toBe('-42.25e+3');
  expect(
    wrapper.findAll('[data-json-token="literal"]').map((token) => token.text()),
  ).toEqual(['true', 'null']);
  await wrapper.setProps({ format: 'text' });
  expect(wrapper.get('p').text()).toBe(text);
  expect(wrapper.find('[data-json-token]').exists()).toBe(false);
});

it('Audit detail leaves missing correlation and actor IDs unlinked', async () => {
  const descriptor = section('detail', [
    field('correlation_id'),
    field('actor_public_id'),
  ]);
  const controller = page('runa.audit', 'history', [descriptor]);
  vi.spyOn(window, 'fetch').mockResolvedValue(
    response({
      public_id: id,
      revision: id,
      values: { correlation_id: null, actor_public_id: null },
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
  await vi.waitFor(() => expect(wrapper.findAll('dd')).toHaveLength(2));
  expect(wrapper.find('a').exists()).toBe(false);
  expect(wrapper.findAll('dd').map((dd) => dd.text())).toEqual(['—', '—']);
});

it('Audit detail animates delayed initial reads, settles failure and animates retry and continuation in place', async () => {
  const descriptor = section(
    'detail',
    ['action', 'execution', 'payload_part', 'payload_next'].map((key) =>
      field(key),
    ),
  );
  const controller = page('runa.audit', 'history', [descriptor]);
  let finish!: (value: Response) => void;
  vi.spyOn(window, 'fetch').mockImplementation(
    () =>
      new Promise<Response>((resolve) => {
        finish = resolve;
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
  await vi.waitFor(() =>
    expect(wrapper.find('[role="status"]').attributes('aria-label')).toBe(
      'Loading event details',
    ),
  );
  expect(wrapper.text()).not.toContain('Select an event');
  finish(
    new Response(JSON.stringify({ message: 'Unavailable' }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('[role="status"]').exists()).toBe(false),
  );
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Retry loading')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(wrapper.find('[role="status"]').exists()).toBe(true),
  );
  finish(
    response({
      public_id: id,
      revision: id,
      values: {
        action: 'example.changed',
        execution: 'first fragment',
        payload_part: '1 / 2',
        payload_next: '1',
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('first fragment'));
  expect(wrapper.find('[role="status"]').exists()).toBe(false);
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Next payload part')!
    .trigger('click');
  await vi.waitFor(() =>
    expect(wrapper.find('[role="region"][aria-busy="true"]').exists()).toBe(
      true,
    ),
  );
  expect(wrapper.text()).toContain('first fragment');
  expect(wrapper.text()).toContain('example.changed');
  finish(
    response({
      public_id: id,
      revision: id,
      values: {
        action: 'example.changed',
        execution: 'second fragment',
        payload_part: '2 / 2',
        payload_next: null,
      },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('second fragment'));
  expect(wrapper.find('[role="region"][aria-busy="true"]').exists()).toBe(
    false,
  );
});

it('shared Admin record section animates pending reads without showing an empty-state warning', async () => {
  const descriptor = section('editor', [field('name_en')]);
  const controller = page('runa.permissions', 'roles', [descriptor]);
  let finish!: (value: Response) => void;
  vi.spyOn(window, 'fetch').mockImplementation(
    () =>
      new Promise<Response>((resolve) => {
        finish = resolve;
      }),
  );
  const wrapper = mount(
    defineComponent({
      setup() {
        const form = useAdminForm(controller, { autoRead: false });
        return () => h(AdminSection, { section: descriptor, controller: form });
      },
    }),
  );
  await vi.waitFor(() =>
    expect(wrapper.find('[role="status"]').exists()).toBe(true),
  );
  expect(wrapper.text()).not.toContain('Unable to load');
  finish(
    response({
      public_id: id,
      revision: id,
      values: { name_en: 'Loaded definition' },
    }),
  );
  await vi.waitFor(() => expect(wrapper.text()).toContain('Loaded definition'));
  expect(wrapper.find('[role="status"]').exists()).toBe(false);
});

it('new role details reuse the compact permission catalogue before a record UUID exists', async () => {
  const descriptor = section('editor', [
    field('code', false),
    field('name_en', false),
    field('module'),
    { ...field('system'), type: 'boolean' },
    {
      ...field('permissions', false),
      type: 'uuid',
      multiple: true,
      lookup: true,
    },
  ]);
  descriptor.commands = [
    {
      key: 'store',
      title: 'Save',
      mode: 'independent',
      intent: 'action',
      inputs: ['code', 'name_en', 'permissions'],
      requirement: 'password',
      action: 'runa.permissions.access.manage',
      destructive: false,
      bulk: false,
      preview_required: true,
      available: true,
    },
  ];
  const controller = page('runa.permissions', 'roles', [descriptor]);
  const options: URL[] = [];
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    const url = new URL(String(input), window.location.origin);
    if (url.searchParams.get('kind') === 'options') {
      options.push(url);
      return response({
        field: 'permissions',
        items: [{ value: other, label: 'Read audit history' }],
        next: null,
      });
    }
    return response({
      public_id: null,
      revision: '0',
      values: {
        code: 'custom.',
        name_en: null,
        permissions: [],
        module: null,
        system: false,
      },
    });
  });
  let form!: AdminFormController;
  const wrapper = mount(
    defineComponent({
      setup() {
        form = useAdminForm(controller, { autoRead: false });
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
    expect(wrapper.text()).toContain('Read audit history'),
  );
  expect(options[0]!.searchParams.has('public_id')).toBe(false);
  await wrapper.get('[data-assignment-option] input').setValue(true);
  expect(form.values.value[form.fieldPath('editor', 'permissions')]).toEqual([
    other,
  ]);
  expect(wrapper.findAll('form')).toHaveLength(1);
  expect(
    wrapper.findAll('button').some((button) => button.text() === 'Save'),
  ).toBe(true);
});
