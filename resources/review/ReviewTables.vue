<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Table,
  TablePreferences,
  useTableViews,
  Card,
  Button,
  TextInput,
  Select,
  Checkbox,
  clientTablePage,
  type TableColumn,
  type TableProvider,
  type TableQuery,
} from '@/runa/ui';
const props = defineProps<{ english: boolean }>();
const text = (pl: string, en: string) => (props.english ? en : pl);
interface RecordRow {
  public_id: string;
  name: string;
  city: string;
  count: number;
}
const rows: RecordRow[] = Array.from({ length: 43 }, (_, i) => ({
  public_id: `019a0000-0000-7000-8000-${String(i + 1).padStart(12, '0')}`,
  name: ['Atlas', 'Aurora', 'Orion', 'Vega'][i % 4] + ` ${i + 1}`,
  city: ['Warszawa', 'Gdańsk', 'Poznań'][i % 3]!,
  count: i * 7,
}));
const columns = computed<TableColumn<RecordRow>[]>(() => [
  {
    key: 'name',
    label: text('Nazwa', 'Name'),
    value: (row) => row.name,
    sortable: true,
    filter: (row, value) =>
      row.name.toLowerCase().includes(value.toLowerCase()),
  },
  {
    key: 'city',
    label: text('Miasto', 'City'),
    value: (row) => row.city,
    sortable: true,
  },
  {
    key: 'count',
    label: text('Dokumenty', 'Documents'),
    value: (row) => row.count,
    sortable: true,
    compare: (a, b) => a.count - b.count,
  },
]);
const initial = (): TableQuery => ({
  page: 1,
  perPage: 5,
  sort: null,
  filters: {},
});
const localQuery = ref(initial()),
  serverQuery = ref(initial());
const selected = ref<string[]>([]),
  serverSelected = ref<string[]>([]);
const localViews = useTableViews({
  scope: { applicationId: 'review', moduleKey: 'ui', viewKey: 'client-table' },
  columns: () => columns.value,
  query: localQuery,
});
const serverViews = useTableViews({
  scope: { applicationId: 'review', moduleKey: 'ui', viewKey: 'server-table' },
  columns: () => columns.value,
  query: serverQuery,
});
const fail = ref(false),
  unknown = ref(false),
  result = ref('');
const provider: TableProvider<RecordRow> = async (query, signal) => {
  await new Promise<void>((resolve, reject) => {
    const timer = globalThis.setTimeout(resolve, 450);
    signal.addEventListener(
      'abort',
      () => {
        globalThis.clearTimeout(timer);
        reject(new globalThis.DOMException('Aborted', 'AbortError'));
      },
      { once: true },
    );
  });
  if (fail.value) throw new Error('Synthetic provider failure');
  const page = clientTablePage(rows, columns.value, query);
  return unknown.value
    ? { ...page, total: null, matching: null, counts: 'unknown' }
    : page;
};
</script>
<template>
  <div class="space-y-6">
    <Card v-for="mode in ['client', 'server'] as const" :key="mode">
      <template #header>{{
        mode === 'client'
          ? text('Tabela lokalna', 'Client table')
          : text('Tabela serwerowa', 'Server table')
      }}</template>
      <p class="mb-4 text-sm text-muted">
        {{
          mode === 'client'
            ? text(
                '43 rekordy w pamięci przeglądarki.',
                '43 records in browser memory.',
              )
            : text(
                'Deterministyczny provider testowy: opóźnienie 450 ms, pobierana tylko bieżąca strona.',
                'Deterministic test provider: 450 ms delay, only the current page returned.',
              )
        }}
      </p>
      <div v-if="mode === 'server'" class="mb-4 flex flex-wrap gap-4">
        <Checkbox
          v-model="fail"
          :label="text('Błąd pobierania', 'Request failure')"
        /><Checkbox
          v-model="unknown"
          :label="text('Nieznana liczba wyników', 'Unknown count')"
        />
      </div>
      <div class="mb-4">
        <TablePreferences
          :controller="mode === 'client' ? localViews : serverViews"
          :columns="columns"
          :english="english"
        />
      </div>
      <Table
        :column-state="
          (mode === 'client' ? localViews : serverViews).columns.value
        "
        :mode="mode"
        :rows="mode === 'client' ? rows : []"
        :provider="provider"
        :columns="columns"
        :row-key="(row) => row.public_id"
        :query="mode === 'client' ? localQuery : serverQuery"
        :selected="mode === 'client' ? selected : serverSelected"
        selectable
        :caption="text('Projekty', 'Projects')"
        :english="english"
        @update:query="
          mode === 'client' ? (localQuery = $event) : (serverQuery = $event)
        "
        @update:selected="
          mode === 'client' ? (selected = $event) : (serverSelected = $event)
        "
      >
        <template #filters="{ query, update }"
          ><div class="flex flex-wrap items-end gap-4">
            <TextInput
              :model-value="query.filters.name ?? ''"
              :label="text('Nazwa projektu', 'Project name')"
              @update:model-value="
                update({ ...query, filters: { name: $event ?? '' } })
              "
            /><Select
              :model-value="String(query.perPage)"
              :label="text('Na stronie', 'Page size')"
              :options="
                [5, 10, 20].map((n) => ({ value: String(n), label: String(n) }))
              "
              @update:model-value="
                update({ ...query, perPage: Number($event) })
              "
            /></div
        ></template>
        <template #actions="{ row }"
          ><Button size="sm" variant="ghost" @click="result = row.name">{{
            text('Otwórz', 'Open')
          }}</Button></template
        >
      </Table>
    </Card>
    <p role="status" class="text-sm text-muted">
      {{ text('Wybrane', 'Selected') }}:
      {{ selected.length + serverSelected.length }} · {{ result }}
    </p>
  </div>
</template>
