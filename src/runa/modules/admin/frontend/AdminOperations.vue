<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import AdminOperationResult from './AdminOperationResult.vue';
import { computed, nextTick, ref, shallowRef, watch } from 'vue';
import {
  PageNavigation,
  usePageResolver,
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
const capabilitiesReady = ref(false);
const capabilities = ref<string[]>([]),
  filter = ref('enabled');
const moduleInput = ref(''),
  selected = ref('');
const pageNavigation = usePageResolver(
  [
    {
      href: window.location.pathname,
      routeKey: 'admin.dashboard',
      moduleKey: 'runa.admin',
      serverRouteName: 'runa.admin.dashboard',
      title: 'Administrator panel',
    },
  ],
  { beforeLeave: () => controller.beforeLeave() },
);
const applicationSettings = computed(
  () =>
    new URL(pageNavigation.href.value).searchParams.get('section') ===
    'application',
);
function sectionHref(
  section: boolean | 'health' | 'settings' | 'integrations',
) {
  const url = new URL(pageNavigation.href.value);
  if (section)
    url.searchParams.set('section', section === true ? 'application' : section);
  else url.searchParams.delete('section');
  if (selected.value) url.searchParams.set('module', selected.value);
  else url.searchParams.delete('module');
  return url.pathname + url.search + url.hash;
}
const navigationEntries = computed(() => [
  {
    key: 'operations',
    routeKey: 'admin.dashboard.operations',
    moduleKey: 'runa.admin',
    label: tr('Operations'),
    href: sectionHref(false),
    availability: 'available' as const,
  },
  {
    key: 'application',
    routeKey: 'admin.dashboard.application',
    moduleKey: 'runa.admin',
    label: tr('Application settings'),
    href: sectionHref(true),
    availability: 'available' as const,
  },
  ...(selected.value &&
  (has('settings.read') ||
    has('integrations.manage') ||
    (has('health.configure') && has('diagnostics.read')))
    ? [
        {
          key: 'module',
          routeKey: 'admin.dashboard.module',
          moduleKey: 'runa.admin',
          label: tr('Module operations'),
          href: sectionHref(false),
          availability: 'available' as const,
          children: [
            ...(has('health.configure') && has('diagnostics.read')
              ? [
                  {
                    key: 'health',
                    routeKey: 'admin.dashboard.health',
                    moduleKey: 'runa.admin',
                    label: tr('Health policy'),
                    href: sectionHref('health'),
                    availability: 'available' as const,
                  },
                ]
              : []),
            ...(has('settings.read')
              ? [
                  {
                    key: 'settings',
                    routeKey: 'admin.dashboard.settings',
                    moduleKey: 'runa.admin',
                    label: tr('Settings'),
                    href: sectionHref('settings'),
                    availability: 'available' as const,
                  },
                ]
              : []),
            ...(has('integrations.manage')
              ? [
                  {
                    key: 'integrations',
                    routeKey: 'admin.dashboard.integrations',
                    moduleKey: 'runa.admin',
                    label: tr('Integrations'),
                    href: sectionHref('integrations'),
                    availability: 'available' as const,
                  },
                ]
              : []),
          ],
        },
      ]
    : []),
]);
const navigationCurrent = computed(() => ({
  routeKey: applicationSettings.value
    ? 'admin.dashboard.application'
    : 'admin.dashboard.' + (editor.value ?? 'operations'),
  moduleKey: 'runa.admin',
  href: sectionHref(editor.value ?? applicationSettings.value),
}));
function navigateSection(href: string, event: MouseEvent) {
  void pageNavigation.navigate(href, event);
}
watch(applicationSettings, () => {
  editor.value = undefined;
});
const editor = ref<'settings' | 'health' | 'integrations'>();
const work = shallowRef<AdminOperationData>();
const preview = shallowRef<AdminOperationData>();
const result = shallowRef<AdminOperationData>();
const operationId = ref('');
const operationKind = ref('modules');
const intent = ref('disable');
const has = (permission: string) => capabilities.value.includes(permission);
watch(
  [pageNavigation.href, capabilities],
  () => {
    const url = new URL(pageNavigation.href.value);
    const module = url.searchParams.get('module') ?? '';
    if (selected.value !== module) {
      selected.value = module;
      moduleInput.value = module;
    }
    const section = url.searchParams.get('section');
    editor.value =
      selected.value &&
      section === 'health' &&
      has('health.configure') &&
      has('diagnostics.read')
        ? 'health'
        : selected.value && section === 'settings' && has('settings.read')
          ? 'settings'
          : selected.value &&
              section === 'integrations' &&
              has('integrations.manage')
            ? 'integrations'
            : undefined;
  },
  { immediate: true },
);
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
  const url = new URL(sectionHref(false), window.location.origin);
  url.searchParams.set('module', module);
  if (!(await pageNavigation.navigate(url.pathname + url.search + url.hash)))
    return;
  result.value = undefined;
  preview.value = undefined;
  work.value = undefined;
  controller.dirty.value = false;
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
    capabilitiesReady.value = false;
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
    capabilitiesReady.value = true;
    await nextTick();
    if (!applicationSettings.value && !editor.value) await refresh();
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
    capabilitiesReady.value = true;
    await nextTick();
    if (!applicationSettings.value && !editor.value) await refresh();
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
      <PageNavigation
        :entries="navigationEntries"
        :current="navigationCurrent"
        :label="tr('Administrator panel')"
        :disabled="controller.busy.value"
        @navigate="navigateSection"
      />
      <Card v-if="applicationSettings && capabilitiesReady">
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
