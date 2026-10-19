import { expect, it, vi } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { defineComponent, h, ref } from 'vue';
import {
  Table,
  useTable,
  clientTablePage,
  createApiTableProvider,
  type TableColumn,
  type TablePage,
  type TableQuery,
} from '../../resources/js/runa/ui';
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
  expect(wrapper.text()).toContain('Brak wyników');
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
      query: { ...query(), sort: { key: 'name', direction: 'asc' } },
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
  expect(wrapper.findAll('thead th')).toHaveLength(1);
  expect(wrapper.find('thead').text()).toContain('Count');
  expect(wrapper.find('col').attributes('style')).toContain('220px');
  expect(provider).toHaveBeenCalledTimes(1);
  expect(wrapper.props('query').sort?.key).toBe('name');
  wrapper.unmount();
});
