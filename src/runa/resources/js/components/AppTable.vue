<script setup lang="ts" generic="Row">
import {
  computed,
  toRef,
  useSlots,
  ref,
  watch,
  nextTick,
  useId,
  onScopeDispose,
} from 'vue';
import AppFormNotifications from './AppFormNotifications.vue';
import AppDateTimeInput from './AppDateTimeInput.vue';
import AppDateTimeRangeInput from './AppDateTimeRangeInput.vue';
import { isInstant } from '../lib/temporal';
import { useTableViewport } from '../composables/useTableViewport';
import AppTablePreferences from './AppTablePreferences.vue';
import AppTableActions from './AppTableActions.vue';
import { useTableViews } from '../composables/useTableViews';
import { createPreferenceStore } from '../lib/preferenceStore';
import { createClientExportProvider } from '../lib/clientExports';
import { createClientPrintAdapter } from '../lib/printTable';
import type {
  ExportColumn,
  ExportProvider,
  PrintAdapter,
} from '../runa/ui/exports';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';
import AppButton from './AppButton.vue';
import AppTextInput from './AppTextInput.vue';
import AppSelect from './AppSelect.vue';
import AppCheckbox from './AppCheckbox.vue';
import AppPagination from './AppPagination.vue';
import AppAsyncContent from './AppAsyncContent.vue';
import AppSpinner from './AppSpinner.vue';
import type { RowSelection } from '../runa/ui/exports';
import {
  selectionContains,
  selectionCount,
  toggleSelection,
  tableQueryIdentity,
} from '../lib/tableSelection';
import { normalizeTableColumns } from '../lib/tableViews';
import type { TableColumnState } from '../runa/ui/tables';
import { useTable } from '../composables/useTable';
import type { TableColumn, TableProvider, TableQuery } from '../runa/ui/tables';
const feedbackId = `table-feedback-${useId()}`;
const props = withDefaults(
  defineProps<{
    mode: 'client' | 'server';
    loading?: boolean;
    retainEmptyFrame?: boolean;
    refresh?: (() => void | Promise<void>) | undefined;
    refreshing?: boolean;
    refreshDisabled?: boolean;
    exportAllowed?: boolean;
    exportColumns?: readonly ExportColumn<Row>[] | undefined;
    exportRows?:
      ((signal: globalThis.AbortSignal) => Promise<readonly Row[]>) | undefined;
    rows?: readonly Row[];
    provider?: TableProvider<Row> | undefined;
    columns: readonly TableColumn<Row>[];
    columnState?: readonly TableColumnState[] | undefined;
    rowKey: (row: Row) => string;
    query: TableQuery;
    caption: string;
    selected?: readonly string[];
    selection?: RowSelection<TableQuery> | undefined;
    selectable?: boolean;
    english?: boolean;
    searchable?: boolean;
    pageSizes?: readonly number[];
  }>(),
  {
    loading: false,
    refresh: undefined,
    exportRows: undefined,
    exportColumns: undefined,
    refreshing: false,
    exportAllowed: false,
    refreshDisabled: false,
    retainEmptyFrame: false,
    columnState: undefined,
    rows: () => [],
    provider: undefined,
    selection: undefined,
    selected: () => [],
    selectable: false,
    english: false,
    searchable: true,
    pageSizes: () => [5, 10, 20, 50],
  },
);
const emit = defineEmits<{
  'update:query': [value: TableQuery];
  'update:selected': [value: string[]];
  'update:selection': [value: RowSelection<TableQuery>];
  'column-widths': [value: Record<string, number>];
}>();
const slots = useSlots();
const memory = new Map<string, string>();
let initializingViews = true;
const views = useTableViews({
  scope: { applicationId: 'runa', moduleKey: 'table', viewKey: 'current' },
  query: computed({
    get: () => props.query,
    set: (value) => {
      if (!initializingViews) emit('update:query', value);
    },
  }),
  columns: () => props.columns,
  store: createPreferenceStore({
    read: (key) => memory.get(key) ?? null,
    write: (key, value) => {
      memory.set(key, value);
    },
    remove: (key) => {
      memory.delete(key);
    },
  }),
});
initializingViews = false;
if (props.columnState) views.setColumns([...props.columnState]);
const exportColumns = computed(() =>
  visibleColumns.value.flatMap((column) => {
    if (props.exportColumns)
      return props.exportColumns.filter((item) => item.key === column.key);
    return [{ key: column.key, label: column.label, value: column.value }];
  }),
);
let exportEpoch = 0;
watch(
  () => [props.exportAllowed, props.exportRows, props.query],
  () => {
    exportEpoch++;
  },
  { deep: true, flush: 'sync' },
);
async function authorizedRows(signal: globalThis.AbortSignal) {
  if (!props.exportAllowed || !props.exportRows)
    throw new Error('permission_denied');
  const own = exportEpoch,
    read = props.exportRows;
  const rows = await read(signal);
  signal.throwIfAborted();
  if (!props.exportAllowed || read !== props.exportRows || own !== exportEpoch)
    throw new Error('selection_stale');
  return rows;
}
const builtExports = computed<ExportProvider<TableQuery> | undefined>(() =>
  props.exportAllowed && props.exportRows
    ? {
        capabilities: {
          execution: 'server',
          formats: ['csv', 'xlsx', 'pdf'],
          scopes: ['page'],
          cancellation: true,
        },
        async start(input, signal) {
          if (input.scope.kind !== 'page')
            throw new Error('unsupported_export');
          const stamp = exportEpoch;
          const rows = await authorizedRows(signal);
          const provider = createClientExportProvider({
            rows: () => rows,
            columns: () => exportColumns.value,
            tableColumns: () => props.columns,
            rowKey: props.rowKey,
          });
          const scope =
            props.mode === 'server' ? { kind: 'all' as const } : input.scope;
          const result = await provider.start({ ...input, scope }, signal);
          if (!props.exportAllowed || stamp !== exportEpoch)
            throw new Error('permission_denied');
          return result;
        },
      }
    : undefined,
);
const builtPrint = computed<PrintAdapter<TableQuery> | undefined>(() =>
  builtExports.value
    ? {
        async print(scope, signal) {
          if (scope.kind !== 'page') throw new Error('unsupported_export');
          const rows = await authorizedRows(signal);
          await createClientPrintAdapter({
            rows: () => rows,
            columns: () => exportColumns.value,
            tableColumns: () => props.columns,
            rowKey: props.rowKey,
            title: () => props.caption,
          }).print(props.mode === 'server' ? { kind: 'all' } : scope, signal);
        },
      }
    : undefined,
);

const refreshPending = ref(false),
  refreshError = ref('');
async function refreshTable() {
  if (refreshPending.value || props.refreshing || props.refreshDisabled) return;
  refreshPending.value = true;
  refreshError.value = '';
  try {
    await (props.refresh ? props.refresh() : reload());
  } catch {
    refreshError.value = text(
      'Nie udało się odświeżyć danych.',
      'Unable to refresh data.',
    );
  } finally {
    refreshPending.value = false;
  }
}
function filter(key: string, value: string | null) {
  const filters = { ...props.query.filters };
  if (value) filters[key] = value;
  else delete filters[key];
  emit('update:query', { ...props.query, page: 1, filters });
}
const filterColumns = computed(() =>
  props.columns
    .filter(
      (column) =>
        !props.columns.some(
          (start) => start.filterRange?.endKey === column.key,
        ),
    )
    .filter((column) => column.filter),
);
const text = (pl: string, en: string) => (props.english ? en : pl);
const source = () =>
  props.mode === 'client'
    ? { mode: 'client' as const, rows: props.rows }
    : {
        mode: 'server' as const,
        provider:
          props.provider ??
          (async () => {
            throw new Error('Server table requires a provider.');
          }),
      };
const {
  page,
  state: sourceState,
  loading: sourceLoading,
  reload,
} = useTable(source, () => props.columns, toRef(props, 'query'));
// Local calculations can finish within one tick. Keep their feedback paintable
// without delaying processing, issuing requests or changing the controlled query.
const queryFeedback = ref(false);
let focusedColumn: string | undefined;
let feedbackTimer: ReturnType<typeof globalThis.setTimeout> | undefined;
watch(
  () =>
    JSON.stringify([
      props.query.page,
      props.query.perPage,
      tableQueryIdentity(props.query),
    ]),
  () => {
    const active = document.activeElement;
    if (
      active instanceof globalThis.HTMLElement &&
      tableElement.value?.contains(active)
    )
      focusedColumn = active.closest<globalThis.HTMLElement>(
        'th[data-column-key]',
      )?.dataset.columnKey;
    globalThis.clearTimeout(feedbackTimer);
    queryFeedback.value = true;
    feedbackTimer = globalThis.setTimeout(() => {
      queryFeedback.value = false;
    }, 140);
  },
);
onScopeDispose(() => globalThis.clearTimeout(feedbackTimer));
const loading = computed(
  () => props.loading || sourceLoading.value || queryFeedback.value,
);
const state = computed(() => (loading.value ? 'loading' : sourceState.value));
const tableElement = ref<globalThis.HTMLTableElement>();
const {
  setRegion,
  setScrollbar,
  overflow,
  width: scrollWidth,
  headerHeight,
  railHeight,
  synchronize,
  scrollHeader,
} = useTableViewport(tableElement);
const filterReset = ref(0);
const invalidFilters = ref<Record<string, boolean>>({});
function resetFilters() {
  filterReset.value++;
  invalidFilters.value = {};
  emit('update:query', { ...props.query, page: 1, search: '', filters: {} });
}
watch(state, async (value) => {
  if (value === 'loading' || !focusedColumn) return;
  const key = focusedColumn;
  focusedColumn = undefined;
  await nextTick();
  if (document.activeElement !== document.body) return;
  const header = [
    ...(tableElement.value?.querySelectorAll<globalThis.HTMLElement>(
      'th[data-column-key]',
    ) ?? []),
  ].find((cell) => cell.dataset.columnKey === key);
  header?.querySelector<globalThis.HTMLButtonElement>('button')?.focus();
});
let previousWidths = '';
function measureColumns() {
  if (!tableElement.value) return;
  const widths = Object.fromEntries(
    [
      ...tableElement.value.querySelectorAll<globalThis.HTMLElement>(
        'th[data-column-key]',
      ),
    ].map((cell) => [
      cell.dataset.columnKey!,
      Math.round(cell.getBoundingClientRect().width),
    ]),
  );
  const signature = JSON.stringify(widths);
  if (signature !== previousWidths) {
    previousWidths = signature;
    emit('column-widths', widths);
  }
}
watch(tableElement, (table, _previous, cleanup) => {
  if (!table) return;
  const observer = new globalThis.ResizeObserver(measureColumns);
  observer.observe(table);
  cleanup(() => observer.disconnect());
  void nextTick(measureColumns);
});
const pageOptions = computed(() =>
  [...new Set([...props.pageSizes, props.query.perPage])]
    .filter((n) => Number.isInteger(n) && n > 0 && n <= 100)
    .map((n) => ({ value: String(n), label: String(n) })),
);
const pages = computed(() =>
  page.value?.pagination !== 'cursor' &&
  page.value?.counts === 'exact' &&
  page.value.matching !== null
    ? Math.max(1, Math.ceil(page.value.matching / props.query.perPage))
    : null,
);
const presentation = computed(() =>
  normalizeTableColumns(
    slots.toolbar ? props.columnState : views.columns.value,
    props.columns.map((c) => c.key),
  ),
);
const visibleColumns = computed(() =>
  presentation.value
    .filter((c) => c.visible)
    .flatMap((state) => {
      const column = props.columns.find((c) => c.key === state.key);
      return column ? [{ ...column, width: state.width }] : [];
    }),
);
watch(visibleColumns, () => {
  void nextTick(measureColumns);
});
const hasWidths = computed(() =>
  visibleColumns.value.some((c) => c.width !== undefined),
);
const tableStyle = computed(() =>
  hasWidths.value
    ? {
        tableLayout: 'fixed' as const,
        minWidth: `${visibleColumns.value.reduce((sum, c) => sum + (c.width ?? 160), (props.selectable ? 48 : 0) + (slots.actions ? 112 : 0))}px`,
      }
    : undefined,
);
function sort(key: string) {
  emit('update:query', {
    ...props.query,
    page: 1,
    sort:
      props.query.sort?.key === key && props.query.sort.direction === 'desc'
        ? null
        : { key, direction: props.query.sort?.key === key ? 'desc' : 'asc' },
  });
}
const effectiveSelection = computed<RowSelection<TableQuery>>(
  () => props.selection ?? { mode: 'explicit', publicIds: props.selected },
);
const selectedCount = computed(() => selectionCount(effectiveSelection.value));
const pageIds = computed(() => page.value?.rows.map(props.rowKey) ?? []);
const pageChecked = computed(() => {
  const count = pageIds.value.filter((id) =>
    selectionContains(effectiveSelection.value, id),
  ).length;
  return count === 0 ? false : count === pageIds.value.length ? true : null;
});
const selectionNotice = ref(false);
function updateSelection(value: RowSelection<TableQuery>) {
  emit('update:selection', value);
  if (value.mode === 'explicit') emit('update:selected', [...value.publicIds]);
}
function select(id: string, checked: boolean) {
  selectionNotice.value = false;
  updateSelection(toggleSelection(effectiveSelection.value, [id], checked));
}
function selectMatching() {
  if (page.value?.counts !== 'exact' || page.value.matching === null) return;
  selectionNotice.value = false;
  updateSelection({
    mode: 'all-matching',
    query: JSON.parse(JSON.stringify({ ...props.query, page: 1 })),
    matchingCount: page.value.matching,
    excludedPublicIds: [],
  });
}
watch(
  () => tableQueryIdentity(props.query),
  () => {
    if (effectiveSelection.value.mode === 'all-matching') {
      selectionNotice.value = true;
      updateSelection({ mode: 'explicit', publicIds: [] });
    }
  },
);
function changePage(value: number) {
  emit('update:query', { ...props.query, page: value });
}
</script>
<template>
  <div class="min-w-0 rounded border border-line bg-surface text-sm">
    <div
      v-if="filterColumns.length || searchable || $slots.filters"
      data-table-filters
      role="group"
      :aria-label="text('Filtry', 'Filters')"
      class="flex items-end gap-3 border-b border-line px-4 py-3"
    >
      <div class="min-w-0 flex-1">
        <slot
          name="filters"
          :query="query"
          :reset-key="filterReset"
          :set-validity="
            (key: string, valid: boolean) => {
              invalidFilters[key] = !valid;
            }
          "
          :update="
            (value: TableQuery) => emit('update:query', { ...value, page: 1 })
          "
        >
          <div
            class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
          >
            <template v-for="column in filterColumns" :key="column.key">
              <AppDateTimeRangeInput
                v-if="column.filterRange"
                :key="column.key + ':' + filterReset"
                size="sm"
                :label="column.filterRange.label"
                :locale="english ? 'en' : 'pl'"
                :model-value="{
                  start: query.filters[column.key] || null,
                  end: query.filters[column.filterRange.endKey] || null,
                }"
                @update:model-value="
                  (value) => {
                    const filters = { ...query.filters };
                    if (value.start) filters[column.key] = value.start;
                    else delete filters[column.key];
                    if (value.end)
                      filters[column.filterRange!.endKey] = value.end;
                    else delete filters[column.filterRange!.endKey];
                    emit('update:query', { ...query, page: 1, filters });
                  }
                "
                @validity="invalidFilters[column.key] = !$event"
              />
              <AppSelect
                v-else-if="column.filterOptions?.length"
                size="sm"
                clearable
                :locale="english ? 'en' : 'pl'"
                :label="column.label"
                :model-value="query.filters[column.key] ?? ''"
                :options="column.filterOptions"
                @update:model-value="filter(column.key, $event)"
              />
              <AppDateTimeInput
                v-else-if="column.filterType === 'datetime'"
                :key="`${column.key}-${filterReset}`"
                size="sm"
                :label="column.label"
                :locale="english ? 'en' : 'pl'"
                :invalid-message="
                  text(
                    'Wpisz poprawną datę i godzinę.',
                    'Enter a valid date and time.',
                  )
                "
                :model-value="
                  isInstant(query.filters[column.key] ?? '')
                    ? query.filters[column.key]!
                    : null
                "
                :error="
                  query.filters[column.key] &&
                  !isInstant(query.filters[column.key]!)
                    ? text(
                        'Wpisz poprawną datę i godzinę.',
                        'Enter a valid date and time.',
                      )
                    : ''
                "
                @validity="invalidFilters[column.key] = !$event"
                @update:model-value="filter(column.key, $event)"
              />
              <AppTextInput
                v-else
                size="sm"
                :label="column.label"
                :model-value="query.filters[column.key] ?? ''"
                @update:model-value="filter(column.key, $event)"
              />
            </template>
            <AppTextInput
              v-if="searchable"
              class="w-44 max-w-full"
              size="sm"
              :model-value="query.search ?? ''"
              :label="text('Wyszukaj', 'Search')"
              @update:model-value="
                emit('update:query', {
                  ...query,
                  page: 1,
                  search: $event ?? '',
                })
              "
            />
          </div>
        </slot>
      </div>
      <AppTooltip :text="text('Resetuj filtry', 'Reset filters')">
        <AppButton
          size="sm"
          icon-only
          variant="ghost"
          :aria-label="text('Resetuj filtry', 'Reset filters')"
          :disabled="
            !query.search &&
            !Object.values(query.filters).some(Boolean) &&
            !Object.values(invalidFilters).some(Boolean)
          "
          @click="resetFilters"
        >
          <AppIcon name="filter-off" :size="20" />
        </AppButton>
      </AppTooltip>
    </div>
    <slot name="afterFilters" />
    <div
      data-table-toolbar
      class="flex flex-wrap items-end justify-between gap-3 border-b border-line bg-[var(--runa-surface-raised)] px-4 py-3"
    >
      <AppSelect
        size="sm"
        :locale="english ? 'en' : 'pl'"
        :model-value="String(query.perPage)"
        :label="text('Na stronie', 'Page size')"
        :options="pageOptions"
        @update:model-value="
          emit('update:query', { ...query, page: 1, perPage: Number($event) })
        "
      />
      <div class="ml-auto flex min-w-0 flex-wrap items-end justify-end gap-2">
        <slot name="toolbar" :feedback-target="`#${feedbackId}`">
          <AppTablePreferences
            :controller="views"
            :columns="columns"
            :english="english"
          />
        </slot>
        <AppTableActions
          v-if="builtExports"
          :provider="builtExports"
          :print-adapter="builtPrint"
          :query="query"
          :selection="effectiveSelection"
          :columns="exportColumns"
          :filename="caption"
          :feedback-target="`#${feedbackId}`"
          :english="english"
        />
        <AppTooltip :text="text('Odśwież', 'Refresh')">
          <AppButton
            size="sm"
            icon-only
            variant="ghost"
            :aria-label="text('Odśwież', 'Refresh')"
            :disabled="
              loading || refreshing || refreshPending || refreshDisabled
            "
            @click="refreshTable"
          >
            <AppIcon
              name="refresh"
              :size="20"
              :class="{
                'animate-spin motion-reduce:animate-none':
                  refreshing || refreshPending,
              }"
            />
          </AppButton>
        </AppTooltip>
      </div>
    </div>
    <div
      v-if="
        selectable &&
        selection !== undefined &&
        (selectedCount > 0 || selectionNotice)
      "
      class="flex flex-wrap items-center gap-2 border-b border-line px-4 py-2 text-xs"
    >
      <template v-if="selectedCount > 0">
        <span role="status"
          >{{ text('Wybrane', 'Selected') }}: {{ selectedCount }}</span
        >
        <AppButton
          size="sm"
          :disabled="loading || page?.counts !== 'exact' || !page?.matching"
          @click="selectMatching"
          >{{
            text('Zaznacz wszystkie wyniki', 'Select all matching')
          }}</AppButton
        >
        <AppButton
          size="sm"
          :disabled="!selectedCount"
          @click="updateSelection({ mode: 'explicit', publicIds: [] })"
          >{{ text('Wyczyść wybór', 'Clear selection') }}</AppButton
        >
      </template>
      <span v-if="selectionNotice" role="status" class="text-muted">{{
        text(
          'Wybór wszystkich wyników wyczyszczono po zmianie zapytania.',
          'All-matching selection cleared after the query changed.',
        )
      }}</span>
    </div>
    <div :id="feedbackId" class="border-b border-line px-4 py-3 empty:hidden">
      <AppFormNotifications
        v-if="refreshError"
        :locale="english ? 'en' : 'pl'"
        :error="refreshError"
      />
    </div>
    <div v-if="$slots.bulk" class="p-4">
      <slot
        name="bulk"
        :selected="selected"
        :selection="effectiveSelection"
        :count="selectedCount"
      />
    </div>
    <div
      v-if="state !== 'success' && !(retainEmptyFrame && state === 'empty')"
      class="p-4"
    >
      <AppAsyncContent
        action-size="sm"
        :state="state"
        :loading-label="text('Ładowanie danych', 'Loading data')"
        :error-title="
          text('Nie udało się pobrać danych', 'Unable to load data')
        "
        :empty-title="text('Brak wyników', 'No results')"
        :retry-label="text('Spróbuj ponownie', 'Retry')"
        @retry="reload"
      >
        <template #loading>
          <div
            class="flex min-h-32 items-center justify-center py-6 text-muted"
            data-table-loading
          >
            <AppSpinner :label="text('Ładowanie danych', 'Loading data')" />
          </div>
        </template>
      </AppAsyncContent>
    </div>
    <div v-else class="relative" data-table-viewport>
      <div
        :ref="setRegion"
        role="region"
        :aria-label="caption"
        tabindex="0"
        class="table-scroll-region relative overflow-x-auto focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
        @scroll="synchronize('table')"
      >
        <table
          ref="tableElement"
          class="w-full border-collapse text-left"
          :style="tableStyle"
        >
          <colgroup>
            <col v-if="selectable" :style="{ width: '48px' }" />
            <col
              v-for="column in visibleColumns"
              :key="column.key"
              :style="
                hasWidths ? { width: `${column.width ?? 160}px` } : undefined
              "
            />
            <col v-if="$slots.actions" :style="{ width: '112px' }" />
          </colgroup>
          <caption class="sr-only">
            {{
              caption
            }}
          </caption>
          <thead
            class="table-pinned-header relative z-10 border-b border-line bg-[var(--runa-surface-raised)] text-[var(--runa-text-secondary)]"
            @wheel="scrollHeader"
          >
            <tr v-if="overflow" aria-hidden="true">
              <td
                :colspan="
                  visibleColumns.length +
                  Number(selectable) +
                  Number(!!$slots.actions)
                "
                :style="{ height: `${railHeight}px`, padding: 0 }"
              />
            </tr>
            <tr>
              <th
                v-if="selectable"
                scope="col"
                class="w-12 px-3 py-1 align-middle"
              >
                <AppCheckbox
                  hide-label
                  compact
                  :model-value="pageChecked"
                  :label="text('Zaznacz stronę', 'Select page')"
                  @update:model-value="
                    updateSelection(
                      toggleSelection(effectiveSelection, pageIds, $event),
                    )
                  "
                />
              </th>
              <th
                v-for="column in visibleColumns"
                :key="column.key"
                scope="col"
                :data-column-key="column.key"
                class="px-4 py-1 align-middle whitespace-nowrap"
                :aria-sort="
                  query.sort?.key === column.key
                    ? query.sort.direction === 'asc'
                      ? 'ascending'
                      : 'descending'
                    : undefined
                "
              >
                <AppButton
                  v-if="
                    column.sortable !== false &&
                    page?.capabilities.sort.includes(column.key)
                  "
                  variant="ghost"
                  size="sm"
                  class="-ml-[13px]"
                  @click="sort(column.key)"
                  >{{ column.label }}
                  <span aria-hidden="true">{{
                    query.sort?.key === column.key
                      ? query.sort.direction === 'asc'
                        ? '↑'
                        : '↓'
                      : '↕'
                  }}</span></AppButton
                >
                <template v-else>{{ column.label }}</template>
              </th>
              <th
                v-if="$slots.actions"
                scope="col"
                class="px-4 py-1 align-middle"
              >
                <span class="sr-only">{{ text('Działania', 'Actions') }}</span>
              </th>
            </tr>
            <tr v-if="overflow" aria-hidden="true">
              <td
                :colspan="
                  visibleColumns.length +
                  Number(selectable) +
                  Number(!!$slots.actions)
                "
                :style="{ height: `${railHeight}px`, padding: 0 }"
              />
            </tr>
          </thead>
          <tbody>
            <tr v-if="state === 'empty' && retainEmptyFrame">
              <td
                :colspan="
                  Math.max(
                    1,
                    visibleColumns.length +
                      (selectable ? 1 : 0) +
                      ($slots.actions ? 1 : 0),
                  )
                "
                class="px-4 py-8 text-center text-muted"
              >
                <span role="status">{{
                  text('Brak wyników', 'No results')
                }}</span>
              </td>
            </tr>
            <tr
              v-for="row in page?.rows"
              :key="rowKey(row)"
              class="border-b border-line transition-colors duration-160 motion-reduce:transition-none last:border-0 hover:bg-[var(--runa-surface-hover)]"
              :class="
                selectionContains(effectiveSelection, rowKey(row))
                  ? 'bg-[var(--runa-primary-subtle)]'
                  : ''
              "
            >
              <td v-if="selectable" class="px-3 py-1 align-middle">
                <AppCheckbox
                  hide-label
                  compact
                  :model-value="
                    selectionContains(effectiveSelection, rowKey(row))
                  "
                  :label="`${text('Wybierz', 'Select')} ${rowKey(row)}`"
                  @update:model-value="select(rowKey(row), $event)"
                />
              </td>
              <td
                v-for="column in visibleColumns"
                :key="column.key"
                class="px-4 py-1 align-middle break-words"
              >
                <slot
                  :name="`cell-${column.key}`"
                  :row="row"
                  :value="column.value(row)"
                  >{{ column.value(row) ?? '—' }}</slot
                >
              </td>
              <td v-if="$slots.actions" class="px-4 py-1 align-middle">
                <slot name="actions" :row="row" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div
        v-show="overflow"
        :ref="setScrollbar"
        role="region"
        :aria-label="
          text('Przewijanie poziome', 'Horizontal scrolling') + ': ' + caption
        "
        tabindex="0"
        class="table-pinned-rail absolute inset-x-0 z-10 overflow-x-scroll bg-[var(--runa-surface-raised)] focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
        :style="{
          top: `${headerHeight - railHeight}px`,
          height: `${railHeight}px`,
        }"
        @scroll="synchronize('rail')"
        @wheel="scrollHeader"
      >
        <div :style="{ width: `${scrollWidth}px`, height: '1px' }" />
      </div>
    </div>
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-[var(--runa-surface-raised)] px-4 py-3"
    >
      <span role="status" class="text-muted"
        >{{ text('Wyniki', 'Results') }}: {{ page?.matching ?? '—'
        }}<template v-if="page && page.counts !== 'exact'">
          · {{ text('Liczba niepotwierdzona', 'Count unconfirmed') }}</template
        ></span
      >
      <AppPagination
        v-if="pages !== null"
        size="sm"
        :model-value="query.page"
        :pages="Math.max(pages, query.page)"
        :label="text('Strona', 'Page')"
        :previous-label="text('Poprzednia', 'Previous')"
        :next-label="text('Następna', 'Next')"
        :disabled="loading"
        @update:model-value="changePage"
      />
      <div v-else class="flex items-center gap-2">
        <AppButton
          size="sm"
          :disabled="loading || query.page <= 1"
          @click="changePage(query.page - 1)"
          >{{ text('Poprzednia', 'Previous') }}</AppButton
        ><span>{{ query.page }}</span
        ><AppButton
          size="sm"
          :disabled="loading || !page?.hasNext"
          @click="changePage(query.page + 1)"
          >{{ text('Następna', 'Next') }}</AppButton
        >
      </div>
    </div>
  </div>
</template>

<style scoped>
.table-pinned-header,
.table-pinned-rail {
  will-change: transform;
}
.table-scroll-region {
  scrollbar-width: none;
}
.table-scroll-region::-webkit-scrollbar {
  display: none;
}
</style>
