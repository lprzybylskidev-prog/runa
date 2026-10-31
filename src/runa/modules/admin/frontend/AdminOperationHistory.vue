<script setup lang="ts">
import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { Alert, Badge, Button, Table } from '@/runa/ui';
import type { TableColumn, TableQuery } from '@/runa/ui';
import type {
  AdminOperationCommand,
  AdminOperationData,
  AdminOperationsController,
} from './useAdminOperations';
import { useAdminLocale } from './localization';
import AdminOperationResult from './AdminOperationResult.vue';
const props = defineProps<{
  controller: AdminOperationsController;
  module: string;
  pending?: boolean;
}>();
const emit = defineEmits<{
  execute: [command: AdminOperationCommand, id: string];
}>();
const { locale, t: tr } = useAdminLocale();
interface HistoryRow {
  public_id: string;
  target: string;
  intent: string;
  status: string;
  created_at: string;
  updated_at: string;
  actions: string[];
}
const query = ref<TableQuery>({
  page: 1,
  perPage: 10,
  sort: null,
  filters: {},
});
const rows = shallowRef<HistoryRow[]>([]);
const cursor = ref<string | null>(null);
const loaded = ref(false);
const loading = ref(false),
  error = ref(''),
  needsRead = ref(true);
const result = shallowRef<AdminOperationData>();
let epoch = 0,
  alive = true;
function date(value: string) {
  const date = new Date(value);
  return Number.isNaN(date.getTime())
    ? '—'
    : date.toLocaleString(locale.value === 'pl' ? 'pl-PL' : 'en-GB');
}
const columns = computed<TableColumn<HistoryRow>[]>(() => [
  {
    key: 'created_at',
    label: tr('Started'),
    value: (row) => date(row.created_at),
    compare: (a, b) => Date.parse(a.created_at) - Date.parse(b.created_at),
  },
  {
    key: 'intent',
    label: tr('Operation'),
    value: (row) =>
      tr(
        (
          {
            enable: 'Module enabled',
            disable: 'Module disabled',
            unregister: 'Module unregistered',
            settings: 'Settings change',
          } as Record<string, string>
        )[row.intent] ?? row.intent,
      ),
  },
  { key: 'status', label: tr('Status'), value: (row) => tr(row.status) },
  {
    key: 'updated_at',
    label: tr('Last change'),
    value: (row) => date(row.updated_at),
    compare: (a, b) => Date.parse(a.updated_at) - Date.parse(b.updated_at),
  },
]);
const columnState = [
  { key: 'created_at', visible: true, width: 200 },
  { key: 'intent', visible: true, width: 180 },
  { key: 'status', visible: true, width: 160 },
  { key: 'updated_at', visible: true, width: 200 },
];
const commands = [
  'modules.resume',
  'modules.cancel',
  'settings.recover',
] as const;
function actions(row: HistoryRow) {
  return commands.filter((command) => row.actions.includes(command));
}
function actionName(command: AdminOperationCommand) {
  return tr(
    command === 'modules.resume'
      ? 'Resume native workflow'
      : command === 'modules.cancel'
        ? 'Cancel and compensate'
        : 'Recover settings',
  );
}
function tone(status: string) {
  return status === 'succeeded'
    ? 'success'
    : status === 'failed' || status === 'refused'
      ? 'danger'
      : status === 'recovery_required'
        ? 'warning'
        : 'info';
}
function decode(value: unknown): HistoryRow {
  if (!value || typeof value !== 'object' || Array.isArray(value))
    throw new TypeError();
  const row = value as Record<string, unknown>;
  for (const key of [
    'public_id',
    'target',
    'intent',
    'status',
    'created_at',
    'updated_at',
  ])
    if (typeof row[key] !== 'string') throw new TypeError();
  if (
    !Array.isArray(row.actions) ||
    !row.actions.every((action) => typeof action === 'string')
  )
    throw new TypeError();
  return {
    public_id: row.public_id as string,
    target: row.target as string,
    intent: row.intent as string,
    status: row.status as string,
    created_at: row.created_at as string,
    updated_at: row.updated_at as string,
    actions: row.actions as string[],
  };
}
async function refresh(older = false) {
  if (
    loading.value ||
    props.pending ||
    props.controller.busy.value ||
    props.controller.refreshing.value
  )
    return;
  const own = epoch,
    module = props.module;
  needsRead.value = false;
  loading.value = true;
  error.value = '';
  try {
    const next = await props.controller.read('history', {
      module,
      limit: 25,
      ...(older && cursor.value ? { cursor: cursor.value } : {}),
    });
    if (!alive || own !== epoch || module !== props.module) return;
    if (!next) {
      if (props.controller.error.value)
        error.value = 'Unable to load operation history.';
      return;
    }
    if (
      !Array.isArray(next.operations) ||
      (next.next_cursor !== null && typeof next.next_cursor !== 'string')
    )
      throw new TypeError();
    const incoming = next.operations.map(decode);
    rows.value = older
      ? [
          ...rows.value,
          ...incoming.filter(
            (row) =>
              !rows.value.some(
                (current) => current.public_id === row.public_id,
              ),
          ),
        ]
      : incoming;
    cursor.value = next.next_cursor;
    loaded.value = true;
  } catch {
    if (alive && own === epoch)
      error.value = 'Unable to load operation history.';
  } finally {
    if (own === epoch) loading.value = false;
  }
}
async function status(row: HistoryRow) {
  const own = epoch;
  const next = await props.controller.read(
    row.intent === 'settings' ? 'settings.status' : 'modules.status',
    { id: row.public_id },
  );
  if (alive && own === epoch && next) result.value = next;
}
watch(
  () => [props.module, props.controller.contextRevision.value],
  () => {
    epoch++;
    query.value = { ...query.value, page: 1 };
    rows.value = [];
    cursor.value = null;
    result.value = undefined;
    error.value = '';
    loading.value = false;
    loaded.value = false;
    needsRead.value = true;
  },
  { immediate: true, flush: 'sync' },
);
watch(
  () => props.controller.changed.value,
  () => {
    needsRead.value = true;
  },
);
watch(
  () => [
    needsRead.value,
    props.pending,
    props.controller.busy.value,
    props.controller.refreshing.value,
  ],
  () => {
    if (
      needsRead.value &&
      !props.pending &&
      !props.controller.busy.value &&
      !props.controller.refreshing.value
    )
      void refresh();
  },
  { immediate: true, flush: 'post' },
);
onScopeDispose(() => {
  alive = false;
  epoch++;
});
</script>
<template>
  <section
    class="space-y-3 border-t border-line pt-5"
    :aria-busy="loading"
    data-operation-history
  >
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h3 class="font-semibold">{{ tr('Operation history') }}</h3>
      <Button
        variant="outline"
        size="sm"
        :busy="loading"
        :disabled="
          pending || controller.busy.value || controller.refreshing.value
        "
        @click="refresh()"
        >{{ tr('Refresh history') }}</Button
      >
    </div>
    <Alert v-if="error && !pending" tone="warning">{{ tr(error) }}</Alert>
    <Table
      v-model:query="query"
      mode="client"
      :rows="rows"
      :columns="columns"
      :column-state="columnState"
      :row-key="(row) => row.public_id"
      :loading="loading && !loaded"
      retain-empty-frame
      :english="locale === 'en'"
      :caption="tr('Operation history')"
      :searchable="false"
    >
      <template #cell-status="{ row }"
        ><Badge :tone="tone(row.status)">{{ tr(row.status) }}</Badge></template
      >
      <template #actions="{ row }">
        <Button
          size="sm"
          :disabled="
            pending || controller.busy.value || controller.refreshing.value
          "
          @click="status(row)"
          >{{ tr('Read status') }}</Button
        >
        <Button
          v-for="command in actions(row)"
          :key="command"
          size="sm"
          :tone="command === 'modules.resume' ? 'primary' : 'warning'"
          :disabled="
            pending || controller.busy.value || controller.refreshing.value
          "
          @click="emit('execute', command, row.public_id)"
          >{{ actionName(command) }}</Button
        >
      </template>
    </Table>
    <Button
      v-if="cursor"
      size="sm"
      :disabled="
        loading ||
        pending ||
        controller.busy.value ||
        controller.refreshing.value
      "
      @click="refresh(true)"
      >{{ tr('Load older operations') }}</Button
    >
    <AdminOperationResult v-if="result" :result="result" />
  </section>
</template>
