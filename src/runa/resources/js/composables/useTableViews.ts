import { computed, onScopeDispose, shallowRef, watch, type Ref } from 'vue';
import {
  defaultPreferenceStore,
  type PreferenceDefinition,
  type PreferenceStore,
} from '../lib/preferenceStore';
import {
  normalizeTableColumns,
  normalizeTableSnapshot,
  normalizeTableViews,
} from '../lib/tableViews';
import type { PreferenceScope } from '../runa/ui/preferences';
import type {
  TableColumn,
  TableColumnState,
  TableQuery,
  TableViewPreferences,
  TableViewSnapshot,
} from '../runa/ui/tables';

/** Account-backed when signed in, separate guest fallback otherwise. */
export function useTableViews<Row>(options: {
  scope: PreferenceScope;
  columns: () => readonly TableColumn<Row>[];
  query: Ref<TableQuery>;
  store?: PreferenceStore;
}) {
  const store = options.store ?? defaultPreferenceStore;
  const baseline = JSON.parse(
    JSON.stringify(options.query.value),
  ) as TableQuery;
  const empty = () => normalizeTableViews({}, options.columns(), baseline);
  const definition: PreferenceDefinition<TableViewPreferences> = {
    version: 2,
    defaultValue: empty(),
    validate: (value): value is TableViewPreferences => {
      try {
        return (
          JSON.stringify(value) ===
          JSON.stringify(
            normalizeTableViews(value, options.columns(), baseline),
          )
        );
      } catch {
        return false;
      }
    },
    migrate: (value, version) => {
      if (version !== 1) return undefined;
      const raw = value && typeof value === 'object' ? value : {};
      return normalizeTableViews(
        'current' in raw ? raw : { current: raw },
        options.columns(),
        baseline,
      );
    },
  };
  // Read permissively, then reconcile removed/new fields against current descriptors.
  const readDefinition = {
    ...definition,
    validate: (value: unknown): value is TableViewPreferences =>
      !!value &&
      typeof value === 'object' &&
      'current' in value &&
      'views' in value,
  };
  const state = shallowRef(
    normalizeTableViews(
      store.read(options.scope, 'table-views', readDefinition),
      options.columns(),
      baseline,
    ),
  );
  function copy<T>(value: T): T {
    return JSON.parse(JSON.stringify(value)) as T;
  }
  function apply(snapshot: TableViewSnapshot) {
    options.query.value = copy({ ...snapshot.query, page: 1 });
  }
  const preferred = state.value.views.find(
    (view) => view.id === state.value.defaultId,
  );
  if (preferred)
    state.value = {
      ...state.value,
      current: copy(preferred.snapshot),
      activeId: preferred.id,
    };
  apply(state.value.current);
  let syncing = false;
  const unsubscribe = store.subscribe?.(() => {
    syncing = true;
    state.value = normalizeTableViews(
      store.read(options.scope, 'table-views', readDefinition),
      options.columns(),
      baseline,
    );
    apply(state.value.current);
    syncing = false;
  });
  if (unsubscribe) onScopeDispose(unsubscribe);
  function commit(next: TableViewPreferences) {
    state.value = normalizeTableViews(next, options.columns(), baseline);
    if (!syncing)
      store.write(options.scope, 'table-views', definition, state.value);
  }
  commit(state.value);
  watch(
    options.query,
    (value) => {
      const current = normalizeTableSnapshot(
        { columns: state.value.current.columns, query: value },
        options.columns(),
        baseline,
      );
      commit({ ...state.value, current });
    },
    { deep: true, flush: 'sync' },
  );
  watch(
    options.columns,
    () => {
      commit(state.value);
      apply(state.value.current);
    },
    { deep: true },
  );
  function setColumns(columns: TableColumnState[]) {
    commit({
      ...state.value,
      current: {
        ...state.value.current,
        columns: normalizeTableColumns(
          columns,
          options.columns().map((c) => c.key),
        ),
      },
    });
  }
  function move(key: string, direction: -1 | 1) {
    const columns = copy(state.value.current.columns),
      index = columns.findIndex((c) => c.key === key),
      target = index + direction;
    if (index < 0 || target < 0 || target >= columns.length) return;
    [columns[index], columns[target]] = [columns[target]!, columns[index]!];
    setColumns(columns);
  }
  function reorder(key: string, before: string) {
    const columns = copy(state.value.current.columns),
      index = columns.findIndex((c) => c.key === key);
    if (index < 0 || key === before || !columns.some((c) => c.key === before))
      return;
    const [item] = columns.splice(index, 1);
    columns.splice(
      columns.findIndex((c) => c.key === before),
      0,
      item!,
    );
    setColumns(columns);
  }
  function validName(name: string, except?: string) {
    const value = name.trim();
    if (
      !value ||
      value.length > 80 ||
      state.value.views.some(
        (v) =>
          v.id !== except &&
          v.name.toLocaleLowerCase() === value.toLocaleLowerCase(),
      )
    )
      throw new Error('invalid_view_name');
    return value;
  }
  function create(name: string) {
    if (state.value.views.length >= 50) throw new Error('view_limit');
    const view = {
      id: Array.from(
        globalThis.crypto.getRandomValues(new Uint8Array(16)),
        (value) => value.toString(16).padStart(2, '0'),
      ).join(''),
      name: validName(name),
      snapshot: copy(state.value.current),
    };
    commit({
      ...state.value,
      views: [...state.value.views, view],
      activeId: view.id,
    });
  }
  function select(id: string | null) {
    if (id === null) {
      reset();
      return;
    }
    const view = state.value.views.find((v) => v.id === id);
    if (!view) return;
    commit({ ...state.value, activeId: id, current: copy(view.snapshot) });
    apply(state.value.current);
  }
  function update() {
    commit({
      ...state.value,
      views: state.value.views.map((view) =>
        view.id === state.value.activeId
          ? { ...view, snapshot: copy(state.value.current) }
          : view,
      ),
    });
  }
  function rename(name: string) {
    const value = validName(name, state.value.activeId ?? undefined);
    commit({
      ...state.value,
      views: state.value.views.map((view) =>
        view.id === state.value.activeId ? { ...view, name: value } : view,
      ),
    });
  }
  function remove() {
    const id = state.value.activeId;
    commit({
      ...state.value,
      activeId: null,
      defaultId: state.value.defaultId === id ? null : state.value.defaultId,
      views: state.value.views.filter((view) => view.id !== id),
    });
  }
  function setDefault(id: string | null) {
    commit({
      ...state.value,
      defaultId: state.value.views.some((v) => v.id === id) ? id : null,
    });
  }
  function reset() {
    commit({
      ...state.value,
      current: normalizeTableSnapshot(
        { query: baseline },
        options.columns(),
        baseline,
      ),
      activeId: null,
    });
    apply(state.value.current);
  }
  function selectDefault() {
    select(state.value.defaultId);
  }
  function resetColumns() {
    setColumns(options.columns().map((c) => ({ key: c.key, visible: true })));
  }
  const dirty = computed(() => {
    const view = state.value.views.find((v) => v.id === state.value.activeId);
    return (
      !!view &&
      JSON.stringify(view.snapshot) !== JSON.stringify(state.value.current)
    );
  });
  return {
    state: computed(() => state.value),
    columns: computed(() => state.value.current.columns),
    dirty,
    setColumns,
    move,
    reorder,
    create,
    select,
    selectDefault,
    update,
    rename,
    remove,
    setDefault,
    reset,
    resetColumns,
  };
}
export type TableViewsController = ReturnType<typeof useTableViews>;
