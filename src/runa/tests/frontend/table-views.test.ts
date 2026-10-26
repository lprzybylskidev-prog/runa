import { afterEach, expect, it } from 'vitest';
import { mount, enableAutoUnmount } from '@vue/test-utils';
import { defineComponent, h, nextTick, ref } from 'vue';
import {
  createPreferenceStore,
  preferenceKey,
  normalizeTableColumns,
  normalizeTableSnapshot,
  useTableViews,
  type TableColumn,
  type TableQuery,
  type PreferenceStorage,
} from '../../resources/js/runa/ui';
enableAutoUnmount(afterEach);
const scope = { applicationId: 'test', moduleKey: 'ui', viewKey: 'projects' };
const columns: TableColumn<{ name: string }>[] = [
  {
    key: 'name',
    label: 'Name',
    value: (r) => r.name,
    sortable: true,
    filter: (r, q) => r.name.includes(q),
  },
  { key: 'city', label: 'City', value: () => 'City' },
];
const initial = (): TableQuery => ({
  page: 1,
  perPage: 5,
  sort: null,
  filters: {},
});
function adapter(raw?: string): PreferenceStorage {
  const memory = new Map<string, string>(
    raw ? [[preferenceKey(scope, 'table-views'), raw]] : [],
  );
  return {
    read: (key) => memory.get(key) ?? null,
    write: (key, value) => {
      memory.set(key, value);
    },
    remove: (key) => {
      memory.delete(key);
    },
  };
}
function setup(storage: PreferenceStorage, descriptors = ref(columns)) {
  const query = ref(initial());
  let controller!: ReturnType<typeof useTableViews>;
  const wrapper = mount(
    defineComponent({
      setup() {
        controller = useTableViews({
          scope,
          columns: () => descriptors.value,
          query,
          store: createPreferenceStore(storage),
        });
        return () => h('div');
      },
    }),
  );
  return { controller, query, wrapper, descriptors };
}
it('preserves named view lifecycle, dirty snapshots and default on reopening', () => {
  const storage = adapter();
  const { controller: c, query, wrapper } = setup(storage);
  query.value = {
    ...initial(),
    page: 4,
    sort: { key: 'name', direction: 'desc' },
    filters: { name: 'Atlas' },
  };
  c.move('city', -1);
  c.setColumns(
    c.columns.value.map((column) =>
      column.key === 'name' ? { ...column, width: 240 } : column,
    ),
  );
  c.create('Atlas');
  const id = c.state.value.activeId!;
  c.setDefault(id);
  expect(c.dirty.value).toBe(false);
  query.value = { ...query.value, filters: { name: 'Beta' } };
  expect(c.dirty.value).toBe(true);
  c.update();
  expect(c.dirty.value).toBe(false);
  c.rename('Beta view');
  expect(() => c.create('beta VIEW')).toThrow('invalid_view_name');
  c.reset();
  expect(query.value.filters).toEqual({});
  c.select(id);
  expect(query.value.filters).toEqual({ name: 'Beta' });
  expect(query.value.page).toBe(1);
  wrapper.unmount();
  const reopened = setup(storage).controller;
  expect(reopened.state.value.activeId).toBe(id);
  expect(reopened.columns.value.map((c) => c.key)).toEqual(['city', 'name']);
  expect(reopened.columns.value[1]?.width).toBe(240);
  reopened.remove();
  expect(reopened.state.value.views).toEqual([]);
  expect(reopened.state.value.defaultId).toBeNull();
});
it('reconciles removed/new columns, invalid widths, unavailable sort/filter fields and saved views', async () => {
  const { controller: c, query, descriptors } = setup(adapter());
  query.value = {
    ...initial(),
    sort: { key: 'name', direction: 'asc' },
    filters: { name: 'Atlas' },
  };
  c.create('Old');
  c.setDefault(c.state.value.activeId);
  descriptors.value = [
    columns[1]!,
    { key: 'new', label: 'New', value: () => 'New' },
  ];
  await nextTick();
  expect(c.columns.value.map((c) => c.key)).toEqual(['city', 'new']);
  expect(query.value.sort).toBeNull();
  expect(query.value.filters).toEqual({});
  expect(c.state.value.views[0]?.snapshot.columns.map((c) => c.key)).toEqual([
    'city',
    'new',
  ]);
  expect(
    normalizeTableColumns(
      [
        { key: 'city', visible: false, width: 2000 },
        { key: 'city', visible: true },
        { key: 'removed' },
      ],
      ['city'],
    ),
  ).toEqual([{ key: 'city', visible: true, width: 800 }]);
});
it('retains the application baseline sort and deduplicates current descriptors', () => {
  const baseline: TableQuery = {
    ...initial(),
    sort: { key: 'name', direction: 'desc' },
  };
  expect(normalizeTableSnapshot({}, columns, baseline).query.sort).toEqual(
    baseline.sort,
  );
  expect(normalizeTableColumns([], ['name', 'name'])).toHaveLength(1);
});
it('recovers corrupt storage and migrates the version-one snapshot', () => {
  expect(setup(adapter('{invalid')).controller.columns.value).toHaveLength(2);
  const storage = adapter(
    JSON.stringify({
      version: 1,
      value: {
        columns: [
          { key: 'city', visible: false },
          { key: 'name', visible: true, width: 210 },
        ],
        query: { ...initial(), filters: { name: 'A' } },
      },
    }),
  );
  const { controller, query } = setup(storage);
  expect(controller.columns.value[0]).toEqual({ key: 'city', visible: false });
  expect(query.value.filters).toEqual({ name: 'A' });
  expect(
    JSON.parse(storage.read(preferenceKey(scope, 'table-views'))!).version,
  ).toBe(2);
});
it('handles denied storage in memory and resets/reorders without losing saved views', () => {
  const denied: PreferenceStorage = {
    read() {
      throw new Error('denied');
    },
    write() {
      throw new Error('denied');
    },
    remove() {
      throw new Error('denied');
    },
  };
  const { controller: c } = setup(denied);
  c.reorder('city', 'name');
  expect(c.columns.value[0]?.key).toBe('city');
  c.create('Local');
  c.setDefault(c.state.value.activeId);
  c.setDefault(null);
  expect(c.state.value.defaultId).toBeNull();
  c.resetColumns();
  expect(c.columns.value[0]?.key).toBe('name');
  expect(c.state.value.views).toHaveLength(1);
  c.reset();
  expect(c.state.value.activeId).toBeNull();
});
it('retains global search in saved snapshots and resolves the default option', () => {
  const storage = adapter();
  const { controller: c, query } = setup(storage);
  query.value = { ...initial(), search: 'City' };
  c.create('Search');
  const id = c.state.value.activeId!;
  c.setDefault(id);
  c.reset();
  expect(query.value.search).toBeUndefined();
  c.selectDefault();
  expect(query.value.search).toBe('City');
  c.setDefault(null);
  c.selectDefault();
  expect(query.value.search).toBeUndefined();
});

it('sorting defaults survive saved-view normalization and respect explicit opt-out', () => {
  const snapshot = {
    query: { ...initial(), sort: { key: 'city', direction: 'asc' } },
  };
  expect(
    normalizeTableSnapshot(snapshot, columns, initial()).query.sort,
  ).toEqual({ key: 'city', direction: 'asc' });
  expect(
    normalizeTableSnapshot(
      snapshot,
      columns.map((column) => ({ ...column, sortable: false })),
      initial(),
    ).query.sort,
  ).toBeNull();
});
