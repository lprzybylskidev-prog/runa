<script setup lang="ts" generic="Query">
import { computed, ref, watch } from 'vue';
import AppButton from './AppButton.vue';
import AppSelect from './AppSelect.vue';
import AppOperationPanel from './AppOperationPanel.vue';
import { useOperation, operationFailure } from '../composables/useOperation';
import { downloadExport } from '../lib/exportFiles';
import { selectionCount } from '../lib/tableSelection';
import type {
  BulkProvider,
  ExportProvider,
  ExportFormat,
  ExportScope,
  PrintAdapter,
  RowSelection,
} from '../runa/ui/exports';
const props = withDefaults(
  defineProps<{
    provider: ExportProvider<Query>;
    printAdapter?: PrintAdapter<Query> | undefined;
    bulkProvider?: BulkProvider<Query> | undefined;
    query: Query;
    selection: RowSelection<Query>;
    columns: readonly { key: string; label: string }[];
    filename?: string;
    english?: boolean;
  }>(),
  {
    filename: 'export',
    english: false,
    printAdapter: undefined,
    bulkProvider: undefined,
  },
);
const scope = ref('filtered');
const orientation = ref('portrait');
const operation = useOperation();
watch(
  () => operation.status.value,
  (state) => {
    if (state?.state === 'completed' && state.download) {
      try {
        downloadExport(state.download);
      } catch (cause) {
        operation.status.value = {
          state: 'failed',
          error: operationFailure(cause),
        };
      }
    }
  },
);
watch(
  () => props.provider.capabilities.scopes,
  (values) => {
    if (!values.includes(scope.value as 'all' | 'filtered' | 'selected'))
      scope.value = values[0] ?? 'filtered';
  },
  { immediate: true },
);
const selected = computed(() => selectionCount(props.selection));
const scopes = computed(() =>
  props.provider.capabilities.scopes.map((value) => ({
    value,
    label:
      value === 'all'
        ? props.english
          ? 'All rows'
          : 'Wszystkie wiersze'
        : value === 'selected'
          ? props.english
            ? 'Selected rows'
            : 'Zaznaczone wiersze'
          : props.english
            ? 'Filtered results'
            : 'Przefiltrowane wyniki',
    disabled: value === 'selected' && selected.value === 0,
  })),
);
function snapshot(): ExportScope<Query> {
  const result: ExportScope<Query> =
    scope.value === 'selected'
      ? { kind: 'selected', selection: props.selection }
      : scope.value === 'all'
        ? { kind: 'all' }
        : { kind: 'filtered', query: props.query };
  return JSON.parse(JSON.stringify(result)) as ExportScope<Query>;
}
function key() {
  return Array.from(
    globalThis.crypto.getRandomValues(new Uint32Array(4)),
    (n) => n.toString(16).padStart(8, '0'),
  ).join('');
}
async function exportFile(format: ExportFormat) {
  const request = {
    format,
    scope: snapshot(),
    columnKeys: props.columns.map((c) => c.key),
    filename: props.filename,
    orientation:
      orientation.value === 'landscape'
        ? ('landscape' as const)
        : ('portrait' as const),
    operationKey: key(),
  };
  await operation.run(
    (signal) => props.provider.start(request, signal),
    true,
    props.provider.capabilities.execution === 'client',
  );
}
async function print() {
  const adapter = props.printAdapter,
    request = snapshot();
  if (adapter)
    await operation.run(async (signal) => {
      await adapter.print(request, signal);
      return { kind: 'status', status: { state: 'completed' } };
    });
}
async function bulk(action: string) {
  const provider = props.bulkProvider;
  const request = {
    action,
    selection: JSON.parse(
      JSON.stringify(props.selection),
    ) as RowSelection<Query>,
    operationKey: key(),
  };
  if (provider)
    await operation.run((signal) => provider.start(request, signal));
}
</script>
<template>
  <div class="space-y-3">
    <div class="flex flex-wrap items-end gap-2">
      <div class="w-56">
        <AppSelect
          v-model="scope"
          size="sm"
          :label="english ? 'Export scope' : 'Zakres eksportu'"
          :options="scopes"
          :disabled="operation.busy.value"
        />
      </div>
      <div class="w-40">
        <AppSelect
          v-model="orientation"
          size="sm"
          :label="english ? 'PDF orientation' : 'Orientacja PDF'"
          :options="[
            { value: 'portrait', label: english ? 'Portrait' : 'Pionowa' },
            { value: 'landscape', label: english ? 'Landscape' : 'Pozioma' },
          ]"
          :disabled="operation.busy.value"
        />
      </div>
      <AppButton
        v-for="format in ['csv', 'xlsx', 'pdf'] as const"
        :key="format"
        size="sm"
        :disabled="
          operation.busy.value ||
          !provider.capabilities.formats.includes(format) ||
          !provider.capabilities.scopes.includes(
            scope as 'all' | 'filtered' | 'selected',
          ) ||
          (scope === 'selected' && !selected)
        "
        @click="exportFile(format)"
        >{{ format.toUpperCase() }}</AppButton
      >
      <AppButton
        v-if="printAdapter"
        size="sm"
        :disabled="operation.busy.value || (scope === 'selected' && !selected)"
        @click="print"
        >{{ english ? 'Print' : 'Drukuj' }}</AppButton
      >
      <AppButton
        v-for="action in bulkProvider?.actions ?? []"
        :key="action.key"
        size="sm"
        :disabled="operation.busy.value || !selected"
        @click="bulk(action.key)"
        >{{ action.label }}</AppButton
      >
    </div>
    <AppOperationPanel :operation="operation" :english="english" />
  </div>
</template>
