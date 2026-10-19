<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Table,
  TableActions,
  createClientExportProvider,
  createClientPrintAdapter,
  selectionCount,
  type ExportColumn,
  type ExportProvider,
  type RowSelection,
  type BulkProvider,
  TablePreferences,
  useTableViews,
  Card,
  Button,
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
const selected = ref<RowSelection<TableQuery>>({
    mode: 'explicit',
    publicIds: [],
  }),
  serverSelected = ref<RowSelection<TableQuery>>({
    mode: 'explicit',
    publicIds: [],
  });
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
const localWidths = ref<Record<string, number>>({}),
  serverWidths = ref<Record<string, number>>({});
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

const operationOutcome = ref(false);
function exportColumns(mode: 'client' | 'server'): ExportColumn<RecordRow>[] {
  return (mode === 'client' ? localViews : serverViews).columns.value
    .filter((c) => c.visible)
    .map((c) => {
      const column = columns.value.find((item) => item.key === c.key)!;
      return {
        key: column.key,
        label: column.label,
        value: column.value,
        ...(c.key === 'count' ? { format: 'number' as const } : {}),
      };
    });
}
const localExport = createClientExportProvider({
  rows: () => rows,
  columns: () => exportColumns('client'),
  tableColumns: () => columns.value,
  rowKey: (row) => row.public_id,
});
const fixtureExport = createClientExportProvider({
  rows: () => rows,
  columns: () => exportColumns('server'),
  tableColumns: () => columns.value,
  rowKey: (row) => row.public_id,
});
// This temporary simulator owns its synthetic dataset. Production uses API providers.
const serverExport: ExportProvider<TableQuery> = {
  capabilities: { ...fixtureExport.capabilities, execution: 'server' },
  async start(request) {
    let tick = 0,
      cancelled = false,
      partial = operationOutcome.value;
    const operation = {
      publicId: 'review-operation',
      async poll(signal: globalThis.AbortSignal) {
        signal.throwIfAborted();
        if (cancelled) return { state: 'cancelled' as const };
        if (++tick < 4)
          return { state: 'running' as const, progress: tick * 25 };
        if (partial)
          return {
            state: 'partial' as const,
            succeeded: 2,
            failed: 1,
            error: { code: 'fixture_partial', message: '', retryable: true },
          };
        const result = await fixtureExport.start(request, signal);
        if (result.kind !== 'download') throw new Error('Invalid fixture');
        return { state: 'completed' as const, download: result.download };
      },
      async cancel() {
        cancelled = true;
      },
      async retry() {
        partial = false;
        tick = 0;
        return { kind: 'operation' as const, operation };
      },
    };
    return { kind: 'operation', operation };
  },
};
const bulkProvider: BulkProvider<TableQuery> = {
  get actions() {
    return [{ key: 'review', label: text('Test operacji', 'Test operation') }];
  },
  cancellation: true,
  async start(request) {
    let tick = 0,
      cancelled = false;
    const operation = {
      publicId: 'review-bulk',
      async poll() {
        return cancelled
          ? { state: 'cancelled' as const }
          : ++tick < 4
            ? { state: 'running' as const, progress: tick * 25 }
            : {
                state: 'completed' as const,
                succeeded: selectionCount(request.selection),
              };
      },
      async cancel() {
        cancelled = true;
      },
    };
    return { kind: 'operation', operation };
  },
};
const printAdapters = {
  client: createClientPrintAdapter({
    rows: () => rows,
    columns: () => exportColumns('client'),
    tableColumns: () => columns.value,
    rowKey: (row) => row.public_id,
    title: () => text('Projekty', 'Projects'),
  }),
  server: createClientPrintAdapter({
    rows: () => rows,
    columns: () => exportColumns('server'),
    tableColumns: () => columns.value,
    rowKey: (row) => row.public_id,
    title: () => text('Projekty — dane testowe', 'Projects — test data'),
  }),
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
          v-model="operationOutcome"
          :label="text('Częściowy wynik eksportu', 'Partial export result')"
        />
        <Checkbox
          v-model="fail"
          :label="text('Błąd pobierania', 'Request failure')"
        /><Checkbox
          v-model="unknown"
          :label="text('Nieznana liczba wyników', 'Unknown count')"
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
        :selection="mode === 'client' ? selected : serverSelected"
        :page-sizes="[5, 10, 20]"
        selectable
        :caption="text('Projekty', 'Projects')"
        :english="english"
        @column-widths="
          mode === 'client' ? (localWidths = $event) : (serverWidths = $event)
        "
        @update:query="
          mode === 'client' ? (localQuery = $event) : (serverQuery = $event)
        "
        @update:selection="
          mode === 'client' ? (selected = $event) : (serverSelected = $event)
        "
      >
        <template #toolbar="{ feedbackTarget }">
          <div class="flex min-w-0 flex-wrap items-end gap-3">
            <TablePreferences
              :controller="mode === 'client' ? localViews : serverViews"
              :columns="columns"
              :english="english"
              :measured-widths="mode === 'client' ? localWidths : serverWidths"
            />

            <TableActions
              :feedback-target="feedbackTarget"
              :provider="mode === 'client' ? localExport : serverExport"
              :print-adapter="printAdapters[mode]"
              :bulk-provider="bulkProvider"
              :query="mode === 'client' ? localQuery : serverQuery"
              :selection="mode === 'client' ? selected : serverSelected"
              :columns="exportColumns(mode)"
              :english="english"
              filename="projekty"
            />
          </div>
        </template>
        <template #actions="{ row }"
          ><Button size="sm" variant="ghost" @click="result = row.name">{{
            text('Otwórz', 'Open')
          }}</Button></template
        >
      </Table>
    </Card>
    <p role="status" class="text-sm text-muted">
      {{ text('Wybrane', 'Selected') }}:
      {{ selectionCount(selected) + selectionCount(serverSelected) }} ·
      {{ result }}
    </p>
  </div>
</template>
