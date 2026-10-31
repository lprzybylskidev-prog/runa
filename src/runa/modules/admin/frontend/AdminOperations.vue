<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import AdminOperationResult from './AdminOperationResult.vue';
import AdminModuleCard from './AdminModuleCard.vue';
import { computed, nextTick, ref, shallowRef, watch } from 'vue';
import {
  PageNavigation,
  Dialog,
  Tabs,
  Spinner,
  Badge,
  Accordion,
  useIdentity,
  getModulePresentation,
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
import { useAdminInitialReadiness } from './initialReadiness';
const props = defineProps<{ admin: AdminController }>();
const controller = useAdminOperations(props.admin);
const presentation = useAdminPresentation();
const identity = useIdentity();
const recoveryOpen = ref(false);
const capabilitiesReady = ref(false);
const initialSettled = ref(false);
const initialReadiness = useAdminInitialReadiness(() => initialSettled.value);
const capabilities = ref<string[]>([]),
  filter = ref('enabled');
const selected = ref('');
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
  if (selected.value && section !== true)
    url.searchParams.set('module', selected.value);
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
]);
const navigationCurrent = computed(() => ({
  routeKey: applicationSettings.value
    ? 'admin.dashboard.application'
    : 'admin.dashboard.operations',
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
      work.value = undefined;
      preview.value = undefined;
      result.value = undefined;
      operationId.value = '';
      recoveryOpen.value = false;
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
async function select(module: string) {
  const url = new URL(sectionHref(false), window.location.origin);
  url.searchParams.set('module', module);
  if (!(await pageNavigation.navigate(url.pathname + url.search + url.hash)))
    return;
  result.value = undefined;
  preview.value = undefined;
  work.value = undefined;
  controller.dirty.value = false;
}
watch(filter, () => {
  if (capabilitiesReady.value) void refresh();
});
const selectedModule = computed(() =>
  modules.value.find((module) => module.id === selected.value),
);
const moduleTabs = computed(() => [
  { id: 'overview', label: tr('Overview'), disabled: controller.busy.value },
  ...(has('health.configure') && has('diagnostics.read')
    ? [
        {
          id: 'health',
          label: tr('Health policy'),
          disabled: controller.busy.value,
        },
      ]
    : []),
  ...(has('settings.read')
    ? [
        {
          id: 'settings',
          label: tr('Settings'),
          disabled: controller.busy.value,
        },
      ]
    : []),
  ...(has('integrations.manage')
    ? [
        {
          id: 'integrations',
          label: tr('Integrations'),
          disabled: controller.busy.value,
        },
      ]
    : []),
]);
async function changeModuleTab(tab: string) {
  if (!moduleTabs.value.some((item) => item.id === tab && !item.disabled))
    return;
  await pageNavigation.navigate(
    sectionHref(
      tab === 'overview'
        ? false
        : (tab as 'health' | 'settings' | 'integrations'),
    ),
  );
}
async function closeModule() {
  const url = new URL(pageNavigation.href.value);
  url.searchParams.delete('module');
  url.searchParams.delete('section');
  if (await pageNavigation.navigate(url.pathname + url.search + url.hash)) {
    work.value = undefined;
    preview.value = undefined;
    result.value = undefined;
    operationId.value = '';
    recoveryOpen.value = false;
    controller.dirty.value = false;
  }
}
const runtimePhp = computed(() => {
  const runtime = controller.snapshot.value?.runtime;
  return runtime &&
    typeof runtime === 'object' &&
    !Array.isArray(runtime) &&
    typeof runtime.php === 'string'
    ? runtime.php
    : '—';
});
function entries(value: AdminJson | undefined): [string, AdminJson][] {
  return value && typeof value === 'object' && !Array.isArray(value)
    ? Object.entries(value)
    : [];
}
function blocker(code: string) {
  const messages: Record<string, string> = {
    core_target: 'Core module lifecycle cannot be changed here.',
    health_unavailable: 'Health has not been confirmed.',
    installed_data_incompatible: 'Installed data requires an update.',
    policy_unavailable: 'The lifecycle policy is unavailable.',
    not_registered: 'The module is not registered.',
    source_missing: 'Module source is missing.',
    required_cycle: 'Required dependencies contain a cycle.',
    dependency_version: 'A required dependency is incompatible.',
  };
  return (
    tr(messages[code] ?? 'A native module policy blocks this transition.') +
    ' (' +
    code +
    ')'
  );
}
function list(value: AdminJson | undefined): string[] {
  return Array.isArray(value)
    ? value.filter((item): item is string => typeof item === 'string')
    : [];
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
    await nextTick();
    if (!applicationSettings.value) await refresh();
    if (context !== controller.contextRevision.value) return;
    capabilitiesReady.value = true;
    await nextTick();
    initialSettled.value = true;
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
</script>
<template>
  <slot :controller="controller">
    <div class="space-y-6">
      <Alert v-if="controller.error.value && !selected" tone="warning"
        >{{ tr(controller.error.value)
        }}<Button @click="refresh">{{ tr('Retry') }}</Button></Alert
      >
      <PageNavigation
        :entries="navigationEntries"
        :loading="
          initialReadiness ? !initialReadiness.ready.value : !initialSettled
        "
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
      <template v-if="!applicationSettings && has('diagnostics.read')">
        <dl
          v-if="controller.snapshot.value"
          class="grid gap-3 sm:grid-cols-3"
          data-admin-runtime
        >
          <div
            class="rounded border border-line bg-[var(--runa-container-body-background)] px-4 py-3"
          >
            <dt class="text-xs text-muted">{{ tr('Platform') }}</dt>
            <dd class="mt-1 text-sm font-semibold">
              Runa
              <span class="font-normal text-muted">{{
                controller.snapshot.value.platform_version ??
                identity.platformVersion
              }}</span>
            </dd>
          </div>
          <div
            class="rounded border border-line bg-[var(--runa-container-body-background)] px-4 py-3"
          >
            <dt class="text-xs text-muted">{{ tr('Application') }}</dt>
            <dd class="mt-1 text-sm font-semibold">
              {{ identity.applicationName }}
              <span class="font-normal text-muted">{{
                controller.snapshot.value.application_version ??
                identity.applicationVersion
              }}</span>
            </dd>
          </div>
          <div
            class="rounded border border-line bg-[var(--runa-container-body-background)] px-4 py-3"
          >
            <dt class="text-xs text-muted">{{ tr('Runtime') }}</dt>
            <dd class="mt-1 text-sm font-semibold">
              PHP <span class="font-normal text-muted">{{ runtimePhp }}</span>
            </dd>
          </div>
        </dl>
        <section
          class="space-y-5"
          :aria-busy="controller.refreshing.value"
          data-module-diagnostics
        >
          <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="py-3 text-base font-semibold">{{ tr('Modules') }}</h2>
            <div class="flex flex-wrap items-center gap-4">
              <span
                v-if="controller.refreshing.value"
                class="flex items-center gap-2 text-xs text-muted"
                data-diagnostics-refresh
                ><Spinner
                  size="sm"
                  :label="tr('Refreshing module state')"
                /><span aria-hidden="true">{{ tr('Refreshing…') }}</span></span
              >
              <Select
                :label="tr('Module state')"
                v-model="filter"
                :options="[
                  {
                    value: 'enabled',
                    label: tr('Registered and enabled modules'),
                  },
                  { value: 'registered', label: tr('Registered modules') },
                  { value: 'all', label: tr('All modules') },
                ]"
                :locale="adminLocale"
                :disabled="controller.busy.value"
              />
            </div>
          </div>
          <ul
            class="grid min-w-0 items-stretch gap-4 md:grid-cols-2 xl:grid-cols-3"
          >
            <li
              v-for="module in modules"
              :key="String(module.id)"
              class="flex min-w-0"
            >
              <component
                :is="presentation.moduleCard ?? AdminModuleCard"
                :module="module"
                :disabled="controller.busy.value"
                @select="select"
              />
            </li>
          </ul>
          <p
            v-if="!modules.length && !controller.refreshing.value"
            class="py-8 text-center text-sm text-muted"
          >
            {{ tr('No modules match this filter.') }}
          </p>
        </section>
      </template>
    </div>
    <Dialog
      :model-value="!!selected && !applicationSettings && capabilitiesReady"
      :title="getModulePresentation(selected).name[adminLocale]"
      :description="selected"
      size="xl"
      :locale="adminLocale"
      :busy="controller.busy.value"
      @update:model-value="!$event && closeModule()"
    >
      <Alert v-if="controller.error.value" tone="warning">{{
        tr(controller.error.value)
      }}</Alert>
      <div
        v-if="controller.refreshing.value"
        class="flex items-center gap-2 text-xs text-muted"
      >
        <Spinner size="sm" :label="tr('Loading module information')" /><span
          aria-hidden="true"
          >{{ tr('Loading module information') }}</span
        >
      </div>
      <Tabs
        :model-value="editor ?? 'overview'"
        :items="moduleTabs"
        :label="tr('Module operations')"
        @update:model-value="changeModuleTab"
      >
        <template #overview>
          <div v-if="!editor" class="space-y-6">
            <component
              :is="presentation.moduleCard ?? AdminModuleCard"
              v-if="selectedModule"
              :module="selectedModule"
              :interactive="false"
              details
            />
            <div class="flex flex-wrap gap-3">
              <Button
                v-if="has('health.diagnose')"
                variant="outline"
                :busy="controller.busy.value"
                @click="command('health.diagnose', { module: selected })"
                >{{ tr('Diagnose now') }}</Button
              >
              <Button
                v-if="has('work.read')"
                variant="outline"
                :busy="controller.refreshing.value"
                :disabled="controller.busy.value"
                @click="readWork"
                >{{ tr('Active work') }}</Button
              >
            </div>
            <div
              v-if="has('modules.manage') && selectedModule?.kind !== 'core'"
              class="space-y-3"
            >
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
                <div class="space-y-3 rounded border border-line p-4">
                  <Badge :tone="preview.ready ? 'success' : 'warning'">{{
                    preview.ready
                      ? tr('Ready to apply')
                      : tr('Transition blocked')
                  }}</Badge>
                  <div
                    v-if="list(preview.affected).length"
                    class="space-y-2 text-xs"
                  >
                    <h4 class="font-semibold">{{ tr('Affected modules') }}</h4>
                    <ul class="space-y-1">
                      <li v-for="id in list(preview.affected)" :key="id">
                        {{ getModulePresentation(id).name[adminLocale] }}
                      </li>
                    </ul>
                  </div>
                  <dl
                    v-if="entries(preview.changes).length"
                    class="space-y-2 text-xs"
                  >
                    <dt class="font-semibold">{{ tr('Planned changes') }}</dt>
                    <dd
                      v-for="[id, enabled] in entries(preview.changes)"
                      :key="id"
                      class="flex flex-wrap justify-between gap-2"
                    >
                      <span>{{
                        getModulePresentation(id).name[adminLocale]
                      }}</span
                      ><Badge>{{
                        tr(
                          intent === 'unregister'
                            ? 'unregister'
                            : enabled
                              ? 'enable'
                              : 'disable',
                        )
                      }}</Badge>
                    </dd>
                  </dl>
                  <Alert v-if="list(preview.blockers).length" tone="warning"
                    ><ul class="space-y-2">
                      <li v-for="code in list(preview.blockers)" :key="code">
                        {{ blocker(code) }}
                      </li>
                    </ul></Alert
                  >
                  <dl
                    v-if="entries(preview.active_work).length"
                    class="space-y-2 text-xs"
                  >
                    <dt class="font-semibold">{{ tr('Active work') }}</dt>
                    <dd
                      v-for="[id, counts] in entries(preview.active_work)"
                      :key="id"
                    >
                      <span>{{
                        getModulePresentation(id).name[adminLocale]
                      }}</span>
                      <ul>
                        <li
                          v-for="[state, count] in entries(counts)"
                          :key="state"
                        >
                          {{ tr(state) }}: {{ count }}
                        </li>
                      </ul>
                    </dd>
                  </dl>
                </div>
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
            <Accordion
              v-if="has('modules.manage') || has('settings.manage')"
              v-model="recoveryOpen"
              :label="tr('Retained operation')"
            >
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
                  @click="
                    command('settings.recover', { id: operationId }, true)
                  "
                  >{{ tr('Recover settings') }}</Button
                >
              </div>
            </Accordion>
          </div>
        </template>
        <template
          v-for="tab in moduleTabs.filter((tab) => tab.id !== 'overview')"
          :key="tab.id"
          #[tab.id]
        >
          <component
            v-if="editor === tab.id"
            :is="presentation.operationalEditor ?? AdminOperationalEditor"
            :key="selected + ':' + editor"
            :controller="controller"
            :module="selected"
            :kind="editor"
            :writable="editor !== 'settings' || has('settings.manage')"
            @saved="refresh"
          />
        </template>
      </Tabs>
      <AdminOperationResult v-if="result" :result="result" />
      <template #footer
        ><Button
          variant="outline"
          :disabled="controller.busy.value"
          @click="closeModule"
          >{{ tr('Close') }}</Button
        ></template
      >
    </Dialog>
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
