<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
  Button,
  Card,
  ConfirmDialog,
  Form,
  FormConflict,
  FormNotifications,
  RadioGroup,
  ContentLoading,
  Table,
  TextInput,
  formatApplicationInstant,
} from '@/runa/ui';
import type { TableColumn, TableQuery } from '@/runa/ui';
import type {
  ReferenceWorkspaceProps,
  Sample,
  Snapshot,
  HistoryEntry,
  WorkEntry,
} from '../contracts';
import { referenceCopy } from '../copy';
import { useReferenceWorkspace } from '../useReferenceWorkspace';
import type { ReferenceWorkspaceController } from '../useReferenceWorkspace';
const props = withDefaults(defineProps<ReferenceWorkspaceProps>(), {
  locale: 'pl',
});
defineSlots<{
  default(props: { controller: ReferenceWorkspaceController }): unknown;
}>();
const controller = useReferenceWorkspace(
  () => props.access,
  () => props.view,
  () => props.locale,
);
defineExpose({
  beforeLeave: controller.beforeLeave,
  blocksNavigation: computed(
    () => controller.dirty.value || controller.writing.value,
  ),
  initialSettled: computed(
    () => controller.ready.value || !controller.busy.value,
  ),
});
const {
  rows,
  state,
  snapshotRows,
  historyRows,
  workRows,
  selected,
  code,
  label,
  credential,
  secretMode,
  snapshotName,
  next,
  sampleConflict,
  settingsConflict,
  review,
  busy,
  ready,
  allowed,
  writable,
  canEditCode,
  sampleDirty,
  settingsDirty,
  error,
  success,
  fieldErrors,
  attempt,
  leaveOpen,
} = controller;
const copy = computed(() => referenceCopy(props.locale));
const title = computed(() => copy.value[props.view]);
const deleteTarget = ref<Snapshot>();
const query = ref<TableQuery>({
  page: 1,
  perPage: 10,
  sort: null,
  filters: {},
});
watch(
  () => JSON.stringify([props.access, props.view]),
  () => {
    deleteTarget.value = undefined;
    query.value = { page: 1, perPage: 10, sort: null, filters: {} };
  },
  { flush: 'sync' },
);
const sampleColumns = computed<TableColumn<Sample>[]>(() => [
  { key: 'public_id', label: 'ID', value: (row) => row.public_id },
  ...(props.access.codeRead
    ? [
        {
          key: 'code',
          label: copy.value.code,
          value: (row: Sample) => row.code ?? '',
        },
      ]
    : []),
]);
const snapshotColumns = computed<TableColumn<Snapshot>[]>(() => [
  { key: 'name', label: copy.value.snapshotName, value: (row) => row.name },
  {
    key: 'created_at',
    label: copy.value.date,
    value: (row) => formatApplicationInstant(row.created_at, props.locale),
  },
]);
const historyColumns = computed<TableColumn<HistoryEntry>[]>(() => [
  {
    key: 'occurred_at',
    label: copy.value.date,
    value: (row) => formatApplicationInstant(row.occurred_at, props.locale),
  },
  { key: 'action', label: copy.value.action, value: (row) => row.action },
  { key: 'outcome', label: copy.value.outcome, value: (row) => row.outcome },
]);
const workColumns = computed<TableColumn<WorkEntry>[]>(() => [
  { key: 'work_id', label: 'ID', value: (row) => row.work_id },
  {
    key: 'status',
    label: copy.value.status,
    value: (row) => {
      const labels: Record<string, string> = {
        pending: copy.value.workQueued,
        running: copy.value.workRunning,
        succeeded: copy.value.workSucceeded,
        failed: copy.value.workFailed,
        cancelled: copy.value.workCancelled,
      };
      return labels[row.status] ?? copy.value.workOther;
    },
  },
  { key: 'attempts', label: copy.value.attempts, value: (row) => row.attempts },
  { key: 'progress', label: copy.value.progress, value: (row) => row.progress },
]);
const secretOptions = computed(() => [
  { value: 'keep', label: copy.value.keepSecret },
  { value: 'replace', label: copy.value.replaceSecret },
  { value: 'clear', label: copy.value.clearSecret },
]);
function setSecretMode(value: string | null) {
  if (value === 'keep' || value === 'replace' || value === 'clear')
    secretMode.value = value;
}
async function removeSnapshot() {
  const row = deleteTarget.value;
  deleteTarget.value = undefined;
  if (row) await controller.deleteSnapshot(row);
}
const submit = () =>
  props.view === 'samples'
    ? controller.saveSample()
    : props.view === 'settings'
      ? controller.reviewSettings()
      : Promise.resolve();
</script>

<template>
  <slot :controller="controller">
    <Card>
      <template #header
        ><h1 class="text-xl font-semibold">{{ title }}</h1></template
      >
      <template #actions>
        <Button
          v-if="allowed"
          intent="inspect"
          :busy="busy"
          @click="controller.refresh()"
          >{{ copy.refresh }}</Button
        >
      </template>
      <Form
        :label="title"
        :busy="busy"
        :dirty="controller.dirty.value"
        @submit="submit"
      >
        <template #feedback>
          <FormNotifications
            :locale="locale"
            :error="error"
            :success="success"
            :attempt="attempt"
          />
        </template>
        <p v-if="!allowed" role="status" class="text-sm text-muted">
          {{ copy.unavailable }}
        </p>
        <ContentLoading
          v-else
          :loading="!ready && busy"
          :refreshing="ready && busy"
          :label="title"
          shape="form"
        >
          <p v-if="!ready" class="text-sm text-muted">{{ copy.failed }}</p>
          <fieldset v-else :disabled="busy" class="min-w-0 space-y-6">
            <template v-if="view === 'samples'">
              <Table
                v-model:query="query"
                mode="client"
                :rows="rows"
                :columns="sampleColumns"
                :row-key="(row) => row.public_id"
                :caption="copy.samples"
                :english="locale === 'en'"
              >
                <template v-if="canEditCode" #actions="{ row }">
                  <Button
                    v-if="row.code !== undefined"
                    intent="inspect"
                    size="sm"
                    :disabled="busy"
                    @click="controller.selectSample(row)"
                    >{{ copy.code }}: {{ row.code }}</Button
                  >
                </template>
              </Table>
              <div v-if="selected && canEditCode" class="space-y-4">
                <p class="break-all text-xs text-muted">
                  {{ selected.public_id }}
                </p>
                <TextInput
                  :model-value="code"
                  :label="copy.code"
                  :disabled="busy"
                  :error="fieldErrors.code ? copy.codeInvalid : ''"
                  :maxlength="40"
                  @update:model-value="code = $event ?? ''"
                />
                <FormConflict
                  v-if="sampleConflict"
                  :current="sampleConflict.code ?? ''"
                  :locale="locale"
                  :busy="busy"
                  @accept="controller.resolveSample(false)"
                  @keep="controller.resolveSample(true)"
                />
                <Button
                  type="submit"
                  intent="commit"
                  :busy="busy"
                  :disabled="!sampleDirty || !!sampleConflict"
                  >{{ copy.save }}</Button
                >
              </div>
            </template>
            <template v-if="view === 'settings' && state">
              <TextInput
                :model-value="label"
                :label="state.labelNames[locale]"
                :helper="state.labelDescriptions[locale]"
                :readonly="!writable"
                :disabled="busy"
                :maxlength="40"
                :error="fieldErrors['values.label'] ? copy.labelInvalid : ''"
                @update:model-value="label = $event ?? ''"
              />
              <p
                v-if="
                  access.credentialRead && state.credentialSet !== undefined
                "
                class="text-sm text-muted"
              >
                {{ state.credentialSet ? copy.secretSet : copy.secretEmpty }}
              </p>
              <template
                v-if="
                  writable &&
                  access.credentialWrite &&
                  access.credentialRead &&
                  state.credentialNames
                "
              >
                <RadioGroup
                  :model-value="secretMode"
                  :label="state.credentialNames[locale]"
                  :options="secretOptions"
                  :disabled="busy"
                  @update:model-value="setSecretMode"
                />
                <TextInput
                  v-if="secretMode === 'replace'"
                  :model-value="credential"
                  type="password"
                  autocomplete="new-password"
                  :label="state.credentialNames[locale]"
                  :helper="copy.secretHelp"
                  :disabled="busy"
                  :maxlength="512"
                  :error="
                    fieldErrors['values.credential'] ? copy.secretInvalid : ''
                  "
                  @update:model-value="credential = $event ?? ''"
                />
              </template>
              <FormConflict
                v-if="settingsConflict"
                :current="
                  settingsConflict.label +
                  (secretMode !== 'keep' ? '\n' + copy.conflictSecret : '')
                "
                :locale="locale"
                :busy="busy"
                @accept="controller.resolveSettings(false)"
                @keep="controller.resolveSettings(true)"
              />
              <div v-if="writable" class="flex flex-wrap gap-2">
                <Button
                  type="submit"
                  intent="inspect"
                  :busy="busy"
                  :disabled="!settingsDirty || !!settingsConflict"
                  >{{ copy.review }}</Button
                >
                <Button
                  v-if="review"
                  intent="commit"
                  :busy="busy"
                  @click="controller.publish"
                  >{{ copy.publish }}</Button
                >
              </div>
              <div v-if="review" role="status" class="space-y-2 text-sm">
                <h2 class="font-semibold">{{ copy.reviewed }}</h2>
                <p v-if="review.patch.label !== undefined" class="break-words">
                  {{ state.label }} → {{ review.patch.label }}
                </p>
                <p v-if="'credential' in review.patch">
                  {{ copy.secretChange }}
                </p>
              </div>
              <section class="space-y-4" :aria-label="copy.snapshots">
                <h2 class="font-semibold">{{ copy.snapshots }}</h2>
                <p class="text-sm text-muted">{{ copy.snapshotHelp }}</p>
                <template v-if="writable">
                  <TextInput
                    :model-value="snapshotName"
                    :label="copy.snapshotName"
                    :disabled="busy"
                    :maxlength="80"
                    :error="fieldErrors.name ? copy.nameInvalid : ''"
                    @update:model-value="snapshotName = $event ?? ''"
                  />
                  <Button
                    intent="commit"
                    :busy="busy"
                    :disabled="!!settingsConflict"
                    @click="controller.capture"
                    >{{ copy.capture }}</Button
                  >
                </template>
                <Table
                  v-model:query="query"
                  mode="client"
                  :rows="snapshotRows"
                  :columns="snapshotColumns"
                  :row-key="(row) => row.public_id"
                  :caption="copy.snapshots"
                  :english="locale === 'en'"
                >
                  <template v-if="writable" #actions="{ row }">
                    <Button
                      intent="inspect"
                      size="sm"
                      :disabled="busy"
                      @click="controller.loadSnapshot(row)"
                      >{{ copy.load }}</Button
                    >
                    <Button
                      intent="danger"
                      size="sm"
                      :disabled="busy"
                      @click="deleteTarget = row"
                      >{{ copy.remove }}</Button
                    >
                  </template>
                </Table>
              </section>
            </template>
            <template v-if="view === 'history'">
              <Table
                v-model:query="query"
                mode="client"
                :rows="historyRows"
                :columns="historyColumns"
                :row-key="(row) => row.public_id"
                :caption="copy.history"
                :english="locale === 'en'"
              />
              <Button
                v-if="next"
                intent="inspect"
                :busy="busy"
                @click="controller.refresh(true)"
                >{{ copy.next }}</Button
              >
            </template>
            <template v-if="view === 'work'">
              <Button
                v-if="writable"
                intent="commit"
                :busy="busy"
                @click="controller.dispatch"
                >{{ copy.dispatch }}</Button
              >
              <Table
                v-model:query="query"
                mode="client"
                :rows="workRows"
                :columns="workColumns"
                :row-key="(row) => row.work_id"
                :caption="copy.work"
                :english="locale === 'en'"
              />
            </template>
          </fieldset>
        </ContentLoading>
      </Form>
    </Card>
  </slot>
  <ConfirmDialog
    :model-value="leaveOpen"
    :title="copy.leave"
    :locale="locale"
    :confirm-label="copy.discard"
    confirm-intent="discard"
    @confirm="controller.answerLeave(true)"
    @cancel="controller.answerLeave(false)"
    @update:model-value="!$event && controller.answerLeave(false)"
  />
  <ConfirmDialog
    :model-value="!!deleteTarget && allowed"
    :title="copy.deleteTitle"
    :description="deleteTarget?.name ?? ''"
    :locale="locale"
    :confirm-label="copy.remove"
    confirm-intent="danger"
    @confirm="removeSnapshot"
    @cancel="deleteTarget = undefined"
    @update:model-value="!$event && (deleteTarget = undefined)"
  />
</template>
