import { computed, onScopeDispose, shallowRef, watch, type Ref } from 'vue';
import type {
  TableColumn,
  TablePage,
  TableProvider,
  TableQuery,
} from '../runa/ui/tables';
import { clientTablePage, normalizeTableQuery } from '../lib/table';

type Source<Row> =
  | { mode: 'client'; rows: readonly Row[] }
  | { mode: 'server'; provider: TableProvider<Row> };
export function useTable<Row>(
  source: () => Source<Row>,
  columns: () => readonly TableColumn<Row>[],
  query: Ref<TableQuery>,
) {
  const page = shallowRef<TablePage<Row> | null>(null);
  const error = shallowRef<unknown>(null);
  const loading = shallowRef(false);
  let controller: AbortController | undefined;
  let generation = 0;
  async function reload() {
    controller?.abort();
    const current = ++generation;
    controller = new AbortController();
    loading.value = true;
    error.value = null;
    page.value = null;
    const fields = columns();
    const state = normalizeTableQuery(
      query.value,
      fields.filter((c) => c.sortable).map((c) => c.key),
      fields.filter((c) => c.filter).map((c) => c.key),
    );
    try {
      const input = source();
      const result =
        input.mode === 'client'
          ? clientTablePage(input.rows, fields, state)
          : await input.provider(state, controller.signal);
      if (generation !== current) return;
      page.value = result;
    } catch (cause) {
      if (generation === current) error.value = cause;
    } finally {
      if (generation === current) loading.value = false;
    }
  }
  watch(
    [source, columns, query],
    () => {
      void reload();
    },
    { deep: true, immediate: true },
  );
  onScopeDispose(() => {
    generation++;
    controller?.abort();
  });
  return {
    page,
    error,
    loading,
    reload,
    state: computed(() =>
      loading.value
        ? 'loading'
        : error.value
          ? 'error'
          : !page.value?.rows.length
            ? 'empty'
            : 'success',
    ),
  };
}
