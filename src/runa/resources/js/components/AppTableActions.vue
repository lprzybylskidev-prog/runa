<script setup lang="ts" generic="Query">
import { computed, ref, watch } from 'vue';
import AppButton from './AppButton.vue';
import AppIcon from './AppIcon.vue';
import AppTooltip from './AppTooltip.vue';
import AppPopover from './AppPopover.vue';
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
    feedbackTarget?: string | undefined;
    filename?: string;
    english?: boolean;
  }>(),
  {
    feedbackTarget: undefined,
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
  <div class="min-w-0 space-y-3">
    <div class="space-y-2">
      <p class="text-xs font-semibold text-muted">
        {{ scopes.find((option) => option.value === scope)?.label
        }}<template v-if="scope === 'selected'">: {{ selected }}</template>
      </p>
      <div
        class="flex flex-wrap items-center gap-1"
        role="group"
        :aria-label="english ? 'Export and print' : 'Eksport i druk'"
      >
        <AppTooltip
          v-for="format in ['csv', 'xlsx', 'pdf'] as const"
          :key="format"
          :text="format.toUpperCase()"
        >
          <AppButton
            size="sm"
            icon-only
            :aria-label="format.toUpperCase()"
            :disabled="
              operation.busy.value ||
              !provider.capabilities.formats.includes(format) ||
              !provider.capabilities.scopes.includes(
                scope as 'all' | 'filtered' | 'selected',
              ) ||
              (scope === 'selected' && !selected)
            "
            @click="exportFile(format)"
          >
            <AppIcon
              :name="
                format === 'csv'
                  ? 'file-type-csv'
                  : format === 'xlsx'
                    ? 'file-type-xls'
                    : 'file-type-pdf'
              "
              :size="20"
            />
          </AppButton>
        </AppTooltip>
        <AppTooltip v-if="printAdapter" :text="english ? 'Print' : 'Drukuj'">
          <AppButton
            size="sm"
            icon-only
            :aria-label="english ? 'Print' : 'Drukuj'"
            :disabled="
              operation.busy.value || (scope === 'selected' && !selected)
            "
            @click="print"
            ><AppIcon name="printer" :size="20"
          /></AppButton>
        </AppTooltip>
        <AppPopover
          :label="english ? 'Export settings' : 'Ustawienia eksportu'"
          align="end"
        >
          <template #trigger="{ toggle, open, panelId }">
            <AppTooltip
              :text="english ? 'Export settings' : 'Ustawienia eksportu'"
            >
              <AppButton
                size="sm"
                icon-only
                variant="ghost"
                :aria-label="
                  english ? 'Export settings' : 'Ustawienia eksportu'
                "
                :aria-expanded="open"
                :aria-controls="panelId"
                aria-haspopup="dialog"
                :disabled="operation.busy.value"
                @click="toggle"
                ><AppIcon name="adjustments-horizontal" :size="20"
              /></AppButton>
            </AppTooltip>
          </template>
          <template #default="{ close }">
            <div class="space-y-3">
              <h3 class="font-semibold">
                {{ english ? 'Export settings' : 'Ustawienia eksportu' }}
              </h3>
              <AppSelect
                v-model="scope"
                size="sm"
                :locale="english ? 'en' : 'pl'"
                :label="english ? 'Export scope' : 'Zakres eksportu'"
                :options="scopes"
                :disabled="operation.busy.value"
              />
              <AppSelect
                v-model="orientation"
                size="sm"
                :locale="english ? 'en' : 'pl'"
                :label="english ? 'PDF orientation' : 'Orientacja PDF'"
                :options="[
                  {
                    value: 'portrait',
                    label: english ? 'Portrait' : 'Pionowa',
                  },
                  {
                    value: 'landscape',
                    label: english ? 'Landscape' : 'Pozioma',
                  },
                ]"
                :disabled="operation.busy.value"
              />
              <AppButton size="sm" @click="close()">{{
                english ? 'Done' : 'Gotowe'
              }}</AppButton>
            </div>
          </template>
        </AppPopover>
        <template v-if="selected > 0">
          <AppButton
            v-for="action in bulkProvider?.actions ?? []"
            :key="action.key"
            size="sm"
            :disabled="operation.busy.value"
            @click="bulk(action.key)"
            >{{ action.label }}</AppButton
          >
        </template>
      </div>
    </div>
    <Teleport :to="feedbackTarget ?? 'body'" :disabled="!feedbackTarget" defer>
      <AppOperationPanel :operation="operation" :english="english" />
    </Teleport>
  </div>
</template>
