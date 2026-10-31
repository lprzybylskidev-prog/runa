<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import AdminOperationResult from './AdminOperationResult.vue';
import { computed, ref, shallowRef, watch } from 'vue';
import {
  Alert,
  Button,
  Card,
  ConfirmDialog,
  Select,
  TextInput,
} from '@/runa/ui';
import type { AdminController, AdminJson } from './contracts';
import { useAdminOperations } from './useAdminOperations';
import type {
  AdminOperationData,
  AdminOperationCommand,
} from './useAdminOperations';
import AdminApplicationSettings from './AdminApplicationSettings.vue';
import AdminOperationalEditor from './AdminOperationalEditor.vue';
import { useAdminPresentation } from './presentation';
const props = defineProps<{ admin: AdminController }>();
const controller = useAdminOperations(props.admin);
const presentation = useAdminPresentation();
const capabilities = ref<string[]>([]),
  filter = ref('enabled');
const moduleInput = ref(''),
  selected = ref('');
const applicationSettings = ref(false);
async function showApplicationSettings() {
  if (!(await controller.beforeLeave())) return;
  applicationSettings.value = !applicationSettings.value;
  editor.value = undefined;
}
const editor = ref<'settings' | 'health' | 'integrations'>();
const work = shallowRef<AdminOperationData>();
const preview = shallowRef<AdminOperationData>();
const result = shallowRef<AdminOperationData>();
const operationId = ref('');
const operationKind = ref('modules');
const intent = ref('disable');
const has = (permission: string) => capabilities.value.includes(permission);
const modules = computed(() => {
  const values = controller.snapshot.value?.modules;
  return Array.isArray(values)
    ? values.filter(
        (value): value is AdminOperationData =>
          value !== null && typeof value === 'object' && !Array.isArray(value),
      )
    : [];
});
const activeWork = computed(() => {
  const values = work.value?.work;
  return Array.isArray(values)
    ? values.filter(
        (value): value is AdminOperationData =>
          value !== null && typeof value === 'object' && !Array.isArray(value),
      )
    : [];
});
async function refresh() {
  if (has('diagnostics.read')) await controller.refresh(filter.value);
}
async function select(module = moduleInput.value) {
  if (!(await controller.beforeLeave())) return;
  result.value = undefined;
  selected.value = module;
  moduleInput.value = module;
  editor.value = undefined;
  preview.value = undefined;
  work.value = undefined;
  controller.dirty.value = false;
}
async function edit(kind: 'settings' | 'health' | 'integrations') {
  if (!(await controller.beforeLeave())) return;
  editor.value = kind;
  preview.value = undefined;
}
async function command(
  name: AdminOperationCommand,
  input: AdminOperationData,
  destructive = false,
) {
  const response = await controller.execute(name, input, destructive);
  if (response) {
    result.value = response;
    const transition = response.transition;
    if (
      transition &&
      typeof transition === 'object' &&
      !Array.isArray(transition) &&
      typeof transition.operationId === 'string'
    ) {
      operationId.value = transition.operationId;
      operationKind.value = name.startsWith('settings.')
        ? 'settings'
        : 'modules';
    }
    preview.value = undefined;
    const changedWork = response.work;
    if (
      changedWork &&
      typeof changedWork === 'object' &&
      !Array.isArray(changedWork) &&
      work.value &&
      Array.isArray(work.value.work)
    ) {
      work.value = {
        ...work.value,
        work: work.value.work.map((item) =>
          item &&
          typeof item === 'object' &&
          !Array.isArray(item) &&
          item.workId === changedWork.workId
            ? changedWork
            : item,
        ),
      };
    }
    await refresh();
  }
}
async function review() {
  preview.value = await controller.execute('modules.preview', {
    module: selected.value,
    intent: intent.value,
  });
}
async function status() {
  result.value = await controller.read(
    operationKind.value === 'settings' ? 'settings.status' : 'modules.status',
    { id: operationId.value },
  );
}
async function readWork() {
  const next = await controller.read('work', { module: selected.value });
  if (next) work.value = next;
}
watch(
  () => [controller.contextRevision.value, props.admin.state.value],
  async () => {
    applicationSettings.value = false;
    capabilities.value = [];
    selected.value = '';
    moduleInput.value = '';
    editor.value = undefined;
    work.value = undefined;
    preview.value = undefined;
    result.value = undefined;
    operationId.value = '';
    if (props.admin.state.value !== 'ready') return;
    const context = controller.contextRevision.value;
    const value = await controller.read('capabilities');
    if (context !== controller.contextRevision.value) return;
    capabilities.value = Array.isArray(value?.capabilities)
      ? value.capabilities.filter((v): v is string => typeof v === 'string')
      : [];
    await refresh();
  },
  { immediate: true },
);
// Validate current capabilities in place without resetting the selected editor.
watch(
  () => props.admin.session.authorityRevision.value,
  async () => {
    if (props.admin.state.value !== 'ready') return;
    const context = controller.contextRevision.value;
    const value = await controller.read('capabilities');
    if (!value || context !== controller.contextRevision.value) return;
    capabilities.value = Array.isArray(value.capabilities)
      ? value.capabilities.filter((v): v is string => typeof v === 'string')
      : [];
    await refresh();
  },
);
defineExpose({ controller });
function format(value: AdminJson | undefined) {
  return typeof value === 'object'
    ? JSON.stringify(value)
    : String(value ?? '—');
}
</script>
<template>
  <slot :controller="controller">
    <div class="space-y-6">
      <p
        v-if="controller.refreshing.value && !controller.snapshot.value"
        class="text-sm text-muted"
        role="status"
      >
        {{ tr('Loading operational information…') }}
      </p>
      <Alert v-if="controller.error.value" tone="warning">{{
        tr(controller.error.value)
      }}</Alert>
      <Button
        :disabled="controller.busy.value"
        @click="showApplicationSettings"
        >{{
          applicationSettings
            ? tr('Back to operations')
            : tr('Application settings')
        }}</Button
      >
      <Card v-if="applicationSettings">
        <component
          :is="presentation.applicationSettings ?? AdminApplicationSettings"
          :controller="controller"
        />
      </Card>
      <Card v-if="!applicationSettings && has('diagnostics.read')">
        <template #header
          ><h2>{{ tr('Runtime and module health') }}</h2></template
        >
        <div class="space-y-4">
          <div class="flex flex-wrap items-end gap-3">
            <Select
              :label="tr('Registry state')"
              v-model="filter"
              :options="[
                { value: 'enabled', label: tr('Registered and enabled') },
                { value: 'registered', label: tr('Registered') },
                { value: 'all', label: tr('All retained modules') },
              ]"
              :locale="adminLocale"
              :disabled="controller.busy.value"
            />
            <Button :disabled="controller.busy.value" @click="refresh">{{
              tr('Refresh observations')
            }}</Button>
          </div>
          <p v-if="controller.snapshot.value" class="text-sm text-muted">
            {{ tr('Runa') }} {{ controller.snapshot.value.platform_version }} ·
            {{ tr('Application') }}
            {{ controller.snapshot.value.application_version }} · PHP
            {{ format(controller.snapshot.value.runtime) }}
          </p>
          <p class="text-xs text-muted">
            {{
              tr(
                'Reads use persisted observations. Diagnosis runs only when explicitly requested.',
              )
            }}
          </p>
          <ul class="grid min-w-0 gap-4 md:grid-cols-2">
            <li
              v-for="module in modules"
              :key="String(module.id)"
              class="min-w-0 space-y-2 border border-field p-4 text-sm"
            >
              <Button
                :disabled="controller.busy.value"
                @click="select(String(module.id))"
                >{{ module.id }}</Button
              >
              <p>
                {{ module.available ? tr('Available') : tr('Unavailable') }} ·
                {{ tr(String(module.health)) }} ·
                {{ module.enabled ? tr('Enabled') : tr('Disabled') }}
              </p>
              <p>
                {{ tr('Source') }}
                {{ module.source_version ?? tr('missing') }} ·
                {{ tr('Installed') }}
                {{ module.installed_version ?? tr('not installed') }}
              </p>
              <p class="break-words text-xs text-muted">
                {{ tr('Required:') }} {{ format(module.required) }} ·
                {{ tr('Optional:') }}
                {{ format(module.optional) }}
              </p>
              <p
                v-if="Array.isArray(module.reasons) && module.reasons.length"
                class="break-words text-xs"
              >
                {{ module.reasons.join(', ') }}
              </p>
              <p
                v-for="(observation, index) in Array.isArray(
                  module.observations,
                )
                  ? module.observations
                  : []"
                :key="index"
                class="break-words text-xs text-muted"
              >
                {{ format(observation) }}
              </p>
            </li>
          </ul>
        </div>
      </Card>
      <Card v-if="!applicationSettings && capabilities.length">
        <template #header
          ><h2>{{ tr('Module operations') }}</h2></template
        >
        <div class="space-y-4">
          <div class="flex flex-wrap items-end gap-3">
            <TextInput
              :label="tr('Module ID')"
              v-model="moduleInput"
              :helper="tr('Select a module above or enter its declared ID.')"
              :disabled="controller.busy.value"
            />
            <Button
              :disabled="controller.busy.value || !moduleInput"
              @click="select()"
              >{{ tr('Select module') }}</Button
            >
          </div>
          <template v-if="selected">
            <h3 class="break-words font-semibold">{{ selected }}</h3>
            <div class="flex flex-wrap gap-3">
              <Button
                v-if="has('health.diagnose')"
                :disabled="controller.busy.value"
                @click="command('health.diagnose', { module: selected })"
                >{{ tr('Diagnose now') }}</Button
              >
              <Button
                v-if="has('health.configure') && has('diagnostics.read')"
                :disabled="controller.busy.value"
                @click="edit('health')"
                >{{ tr('Health policy') }}</Button
              >
              <Button
                v-if="has('settings.read')"
                :disabled="controller.busy.value"
                @click="edit('settings')"
                >{{ tr('Settings') }}</Button
              >
              <Button
                v-if="has('integrations.manage')"
                :disabled="controller.busy.value"
                @click="edit('integrations')"
                >{{ tr('Integrations') }}</Button
              >
              <Button
                v-if="has('work.read')"
                :disabled="controller.busy.value"
                @click="readWork"
                >{{ tr('Active work') }}</Button
              >
            </div>
            <component
              v-if="editor"
              :is="presentation.operationalEditor ?? AdminOperationalEditor"
              :key="selected + ':' + editor"
              :controller="controller"
              :module="selected"
              :kind="editor"
              :writable="editor !== 'settings' || has('settings.manage')"
              @saved="refresh"
            />
            <div v-if="has('modules.manage')" class="space-y-3">
              <h3 class="font-semibold">{{ tr('Optional lifecycle') }}</h3>
              <p class="text-xs text-muted">
                {{
                  tr(
                    'Core targets are refused. Review all affected dependencies and work before applying.',
                  )
                }}
              </p>
              <Select
                :label="tr('Intent')"
                v-model="intent"
                :options="
                  ['enable', 'disable', 'unregister'].map((value) => ({
                    value,
                    label: tr(value),
                  }))
                "
                :locale="adminLocale"
                :disabled="controller.busy.value"
                @update:model-value="preview = undefined"
              />
              <Button :disabled="controller.busy.value" @click="review">{{
                tr('Preview transition')
              }}</Button>
              <template v-if="preview">
                <pre class="whitespace-pre-wrap break-words text-xs">{{
                  JSON.stringify({ ...preview, review: undefined }, null, 2)
                }}</pre>
                <Button
                  v-if="preview.ready && typeof preview.review === 'string'"
                  :disabled="controller.busy.value"
                  @click="
                    command(
                      'modules.apply',
                      { review: preview.review, confirmed: true },
                      true,
                    )
                  "
                  >{{ tr('Apply reviewed transition') }}</Button
                >
              </template>
            </div>
            <div v-if="work" class="space-y-3">
              <p v-if="work.truncated" class="text-sm text-muted">
                {{ tr('Additional active work is omitted.') }}
              </p>
              <p v-if="!activeWork.length" class="text-sm text-muted">
                {{ tr('No active work.') }}
              </p>
              <div
                v-for="item in activeWork"
                :key="String(item.workId)"
                class="space-y-2 border border-field p-3 text-sm"
              >
                <p class="break-words">
                  {{ item.operation }} · {{ tr(String(item.status)) }} ·
                  {{ item.progress }}
                </p>
                <p v-if="item.cancellationRequested">
                  {{
                    tr('Cancellation requested; completion is not confirmed.')
                  }}
                </p>
                <p
                  v-if="item.limitations"
                  class="break-words text-xs text-muted"
                >
                  {{ item.limitations }}
                </p>
                <Button
                  v-if="
                    has('work.cancel') &&
                    item.cancellable &&
                    !item.cancellationRequested
                  "
                  :disabled="controller.busy.value"
                  @click="
                    command('work.cancel', { id: item.workId ?? null }, true)
                  "
                  >{{ tr('Request cancellation') }}</Button
                >
                <Button
                  :disabled="controller.busy.value"
                  @click="
                    controller
                      .read('work.status', { id: item.workId ?? null })
                      .then((value) => (result = value))
                  "
                  >{{ tr('Refresh work status') }}</Button
                >
              </div>
            </div>
          </template>
          <div
            v-if="has('modules.manage') || has('settings.manage')"
            class="space-y-3"
          >
            <h3 class="font-semibold">{{ tr('Retained operation') }}</h3>
            <TextInput
              :label="tr('Operation UUID')"
              v-model="operationId"
              :disabled="controller.busy.value"
            />
            <Select
              :label="tr('Operation owner')"
              v-model="operationKind"
              :options="
                [
                  { value: 'modules', label: tr('Optional lifecycle') },
                  { value: 'settings', label: tr('Module settings') },
                ].filter((option) =>
                  has(
                    option.value === 'modules'
                      ? 'modules.manage'
                      : 'settings.manage',
                  ),
                )
              "
              :locale="adminLocale"
              :disabled="controller.busy.value"
            />
            <div class="flex flex-wrap gap-3">
              <Button
                :disabled="controller.busy.value || !operationId"
                @click="status"
                >{{ tr('Read status') }}</Button
              >
              <Button
                v-if="operationKind === 'modules' && has('modules.manage')"
                :disabled="controller.busy.value || !operationId"
                @click="command('modules.resume', { id: operationId })"
                >{{ tr('Resume native workflow') }}</Button
              >
              <Button
                v-if="operationKind === 'modules' && has('modules.manage')"
                :disabled="controller.busy.value || !operationId"
                @click="command('modules.cancel', { id: operationId }, true)"
                >{{ tr('Cancel and compensate') }}</Button
              >
              <Button
                v-if="operationKind === 'settings' && has('settings.manage')"
                :disabled="controller.busy.value || !operationId"
                @click="command('settings.recover', { id: operationId }, true)"
                >{{ tr('Recover settings') }}</Button
              >
            </div>
          </div>
          <AdminOperationResult v-if="result" :result="result" />
          <p class="text-xs text-muted">
            {{
              tr(
                'Maintenance diagnostics and recovery use the supported CLI. Installation, updates and operator commands are unavailable here.',
              )
            }}
          </p>
        </div>
      </Card>
    </div>
  </slot>
  <ConfirmDialog
    :model-value="!!controller.confirmation.value"
    :title="tr(controller.confirmation.value ?? '')"
    :locale="adminLocale"
    @confirm="controller.confirm(true)"
    @cancel="controller.confirm(false)"
    @update:model-value="!$event && controller.confirm(false)"
  />
</template>
