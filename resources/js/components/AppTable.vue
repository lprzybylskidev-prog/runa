<script setup lang="ts" generic="Row">
import { computed, toRef, useSlots, ref, watch, nextTick } from 'vue';
import AppButton from './AppButton.vue';
import AppTextInput from './AppTextInput.vue';
import AppSelect from './AppSelect.vue';
import AppCheckbox from './AppCheckbox.vue';
import AppPagination from './AppPagination.vue';
import AppAsyncContent from './AppAsyncContent.vue';
import { normalizeTableColumns } from '../lib/tableViews';
import type { TableColumnState } from '../runa/ui/tables';
import { useTable } from '../composables/useTable';
import type { TableColumn, TableProvider, TableQuery } from '../runa/ui/tables';
const props = withDefaults(
  defineProps<{
    mode: 'client' | 'server';
    rows?: readonly Row[];
    provider?: TableProvider<Row> | undefined;
    columns: readonly TableColumn<Row>[];
    columnState?: readonly TableColumnState[] | undefined;
    rowKey: (row: Row) => string;
    query: TableQuery;
    caption: string;
    selected?: readonly string[];
    selectable?: boolean;
    english?: boolean;
    searchable?: boolean;
    pageSizes?: readonly number[];
  }>(),
  {
    columnState: undefined,
    rows: () => [],
    provider: undefined,
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
  'column-widths': [value: Record<string, number>];
}>();
const slots = useSlots();
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
const { page, state, loading, reload } = useTable(
  source,
  () => props.columns,
  toRef(props, 'query'),
);
const tableElement = ref<globalThis.HTMLTableElement>();
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
  page.value?.counts === 'exact' && page.value.matching !== null
    ? Math.max(1, Math.ceil(page.value.matching / props.query.perPage))
    : null,
);
const presentation = computed(() =>
  normalizeTableColumns(
    props.columnState,
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
function select(id: string, checked: boolean) {
  emit(
    'update:selected',
    checked
      ? [...new Set([...props.selected, id])]
      : props.selected.filter((value) => value !== id),
  );
}
function changePage(value: number) {
  emit('update:query', { ...props.query, page: value });
}
</script>
<template>
  <div class="min-w-0 rounded border border-line bg-surface text-sm">
    <div class="border-b border-line bg-[var(--runa-surface-raised)] px-4 py-3">
      <slot
        name="filters"
        :query="query"
        :update="
          (value: TableQuery) => emit('update:query', { ...value, page: 1 })
        "
      >
        <div class="flex flex-wrap items-end gap-3">
          <AppTextInput
            v-if="searchable"
            size="sm"
            :model-value="query.search ?? ''"
            :label="text('Wyszukaj', 'Search')"
            @update:model-value="
              emit('update:query', { ...query, page: 1, search: $event ?? '' })
            "
          />
          <AppSelect
            size="sm"
            :locale="english ? 'en' : 'pl'"
            :model-value="String(query.perPage)"
            :label="text('Na stronie', 'Page size')"
            :options="pageOptions"
            @update:model-value="
              emit('update:query', {
                ...query,
                page: 1,
                perPage: Number($event),
              })
            "
          />
        </div>
      </slot>
    </div>
    <div v-if="$slots.bulk" class="p-4">
      <slot name="bulk" :selected="selected" />
    </div>
    <div v-if="state !== 'success'" class="p-4">
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
      />
    </div>
    <div
      v-else
      role="region"
      :aria-label="caption"
      tabindex="0"
      class="relative overflow-x-auto focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
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
          class="border-b border-line bg-[var(--runa-surface-raised)] text-[var(--runa-text-secondary)]"
        >
          <tr>
            <th
              v-if="selectable"
              scope="col"
              class="w-12 px-3 py-1 align-middle"
            >
              <span class="sr-only">{{ text('Wybór', 'Selection') }}</span>
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
                  column.sortable &&
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
        </thead>
        <tbody>
          <tr
            v-for="row in page?.rows"
            :key="rowKey(row)"
            class="border-b border-line last:border-0 hover:bg-[var(--runa-surface-hover)]"
            :class="
              selected.includes(rowKey(row))
                ? 'bg-[var(--runa-primary-subtle)]'
                : ''
            "
          >
            <td v-if="selectable" class="px-3 py-1 align-middle">
              <AppCheckbox
                hide-label
                compact
                :model-value="selected.includes(rowKey(row))"
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
