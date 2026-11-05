/* eslint-disable vue/one-component-per-file -- Independent controlled table regression fixtures. */
import { expect, it, vi } from '@runa/testing/vitest';
import { mount, flushPromises } from '@runa/testing/vue';
import { defineComponent, h, ref } from 'vue';
import AppDateTimeInput from '../../../../src/runa/resources/js/components/AppDateTimeInput.vue';
import {
  Table,
  useTable,
  clientTablePage,
  createApiTableProvider,
  type TableColumn,
  type TablePage,
  type TableProvider,
  type TableQuery,
} from '../../../../src/runa/resources/js/runa/ui';
const rows = [
  { public_id: 'a', name: 'Beta', count: 12 },
  { public_id: 'b', name: 'Alpha', count: 2 },
];
type Row = (typeof rows)[number];
const columns: TableColumn<Row>[] = [
  {
    key: 'name',
    label: 'Name',
    value: (r) => r.name,
    sortable: true,
    filter: (r, value) => r.name.includes(value),
  },
  {
    key: 'count',
    label: 'Count',
    value: (r) => r.count,
    sortable: true,
    compare: (a, b) => a.count - b.count,
  },
];
const query = (): TableQuery => ({
  page: 1,
  perPage: 1,
  sort: null,
  filters: {},
});
it('datetime table filters keep invalid drafts local, normalize zoned edits and reset uncommitted drafts', async () => {
  const value = ref(query());
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(Table<Row>, {
          mode: 'client',
          rows,
          columns: [{ ...columns[0]!, filterType: 'datetime' }],
          rowKey: (row: Row) => row.public_id,
          query: value.value,
          caption: 'Dates',
          'onUpdate:query': (next: TableQuery) => {
            value.value = next;
          },
        }),
    }),
    { attachTo: document.body },
  );
  try {
    const input = wrapper.getComponent(AppDateTimeInput).get('input');
    expect(input.attributes('data-size')).toBe('sm');
    expect(
      getComputedStyle(wrapper.get('.temporal-trigger').element).height,
    ).toBe('36px');
    await input.setValue('31.02.2026 12:30');
    expect(value.value.filters).toEqual({});
    expect(input.attributes('aria-invalid')).toBe('true');
    await wrapper.get('button[aria-label="Resetuj filtry"]').trigger('click');
    expect(
      (
        wrapper.getComponent(AppDateTimeInput).get('input')
          .element as HTMLInputElement
      ).value,
    ).toBe('');
    await wrapper
      .getComponent(AppDateTimeInput)
      .get('input')
      .setValue('31.10.2026 12:30');
    expect(value.value.filters.name).toBe('2026-10-31T11:30:00.000Z');
    expect(value.value.page).toBe(1);
  } finally {
    wrapper.unmount();
  }
});

it('table viewport pins the original header inside a scrolling region and synchronizes both horizontal controls', async () => {
  const host = document.createElement('div');
  host.style.cssText =
    'height:240px;width:320px;overflow:auto;position:relative';
  const spacer = document.createElement('div');
  spacer.style.height = '100px';
  host.append(spacer);
  document.body.append(host);
  const wrapper = mount(Table<Row>, {
    attachTo: host,
    props: {
      mode: 'client',
      rows: Array.from({ length: 20 }, (_, i) => ({
        ...rows[0]!,
        public_id: String(i),
      })),
      columns,
      columnState: columns.map((column) => ({
        key: column.key,
        visible: true,
        width: 400,
      })),
      rowKey: (row: Row) => row.public_id,
      query: { ...query(), perPage: 20 },
      caption: 'Wide rows',
      searchable: false,
    },
  });
  try {
    await vi.waitFor(() =>
      expect(wrapper.findAll('tbody tr')).toHaveLength(20),
    );
    const viewport = wrapper.get('[data-table-viewport]')
      .element as HTMLElement;
    // This unit harness does not load Tailwind; supply only the layout primitives.
    viewport.style.position = 'relative';
    (wrapper.get('table').element as HTMLElement).style.borderCollapse =
      'collapse';
    (wrapper.get('caption').element as HTMLElement).style.display = 'none';
    const region = viewport.firstElementChild as HTMLElement;
    region.style.overflowX = 'auto';
    const rail = viewport.lastElementChild as HTMLElement;
    rail.style.overflowX = 'scroll';
    rail.style.position = 'absolute';
    rail.style.width = '320px';
    wrapper.findAll('tbody td').forEach((cell) => {
      (cell.element as HTMLElement).style.height = '35px';
    });
    await vi.waitFor(() => expect(rail.style.display).not.toBe('none'));
    await vi.waitFor(() =>
      expect(wrapper.get('[data-table-viewport]').classes()).toContain(
        'has-overflow-right',
      ),
    );
    const originalHead = wrapper.get('thead').element;
    const labelCell = wrapper.get('th[data-column-key="name"]').element;
    const headBounds = originalHead.getBoundingClientRect();
    const labelBounds = labelCell.getBoundingClientRect();
    expect(
      Math.abs(
        labelBounds.top +
          labelBounds.height / 2 -
          headBounds.top -
          headBounds.height / 2,
      ),
    ).toBeLessThan(1);
    const wheel = (target: Element, init: WheelEventInit) => {
      const event = new WheelEvent('wheel', {
        bubbles: true,
        cancelable: true,
        ...init,
      });
      target.dispatchEvent(event);
      return event;
    };
    expect(wheel(originalHead, { deltaY: 80 }).defaultPrevented).toBe(true);
    expect(region.scrollLeft).toBe(80);
    await vi.waitFor(() =>
      expect(wrapper.get('[data-table-viewport]').classes()).toContain(
        'has-overflow-left',
      ),
    );
    expect(rail.scrollLeft).toBe(80);
    wheel(originalHead, { deltaY: 2, deltaMode: 1 });
    expect(region.scrollLeft).toBe(112);
    wheel(rail, { deltaX: 90, deltaY: 10 });
    expect(region.scrollLeft).toBe(202);
    expect(
      wheel(originalHead, { deltaY: 100, ctrlKey: true }).defaultPrevented,
    ).toBe(false);
    expect(region.scrollLeft).toBe(202);
    region.scrollLeft = 0;
    expect(wheel(originalHead, { deltaY: -20 }).defaultPrevented).toBe(true);
    expect(region.scrollLeft).toBe(0);
    wheel(originalHead, { deltaY: 1, deltaMode: 2 });
    expect(region.scrollLeft).toBe(region.clientWidth);
    region.scrollLeft = region.scrollWidth;
    expect(wheel(originalHead, { deltaY: 20 }).defaultPrevented).toBe(true);
    expect(
      wheel(wrapper.get('tbody').element, { deltaY: 20 }).defaultPrevented,
    ).toBe(false);
    // Coalesced scroll events publish both layers together before paint.
    for (const distance of [40, 95, 180, 70]) {
      host.scrollTop = viewport.offsetTop + distance;
      host.dispatchEvent(new Event('scroll'));
      await new Promise<void>((resolve) =>
        requestAnimationFrame(() => resolve()),
      );
      expect(
        Math.abs(
          originalHead.getBoundingClientRect().top -
            host.getBoundingClientRect().top,
        ),
      ).toBeLessThan(2);
      expect(originalHead.style.transform).toBe(rail.style.transform);
    }
    host.scrollTop = viewport.offsetTop + 60;
    await vi.waitFor(() =>
      expect(
        Math.abs(
          originalHead.getBoundingClientRect().top -
            host.getBoundingClientRect().top,
        ),
      ).toBeLessThan(2),
    );
    rail.scrollLeft = 120;
    await vi.waitFor(() => expect(region.scrollLeft).toBe(120));
    region.scrollLeft = 230;
    await vi.waitFor(() => expect(rail.scrollLeft).toBe(230));
    host.scrollTop = viewport.offsetTop + viewport.offsetHeight;
    await vi.waitFor(() =>
      expect(originalHead.getBoundingClientRect().bottom).toBeLessThanOrEqual(
        region.getBoundingClientRect().bottom + 1,
      ),
    );
    expect(wrapper.findAll('thead')).toHaveLength(1);
    region.style.width = '1000px';
    expect(wheel(originalHead, { deltaY: 20 }).defaultPrevented).toBe(false);
  } finally {
    wrapper.unmount();
    host.remove();
  }
});
it('sorts numeric values before slicing and filters with explicit adapters', () => {
  const page = clientTablePage(rows, columns, {
    ...query(),
    sort: { key: 'count', direction: 'asc' },
  });
  expect(page.rows[0]?.public_id).toBe('b');
  expect(page.matching).toBe(2);
  expect(page.hasNext).toBe(true);
  expect(
    clientTablePage(rows, columns, { ...query(), filters: { name: 'Alpha' } })
      .matching,
  ).toBe(1);
});
it('encodes allowlisted API query values and bounds pagination', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(
      new Response('{}', { headers: { 'Content-Type': 'application/json' } }),
    );
  const page = clientTablePage(rows, columns, query());
  const provider = createApiTableProvider({
    path: '/projects',
    sort: ['name'],
    filter: ['name'],
    maxPerPage: 10,
    decode: () => page,
  });
  await provider(
    {
      page: -1,
      perPage: 1000,
      sort: { key: 'forbidden', direction: 'asc' },
      filters: { name: 'a&sort=secret #?', forbidden: 'x' },
    },
    new AbortController().signal,
  );
  const url = new URL(String(fetch.mock.calls[0]?.[0]), window.location.origin);
  expect(url.searchParams.get('filter[name]')).toBe('a&sort=secret #?');
  expect(url.searchParams.get('sort')).toBeNull();
  expect(url.searchParams.get('filter[forbidden]')).toBeNull();
  expect(url.searchParams.get('per_page')).toBe('10');
  expect(url.searchParams.get('page')).toBe('1');
});
it('aborts obsolete requests and ignores providers that resolve after cancellation', async () => {
  const pending: {
    resolve: (page: TablePage<Row>) => void;
    signal: AbortSignal;
  }[] = [];
  const state = ref(query());
  let table!: ReturnType<typeof useTable<Row>>;
  const wrapper = mount(
    defineComponent({
      setup() {
        table = useTable(
          () => ({
            mode: 'server',
            provider: (_query, signal) =>
              new Promise((resolve) => pending.push({ resolve, signal })),
          }),
          () => columns,
          state,
        );
        return () => h('div');
      },
    }),
  );
  state.value = { ...query(), page: 2 };
  await flushPromises();
  expect(pending[0]?.signal.aborted).toBe(true);
  pending[1]!.resolve(clientTablePage(rows, columns, state.value));
  await flushPromises();
  pending[0]!.resolve(clientTablePage(rows, columns, query()));
  await flushPromises();
  expect(table.page.value?.rows[0]?.public_id).toBe('b');
  wrapper.unmount();
  expect(pending[1]?.signal.aborted).toBe(true);
});
it('renders semantic sorting, row selection and empty recovery', async () => {
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'client',
      rows,
      columns,
      rowKey: (row) => row.public_id,
      query: query(),
      caption: 'Projects',
      selectable: true,
    },
    attachTo: document.body,
  });
  await flushPromises();
  expect(wrapper.find('caption').text()).toBe('Projects');
  await wrapper.find('thead button').trigger('click');
  expect(wrapper.emitted('update:query')?.[0]?.[0]).toMatchObject({
    page: 1,
    sort: { key: 'name', direction: 'asc' },
  });
  await wrapper.find('input[type="checkbox"]').setValue(true);
  expect(wrapper.emitted('update:selected')?.[0]?.[0]).toEqual(['a']);
  await wrapper.setProps({
    query: { ...query(), filters: { name: 'Missing' } },
  });
  await flushPromises();
  await vi.waitFor(() => expect(wrapper.text()).toContain('Brak wyników'));
  wrapper.unmount();
});
it('recovers from provider failures and uses hasNext with uncertain counts', async () => {
  const provider = vi
    .fn()
    .mockRejectedValueOnce(new Error('Unavailable'))
    .mockResolvedValue({
      ...clientTablePage(rows, columns, query()),
      matching: null,
      total: null,
      counts: 'unknown',
      hasNext: true,
    });
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'server',
      provider,
      columns,
      rowKey: (row) => row.public_id,
      query: query(),
      caption: 'Projects',
      english: true,
    },
  });
  await flushPromises();
  expect(wrapper.text()).toContain('Unable to load data');
  await wrapper
    .findAll('button')
    .find((button) => button.text() === 'Retry')!
    .trigger('click');
  await flushPromises();
  expect(wrapper.text()).toContain('Count unconfirmed');
  const next = wrapper
    .findAll('button')
    .find((button) => button.text() === 'Next')!;
  expect(next.attributes('disabled')).toBeUndefined();
  await next.trigger('click');
  expect(wrapper.emitted('update:query')?.[0]?.[0]).toMatchObject({ page: 2 });
  wrapper.unmount();
});
it('applies column presentation without fetching or losing hidden-column sorting', async () => {
  const provider = vi
    .fn()
    .mockResolvedValue(clientTablePage(rows, columns, query()));
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'server',
      provider,
      columns,
      rowKey: (row) => row.public_id,
      query: { ...query(), page: 2, sort: { key: 'name', direction: 'asc' } },
      caption: 'Projects',
    },
  });
  await flushPromises();
  await wrapper.setProps({
    columnState: [
      { key: 'count', visible: true, width: 220 },
      { key: 'name', visible: false },
    ],
  });
  expect(wrapper.findAll('thead th[data-column-key]')).toHaveLength(1);
  expect(wrapper.find('thead').text()).toContain('Count');
  expect(
    wrapper
      .findAll('col')
      .find((column) => column.attributes('style')?.includes('220px'))!
      .attributes('style'),
  ).toContain('220px');
  expect(provider).toHaveBeenCalledTimes(1);
  expect(wrapper.props('query').page).toBe(2);
  expect(wrapper.emitted('update:query')).toBeUndefined();
  expect(wrapper.props('query').sort?.key).toBe('name');
  wrapper.unmount();
});
it('searches all declared values, including hidden columns, before pagination', () => {
  const fields: TableColumn<Row>[] = [
    ...columns,
    {
      key: 'city',
      label: 'City',
      value: (r) => (r.public_id === 'a' ? 'Warszawa' : 'Gdańsk'),
    },
  ];
  expect(
    clientTablePage(rows, fields, { ...query(), search: 'warszawa' }).rows[0]
      ?.public_id,
  ).toBe('a');
  expect(
    clientTablePage(rows, fields, { ...query(), search: 'Beta 12' }).matching,
  ).toBe(1);
  expect(
    clientTablePage(rows, fields, { ...query(), search: 'Gdańsk Beta' })
      .matching,
  ).toBe(0);
});
it('passes global search as an encoded backend filter and allows disabling it', async () => {
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(
      async () =>
        new Response('{}', { headers: { 'Content-Type': 'application/json' } }),
    );
  const options = {
    path: '/projects',
    sort: [],
    filter: [],
    maxPerPage: 20,
    decode: () => clientTablePage(rows, columns, query()),
  };
  await createApiTableProvider(options)(
    { ...query(), search: 'Warszawa & 12' },
    new AbortController().signal,
  );
  expect(
    new URL(
      String(fetch.mock.calls[0]?.[0]),
      window.location.origin,
    ).searchParams.get('filter[search]'),
  ).toBe('Warszawa & 12');
  await createApiTableProvider({ ...options, searchable: false })(
    { ...query(), search: 'hidden' },
    new AbortController().signal,
  );
  expect(
    new URL(
      String(fetch.mock.calls[1]?.[0]),
      window.location.origin,
    ).searchParams.has('filter[search]'),
  ).toBe(false);
});

it('sorting defaults support numeric ordering, opt-out and the controlled three-state cycle', async () => {
  const defaults: TableColumn<Row>[] = [
    { key: 'name', label: 'Name', value: (row) => row.name },
    { key: 'count', label: 'Count', value: (row) => row.count },
    {
      key: 'disabled',
      label: 'Disabled',
      value: (row) => row.name,
      sortable: false,
    },
  ];
  expect(
    clientTablePage(rows, defaults, {
      ...query(),
      sort: { key: 'count', direction: 'asc' },
    }).rows[0]?.public_id,
  ).toBe('b');
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'client',
      rows,
      columns: defaults,
      rowKey: (row) => row.public_id,
      query: { ...query(), perPage: 10 },
      caption: 'Default sorting',
    },
    attachTo: document.body,
  });
  try {
    await flushPromises();
    expect(wrapper.findAll('thead button')).toHaveLength(2);
    const header = () => wrapper.get('th[data-column-key="count"]');
    for (const [direction, first] of [
      ['asc', 'Alpha'],
      ['desc', 'Beta'],
      [null, 'Beta'],
    ] as const) {
      await header().get('button').trigger('click');
      const emitted = wrapper.emitted('update:query')!.at(-1)![0] as TableQuery;
      expect(emitted.sort).toEqual(
        direction ? { key: 'count', direction } : null,
      );
      expect(emitted.page).toBe(1);
      await wrapper.setProps({ query: emitted });
      await flushPromises();
      await vi.waitFor(() =>
        expect(wrapper.get('tbody tr').text()).toContain(first),
      );
      expect(header().attributes('aria-sort')).toBe(
        direction === 'asc'
          ? 'ascending'
          : direction === 'desc'
            ? 'descending'
            : undefined,
      );
    }
    expect(
      wrapper.get('th[data-column-key="disabled"]').find('button').exists(),
    ).toBe(false);
  } finally {
    wrapper.unmount();
  }
});

it('sorting defaults keep server capability restrictions and forward supported queries', async () => {
  const defaults = columns.map((column) => {
    const copy = { ...column };
    delete copy.sortable;
    return copy;
  });
  const provider = vi.fn<TableProvider<Row>>(async () => ({
    ...clientTablePage(rows, defaults, { ...query(), perPage: 10 }),
    capabilities: { sort: ['name'], filter: [], maxPerPage: 100 },
  }));
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'server',
      provider,
      columns: defaults,
      rowKey: (row) => row.public_id,
      query: query(),
      caption: 'Server sorting',
    },
  });
  try {
    await flushPromises();
    expect(
      wrapper.get('th[data-column-key="count"]').find('button').exists(),
    ).toBe(false);
    await wrapper.get('th[data-column-key="name"] button').trigger('click');
    const next = wrapper.emitted('update:query')!.at(-1)![0] as TableQuery;
    await wrapper.setProps({ query: next });
    await flushPromises();
    expect(provider.mock.calls.at(-1)?.[0]).toMatchObject({
      sort: { key: 'name', direction: 'asc' },
    });
  } finally {
    wrapper.unmount();
  }
});

it('shows one centered spinner for pagination, page size, sorting, search and filters in client and server tables', async () => {
  for (const mode of ['client', 'server'] as const) {
    const provider = vi.fn(async (value: TableQuery) =>
      clientTablePage(rows, columns, value),
    );
    const wrapper = mount(Table<Row>, {
      props: {
        mode,
        rows,
        provider,
        columns,
        rowKey: (row) => row.public_id,
        query: query(),
        caption: 'Projects',
      },
    });
    await flushPromises();
    const changes: TableQuery[] = [
      { ...query(), page: 2 },
      { ...query(), perPage: 2 },
      { ...query(), sort: { key: 'name', direction: 'asc' } },
      { ...query(), search: 'Alpha' },
      { ...query(), filters: { name: 'Missing' } },
    ];
    for (const value of changes) {
      await wrapper.setProps({ query: value });
      await flushPromises();
      expect(wrapper.findAll('[data-table-loading]')).toHaveLength(1);
      expect(wrapper.get('[data-table-loading]').text()).toBe('');
      expect(
        wrapper.find('[data-table-loading] [role="status"]').exists(),
      ).toBe(true);
      expect(wrapper.text()).not.toContain('Brak wyników');
      await vi.waitFor(() =>
        expect(wrapper.find('[data-table-loading]').exists()).toBe(false),
      );
    }
    expect(wrapper.text()).toContain('Brak wyników');
    if (mode === 'server')
      expect(provider).toHaveBeenCalledTimes(changes.length + 1);
    wrapper.unmount();
  }
});

it('keeps query feedback continuous through rapid changes and a slower provider response', async () => {
  let resolve!: (value: TablePage<Row>) => void;
  const provider = vi.fn(
    () => new Promise<TablePage<Row>>((done) => (resolve = done)),
  );
  const wrapper = mount(Table<Row>, {
    props: {
      mode: 'server',
      provider,
      columns,
      rowKey: (row) => row.public_id,
      query: query(),
      caption: 'Projects',
    },
  });
  resolve(clientTablePage(rows, columns, query()));
  await flushPromises();
  await wrapper.setProps({ query: { ...query(), page: 2 } });
  await wrapper.setProps({ query: { ...query(), filters: { name: 'Alpha' } } });
  const indicator = wrapper.get('[data-table-loading]').element;
  await new Promise((done) => setTimeout(done, 180));
  expect(wrapper.get('[data-table-loading]').element).toBe(indicator);
  resolve(
    clientTablePage(rows, columns, { ...query(), filters: { name: 'Alpha' } }),
  );
  await flushPromises();
  expect(wrapper.find('[data-table-loading]').exists()).toBe(false);
  expect(wrapper.text()).toContain('Alpha');
  wrapper.unmount();
});

it('shared table refresh uses the owning read and controlled filters reset without losing layout', async () => {
  const refresh = vi.fn(async () => {});
  const controlled = ref({
    ...query(),
    filters: { name: 'Alpha' },
    search: 'A',
    sort: { key: 'name', direction: 'asc' as const },
  });
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(Table<Row>, {
          mode: 'client',
          rows,
          columns,
          rowKey: (row) => row.public_id,
          query: controlled.value,
          caption: 'Shared controls',
          english: true,
          refresh,
          'onUpdate:query': (value) => {
            controlled.value = value as typeof controlled.value;
          },
        }),
    }),
  );
  await flushPromises();
  expect(
    wrapper
      .find('[data-table-toolbar] button[aria-label="Reset filters"]')
      .exists(),
  ).toBe(false);
  await wrapper
    .get('[data-table-filters] button[aria-label="Reset filters"]')
    .trigger('click');
  expect(controlled.value.filters).toEqual({});
  expect(controlled.value.search).toBe('');
  expect(controlled.value.sort).toEqual({ key: 'name', direction: 'asc' });
  expect(controlled.value.perPage).toBe(1);
  await vi.waitFor(() =>
    expect(
      wrapper.get('button[aria-label="Refresh"]').attributes('disabled'),
    ).toBeUndefined(),
  );
  await wrapper.get('button[aria-label="Refresh"]').trigger('click');
  expect(refresh).toHaveBeenCalledTimes(1);
  expect(wrapper.get('button[aria-label="Refresh"]').text()).toBe('');
  wrapper.unmount();
});

it('shared table filters use declared dictionaries and update the canonical query automatically', async () => {
  const controlled = ref(query());
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(Table<Row>, {
          mode: 'client',
          rows,
          columns: [
            {
              ...columns[0]!,
              filterOptions: [{ value: 'Alpha', label: 'Alpha' }],
            },
          ],
          rowKey: (row) => row.public_id,
          query: controlled.value,
          caption: 'Dictionaries',
          english: true,
          'onUpdate:query': (value) => {
            controlled.value = value;
          },
        }),
    }),
  );
  await flushPromises();
  const select = wrapper
    .findAllComponents({ name: 'AppSelect' })
    .find((control) => control.props('label') === 'Name');
  expect(select).toBeDefined();
  select!.vm.$emit('update:modelValue', 'Alpha');
  await flushPromises();
  expect(controlled.value.filters).toEqual({ name: 'Alpha' });
  expect(controlled.value.page).toBe(1);
  wrapper.unmount();
});

it('shared table export hides denied formats and fences a revoked in-flight authorization', async () => {
  const allow = ref(false);
  let complete!: (value: readonly Row[]) => void;
  const read = vi.fn(
    () =>
      new Promise<readonly Row[]>((resolve) => {
        complete = resolve;
      }),
  );
  const wrapper = mount(
    defineComponent({
      setup: () => () =>
        h(Table<Row>, {
          mode: 'client',
          rows,
          columns,
          rowKey: (row) => row.public_id,
          query: query(),
          caption: 'Export grant',
          english: true,
          exportAllowed: allow.value,
          exportRows: read,
        }),
    }),
  );
  await flushPromises();
  expect(wrapper.find('button[aria-label="CSV"]').exists()).toBe(false);
  allow.value = true;
  await flushPromises();
  expect(wrapper.find('button[aria-label="Print"]').exists()).toBe(true);
  await wrapper.get('button[aria-label="CSV"]').trigger('click');
  expect(read).toHaveBeenCalledTimes(1);
  allow.value = false;
  await flushPromises();
  complete(rows);
  await flushPromises();
  expect(wrapper.find('button[aria-label="CSV"]').exists()).toBe(false);
  wrapper.unmount();
});
