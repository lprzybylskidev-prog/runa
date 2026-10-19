<script setup lang="ts" generic="Row">
import { computed, toRef } from 'vue';
import AppButton from './AppButton.vue';
import AppCheckbox from './AppCheckbox.vue';
import AppPagination from './AppPagination.vue';
import AppAsyncContent from './AppAsyncContent.vue';
import { useTable } from '../composables/useTable';
import type { TableColumn, TableProvider, TableQuery } from '../runa/ui/tables';
const props = withDefaults(
  defineProps<{
    mode: 'client' | 'server';
    rows?: readonly Row[];
    provider?: TableProvider<Row> | undefined;
    columns: readonly TableColumn<Row>[];
    rowKey: (row: Row) => string;
    query: TableQuery;
    caption: string;
    selected?: readonly string[];
    selectable?: boolean;
    english?: boolean;
  }>(),
  {
    rows: () => [],
    provider: undefined,
    selected: () => [],
    selectable: false,
    english: false,
  },
);
const emit = defineEmits<{
  'update:query': [value: TableQuery];
  'update:selected': [value: string[]];
}>();
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
const pages = computed(() =>
  page.value?.counts === 'exact' && page.value.matching !== null
    ? Math.max(1, Math.ceil(page.value.matching / props.query.perPage))
    : null,
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
    <div
      v-if="$slots.filters"
      class="border-b border-line bg-[var(--runa-surface-raised)] p-4"
    >
      <slot
        name="filters"
        :query="query"
        :update="
          (value: TableQuery) => emit('update:query', { ...value, page: 1 })
        "
      />
    </div>
    <div v-if="$slots.bulk" class="p-4">
      <slot name="bulk" :selected="selected" />
    </div>
    <div v-if="state !== 'success'" class="p-4">
      <AppAsyncContent
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
      <table class="w-full border-collapse text-left">
        <caption class="sr-only">
          {{
            caption
          }}
        </caption>
        <thead
          class="border-b border-line bg-[var(--runa-surface-raised)] text-[var(--runa-text-secondary)]"
        >
          <tr>
            <th v-if="selectable" scope="col" class="w-12 p-3">
              <span class="sr-only">{{ text('Wybór', 'Selection') }}</span>
            </th>
            <th
              v-for="column in columns"
              :key="column.key"
              scope="col"
              class="px-4 py-3 whitespace-nowrap"
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
            <th v-if="$slots.actions" scope="col" class="px-4 py-3">
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
            <td v-if="selectable" class="p-3">
              <AppCheckbox
                hide-label
                :model-value="selected.includes(rowKey(row))"
                :label="`${text('Wybierz', 'Select')} ${rowKey(row)}`"
                @update:model-value="select(rowKey(row), $event)"
              />
            </td>
            <td
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 align-top"
            >
              <slot
                :name="`cell-${column.key}`"
                :row="row"
                :value="column.value(row)"
                >{{ column.value(row) ?? '—' }}</slot
              >
            </td>
            <td v-if="$slots.actions" class="px-4 py-3">
              <slot name="actions" :row="row" />
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div
      class="flex flex-wrap items-center justify-between gap-3 border-t border-line bg-[var(--runa-surface-raised)] p-4"
    >
      <span role="status" class="text-muted"
        >{{ text('Wyniki', 'Results') }}: {{ page?.matching ?? '—'
        }}<template v-if="page && page.counts !== 'exact'">
          · {{ text('Liczba niepotwierdzona', 'Count unconfirmed') }}</template
        ></span
      >
      <AppPagination
        v-if="pages !== null"
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
          :disabled="loading || query.page <= 1"
          @click="changePage(query.page - 1)"
          >{{ text('Poprzednia', 'Previous') }}</AppButton
        ><span>{{ query.page }}</span
        ><AppButton
          :disabled="loading || !page?.hasNext"
          @click="changePage(query.page + 1)"
          >{{ text('Następna', 'Next') }}</AppButton
        >
      </div>
    </div>
  </div>
</template>
