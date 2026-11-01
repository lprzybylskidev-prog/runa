<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import AdminOperationResult from './AdminOperationResult.vue';
import AdminModuleCard from './AdminModuleCard.vue';
import AdminOperationHistory from './AdminOperationHistory.vue';
import type { TableColumn, TableQuery } from '@/runa/ui';
import { computed, nextTick, ref, shallowRef, watch } from 'vue';
import {
  PageNavigation,
  Dialog,
  ContentLoading,
  Skeleton,
  Badge,
  useIdentity,
  getModulePresentation,
  usePageResolver,
  Alert,
  FormNotifications,
  Button,
  Card,
  ConfirmDialog,
  Select,
  Table,
} from '@/runa/ui';
import type { AdminController, AdminJson } from './contracts';
import { useAdminOperations } from './useAdminOperations';
import type {
  AdminOperationData,
  AdminOperationCommand,
} from './useAdminOperations';
import AdminApplicationSettings from './AdminApplicationSettings.vue';
import { useAdminPresentation } from './presentation';
import { useAdminInitialReadiness } from './initialReadiness';
const props = defineProps<{ admin: AdminController }>();
const controller = useAdminOperations(props.admin);
const presentation = useAdminPresentation();
const identity = useIdentity();
const pendingCommand = ref<AdminOperationCommand>();
const workLoading = ref(false);
const workLoaded = ref(false);
const diagnosticsLoading = ref(false);
const filterLoading = ref(false);
let diagnosticsRequest = 0;
const needsWork = ref(false);
let workRequest = 0;
let commandRequest = 0;
const workQuery = ref<TableQuery>({
  page: 1,
  perPage: 10,
  sort: null,
  filters: {},
});
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
function sectionHref(section: boolean) {
  const url = new URL(pageNavigation.href.value);
  if (section) url.searchParams.set('section', 'application');
  else url.searchParams.delete('section');
  if (selected.value && !section)
    url.searchParams.set('module', selected.value);
  else url.searchParams.delete('module');
  return url.pathname + url.search + url.hash;
}
const navigationEntries = computed(() => [
  {
    key: 'operations',
    routeKey: 'admin.dashboard.operations',
    moduleKey: 'runa.admin',
    label: tr('Modules'),
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
  href: sectionHref(applicationSettings.value),
}));
function navigateSection(href: string, event: MouseEvent) {
  void pageNavigation.navigate(href, event);
}
const work = shallowRef<AdminOperationData>();
const preview = shallowRef<AdminOperationData>();
const result = shallowRef<AdminOperationData>();
const diagnosis = shallowRef<AdminOperationData>();
const diagnosisOpen = ref(false);
let diagnosisRequest = 0;
function resetDiagnosis() {
  diagnosisRequest++;
  diagnosisOpen.value = false;
  diagnosis.value = undefined;
}
function closeDiagnosis() {
  if (!controller.busy.value) resetDiagnosis();
}
async function diagnose() {
  if (controller.busy.value || !selected.value || !has('health.diagnose'))
    return;
  const own = ++diagnosisRequest;
  const module = selected.value;
  diagnosis.value = undefined;
  controller.error.value = '';
  diagnosisOpen.value = true;
  // Capture the enabled opener before the shared write lane disables it.
  await nextTick();
  if (own !== diagnosisRequest || !diagnosisOpen.value) return;
  await command('health.diagnose', { module });
}
const has = (permission: string) => capabilities.value.includes(permission);
type LifecycleIntent = 'enable' | 'disable' | 'unregister';
const intent = ref<LifecycleIntent>('disable');
const reviewOpen = ref(false);
const previewLoading = ref(false);
let previewRequest = 0;
const lifecycleLabel = computed(() =>
  tr(
    intent.value === 'enable'
      ? 'Enable module'
      : intent.value === 'disable'
        ? 'Disable module'
        : 'Unregister module',
  ),
);
function resetReview() {
  previewRequest++;
  reviewOpen.value = false;
  previewLoading.value = false;
  preview.value = undefined;
}
function closeReview() {
  if (!controller.busy.value) resetReview();
}
watch(
  [
    selected,
    applicationSettings,
    () => controller.contextRevision.value,
    () => props.admin.session.authorityRevision.value,
    () => has('modules.manage'),
  ],
  resetReview,
  { flush: 'sync' },
);
watch(
  [
    selected,
    applicationSettings,
    () => controller.contextRevision.value,
    () => props.admin.session.authorityRevision.value,
    () => has('health.diagnose'),
  ],
  resetDiagnosis,
  { flush: 'sync' },
);
watch(
  [pageNavigation.href, capabilities],
  () => {
    const url = new URL(pageNavigation.href.value);
    const module = url.searchParams.get('module') ?? '';
    if (selected.value !== module) {
      selected.value = module;
      clearWork();
      preview.value = undefined;
      result.value = undefined;
      diagnosis.value = undefined;
    }
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
async function refresh(replaceCards = false) {
  if (!has('diagnostics.read')) return;
  const own = ++diagnosticsRequest;
  diagnosticsLoading.value = true;
  filterLoading.value = replaceCards;
  try {
    await controller.refresh(filter.value);
  } finally {
    if (own === diagnosticsRequest) {
      diagnosticsLoading.value = false;
      filterLoading.value = false;
    }
  }
}
async function select(module: string) {
  const url = new URL(sectionHref(false), window.location.origin);
  url.searchParams.set('module', module);
  if (!(await pageNavigation.navigate(url.pathname + url.search + url.hash)))
    return;
  result.value = undefined;
  diagnosis.value = undefined;
  preview.value = undefined;
  controller.dirty.value = false;
}
watch(filter, () => {
  if (capabilitiesReady.value) void refresh(true);
});
const selectedModule = computed(() =>
  modules.value.find((module) => module.id === selected.value),
);
async function closeModule() {
  const url = new URL(pageNavigation.href.value);
  url.searchParams.delete('module');
  url.searchParams.delete('section');
  if (await pageNavigation.navigate(url.pathname + url.search + url.hash)) {
    clearWork();
    preview.value = undefined;
    result.value = undefined;
    diagnosis.value = undefined;
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
    disable_before_unregister: 'Disable the module before unregistering it.',
    registered_required_dependent: 'Unregister dependent modules first.',
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
  const own = ++commandRequest;
  const context = controller.contextRevision.value;
  pendingCommand.value = name;
  if (name === 'health.diagnose') diagnosis.value = undefined;
  try {
    const response = await controller.execute(name, input, destructive);
    if (
      response &&
      own === commandRequest &&
      context === controller.contextRevision.value
    ) {
      if (name === 'health.diagnose') diagnosis.value = response;
      else result.value = response;
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
      return response;
    }
  } finally {
    if (own === commandRequest) pendingCommand.value = undefined;
  }
}
async function review(nextIntent: LifecycleIntent) {
  if (
    controller.busy.value ||
    !selected.value ||
    !has('modules.manage') ||
    selectedModule.value?.kind !== 'optional' ||
    selectedModule.value.registered === false
  )
    return;
  const own = ++previewRequest;
  const module = selected.value;
  const context = controller.contextRevision.value;
  intent.value = nextIntent;
  preview.value = undefined;
  previewLoading.value = true;
  reviewOpen.value = true;
  try {
    // Let Dialog capture its enabled opener before the shared write lane disables it.
    await nextTick();
    if (own !== previewRequest || !reviewOpen.value) return;
    const response = await controller.execute('modules.preview', {
      module,
      intent: nextIntent,
    });
    if (
      own === previewRequest &&
      module === selected.value &&
      context === controller.contextRevision.value &&
      reviewOpen.value
    )
      preview.value = response;
  } finally {
    if (own === previewRequest) previewLoading.value = false;
  }
}
async function applyReview() {
  if (
    controller.busy.value ||
    previewLoading.value ||
    !reviewOpen.value ||
    !preview.value?.ready ||
    typeof preview.value.review !== 'string'
  )
    return;
  const own = previewRequest;
  const reviewed = preview.value.review;
  const response = await command('modules.apply', {
    review: reviewed,
    confirmed: true,
  });
  if (own !== previewRequest) return;
  if (response) resetReview();
  else preview.value = undefined; // A failed apply requires another explicit review.
}
async function historyAction(name: AdminOperationCommand, id: string) {
  await command(
    name,
    { id },
    name === 'modules.cancel' || name === 'settings.recover',
  );
}
function clearWork() {
  workRequest++;
  workQuery.value = { ...workQuery.value, page: 1 };
  work.value = undefined;
  needsWork.value = false;
  workLoaded.value = false;
  workLoading.value = false;
}
async function readWork() {
  if (workLoading.value || controller.busy.value || controller.refreshing.value)
    return;
  const own = ++workRequest;
  needsWork.value = false;
  workLoading.value = true;
  const module = selected.value;
  try {
    const next = await controller.read('work', { module });
    if (next && own === workRequest && module === selected.value) {
      work.value = next;
      workLoaded.value = true;
    }
  } finally {
    if (own === workRequest) workLoading.value = false;
  }
}
watch(
  [selected, applicationSettings, capabilitiesReady],
  () => {
    clearWork();
    needsWork.value =
      !!selected.value && !applicationSettings.value && capabilitiesReady.value;
  },
  { immediate: true },
);
watch(
  () => [
    needsWork.value,
    capabilitiesReady.value,
    capabilities.value,
    controller.busy.value,
    controller.refreshing.value,
    pendingCommand.value,
  ],
  () => {
    if (
      needsWork.value &&
      capabilitiesReady.value &&
      has('work.read') &&
      selected.value &&
      !applicationSettings.value &&
      !pendingCommand.value &&
      !controller.busy.value &&
      !controller.refreshing.value
    )
      void readWork();
  },
  { flush: 'post' },
);
watch(
  () => controller.changed.value,
  () => {
    if (selected.value && !applicationSettings.value) needsWork.value = true;
  },
);
function workTone(status: AdminJson | undefined) {
  return status === 'succeeded'
    ? 'success'
    : status === 'failed' || status === 'refused'
      ? 'danger'
      : status === 'recovery_required' || status === 'cancelled'
        ? 'warning'
        : 'info';
}
function cancellable(row: AdminOperationData) {
  return (
    ['pending', 'running', 'failed', 'recovery_required'].includes(
      String(row.status),
    ) &&
    !!row.cancellable &&
    !row.cancellationRequested
  );
}
const workColumnState = [
  { key: 'operation', visible: true, width: 220 },
  { key: 'status', visible: true, width: 200 },
  { key: 'progress', visible: true, width: 120 },
];
const workColumns = computed<TableColumn<AdminOperationData>[]>(() => [
  {
    key: 'operation',
    label: tr('Operation'),
    value: (row) =>
      typeof row.operation === 'string' ? tr(row.operation) : '—',
  },
  {
    key: 'status',
    label: tr('Status'),
    value: (row) => (typeof row.status === 'string' ? tr(row.status) : '—'),
  },
  {
    key: 'progress',
    label: tr('Progress'),
    value: (row) =>
      typeof row.progress === 'string' || typeof row.progress === 'number'
        ? String(row.progress)
        : '—',
  },
]);

watch(
  () => [controller.contextRevision.value, props.admin.state.value],
  async () => {
    commandRequest++;
    pendingCommand.value = undefined;
    capabilitiesReady.value = false;
    capabilities.value = [];
    selected.value = '';
    clearWork();
    preview.value = undefined;
    result.value = undefined;
    diagnosis.value = undefined;
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
// Validate current capabilities in place without resetting the selected content.
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
    if (!applicationSettings.value) await refresh();
  },
);
async function exportWork() {
  const next = await controller.read('work', {
    module: selected.value,
    limit: 100,
    export: true,
  });
  if (!next || !Array.isArray(next.work)) throw new Error('permission_denied');
  return next.work.filter(
    (row): row is AdminOperationData =>
      !!row && typeof row === 'object' && !Array.isArray(row),
  );
}
defineExpose({ controller });
</script>
<template>
  <slot :controller="controller">
    <div class="space-y-6">
      <h1 class="text-xl font-semibold">
        {{ tr(applicationSettings ? 'Application settings' : 'Modules') }}
      </h1>
      <FormNotifications
        v-if="(!applicationSettings || !capabilitiesReady) && !selected"
        :locale="adminLocale"
        :error="tr(controller.error.value)"
      >
        <template #actions
          ><Button :disabled="controller.busy.value" @click="refresh()">{{
            tr('Retry')
          }}</Button></template
        >
      </FormNotifications>
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
        <section :aria-busy="diagnosticsLoading" data-module-diagnostics>
          <ContentLoading
            class="space-y-5"
            :loading="false"
            :refreshing="diagnosticsLoading && !filterLoading"
            :label="tr('Refreshing module state')"
          >
            <div class="flex flex-wrap items-end justify-end gap-4">
              <div class="flex flex-wrap items-center gap-4">
                <span
                  v-if="diagnosticsLoading && !filterLoading"
                  class="flex items-center gap-2 text-xs text-muted"
                  data-diagnostics-refresh
                  >{{ tr('Refreshing…') }}</span
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
            <div
              v-if="filterLoading"
              class="grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3"
              role="status"
              :aria-label="tr('Refreshing module state')"
              data-module-filter-loading
            >
              <Skeleton v-for="index in 3" :key="index" shape="block" />
            </div>
            <ul
              v-else
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
              v-if="
                !filterLoading &&
                !modules.length &&
                !controller.refreshing.value
              "
              class="py-8 text-center text-sm text-muted"
            >
              {{ tr('No modules match this filter.') }}
            </p>
          </ContentLoading>
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
      <FormNotifications
        v-if="!reviewOpen && !diagnosisOpen"
        :locale="adminLocale"
        :error="tr(controller.error.value)"
      />
      <div class="space-y-6" data-module-content>
        <div class="flex flex-wrap items-start gap-3" data-module-actions>
          <div
            v-if="
              has('modules.manage') &&
              selectedModule?.kind === 'optional' &&
              selectedModule.registered !== false
            "
            class="flex flex-wrap gap-3"
            data-module-lifecycle-controls
          >
            <Button
              v-if="!selectedModule.enabled"
              :disabled="controller.busy.value"
              @click="review('enable')"
              >{{ tr('Enable module') }}</Button
            >
            <Button
              v-else
              tone="warning"
              :disabled="controller.busy.value"
              @click="review('disable')"
              >{{ tr('Disable module') }}</Button
            >
            <Button
              variant="outline"
              tone="danger"
              :disabled="controller.busy.value"
              @click="review('unregister')"
              >{{ tr('Unregister module') }}</Button
            >
          </div>
          <Button
            v-if="has('health.diagnose')"
            class="ml-auto"
            variant="outline"
            :busy="pendingCommand === 'health.diagnose'"
            :disabled="controller.busy.value"
            @click="diagnose"
            >{{ tr('Diagnose now') }}</Button
          >
        </div>
        <component
          :is="presentation.moduleCard ?? AdminModuleCard"
          v-if="selectedModule"
          :module="selectedModule"
          :interactive="false"
          details
        />
        <AdminOperationResult v-if="result" :result="result" />
        <component
          v-if="has('modules.manage') || has('settings.manage')"
          :is="presentation.operationHistory ?? AdminOperationHistory"
          :key="selected"
          :controller="controller"
          :module="selected"
          :export-allowed="has('tables.export')"
          :pending="!!pendingCommand"
          @execute="historyAction"
        />
        <section
          v-if="has('work.read')"
          class="space-y-3 border-t border-line pt-5"
          :aria-busy="workLoading"
          data-active-work
        >
          <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 class="font-semibold">{{ tr('Task history') }}</h3>
          </div>
          <Table
            v-model:query="workQuery"
            :export-allowed="has('tables.export')"
            :export-rows="exportWork"
            :refresh="readWork"
            :refreshing="workLoading"
            :refresh-disabled="
              controller.busy.value ||
              controller.refreshing.value ||
              !!pendingCommand
            "
            mode="client"
            :rows="activeWork"
            :columns="workColumns"
            :column-state="workColumnState"
            :row-key="(row) => String(row.workId)"
            :loading="workLoading && !workLoaded"
            retain-empty-frame
            :english="adminLocale === 'en'"
            :searchable="false"
            :caption="tr('Task history')"
          >
            <template #cell-status="{ row }"
              ><Badge
                :tone="
                  row.cancellationRequested ? 'warning' : workTone(row.status)
                "
                >{{
                  row.cancellationRequested
                    ? tr('Cancellation requested; completion is not confirmed.')
                    : tr(String(row.status))
                }}</Badge
              ></template
            >
            <template #actions="{ row }">
              <Button
                v-if="has('work.cancel') && cancellable(row)"
                size="sm"
                tone="warning"
                :disabled="controller.busy.value"
                @click="
                  command('work.cancel', { id: row.workId ?? null }, true)
                "
                >{{ tr('Request cancellation') }}</Button
              >
              <Button
                size="sm"
                :disabled="controller.busy.value || controller.refreshing.value"
                @click="
                  controller
                    .read('work.status', { id: row.workId ?? null })
                    .then((value) => {
                      if (value) result = value;
                    })
                "
                >{{ tr('Refresh work status') }}</Button
              >
            </template>
          </Table>
          <p v-if="work?.truncated" class="text-xs text-muted">
            {{ tr('Additional task history is omitted.') }}
          </p>
        </section>
      </div>

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
  <Dialog
    :model-value="diagnosisOpen"
    :loading="!diagnosis && !controller.error.value"
    :loading-label="tr('Loading operational information…')"
    :title="tr('Diagnostic result')"
    :description="getModulePresentation(selected).name[adminLocale]"
    :locale="adminLocale"
    :busy="pendingCommand === 'health.diagnose'"
    initial-focus="[data-diagnosis-close]"
    @update:model-value="!$event && closeDiagnosis()"
  >
    <div class="space-y-4" data-module-diagnosis>
      <AdminOperationResult v-if="diagnosis" :result="diagnosis" />
      <FormNotifications
        :locale="adminLocale"
        :error="tr(controller.error.value)"
      />
    </div>
    <template #footer>
      <Button
        v-if="controller.error.value && !diagnosis"
        :disabled="controller.busy.value"
        @click="diagnose"
        >{{ tr('Retry') }}</Button
      >
      <Button
        data-diagnosis-close
        variant="outline"
        :disabled="pendingCommand === 'health.diagnose'"
        @click="closeDiagnosis"
        >{{ tr('Close') }}</Button
      >
    </template>
  </Dialog>
  <Dialog
    :model-value="reviewOpen"
    :loading="previewLoading"
    :loading-label="tr('Loading operational information…')"
    :title="lifecycleLabel"
    :description="getModulePresentation(selected).name[adminLocale]"
    :locale="adminLocale"
    :busy="controller.busy.value"
    initial-focus="[data-lifecycle-cancel]"
    size="lg"
    @update:model-value="!$event && closeReview()"
  >
    <div class="space-y-4" data-module-lifecycle-review>
      <p class="text-sm">
        {{ tr('Confirm the module action after reviewing its effects.') }}
      </p>
      <FormNotifications
        :locale="adminLocale"
        :error="tr(controller.error.value)"
      />
      <template v-if="preview">
        <div class="space-y-3 rounded border border-line p-4">
          <Badge :tone="preview.ready ? 'success' : 'warning'">{{
            preview.ready ? tr('Ready to apply') : tr('Transition blocked')
          }}</Badge>
          <div v-if="list(preview.affected).length" class="space-y-2 text-xs">
            <h4 class="font-semibold">{{ tr('Affected modules') }}</h4>
            <ul class="space-y-1">
              <li v-for="id in list(preview.affected)" :key="id">
                {{ getModulePresentation(id).name[adminLocale] }}
              </li>
            </ul>
          </div>
          <dl v-if="entries(preview.changes).length" class="space-y-2 text-xs">
            <dt class="font-semibold">{{ tr('Planned changes') }}</dt>
            <dd
              v-for="[id, enabled] in entries(preview.changes)"
              :key="id"
              class="flex flex-wrap justify-between gap-2"
            >
              <span>{{ getModulePresentation(id).name[adminLocale] }}</span
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
            <dd v-for="[id, counts] in entries(preview.active_work)" :key="id">
              <span>{{ getModulePresentation(id).name[adminLocale] }}</span>
              <ul>
                <li v-for="[state, count] in entries(counts)" :key="state">
                  {{ tr(state) }}: {{ count }}
                </li>
              </ul>
            </dd>
          </dl>
        </div>
      </template>
      <Button
        v-else-if="!previewLoading"
        :disabled="controller.busy.value"
        @click="review(intent)"
        >{{ tr('Review again') }}</Button
      >
    </div>
    <template #footer>
      <Button
        data-lifecycle-cancel
        variant="outline"
        :disabled="controller.busy.value"
        @click="closeReview"
        >{{ tr('Cancel') }}</Button
      >
      <Button
        :tone="intent === 'enable' ? 'primary' : 'danger'"
        :busy="pendingCommand === 'modules.apply'"
        :disabled="
          controller.busy.value ||
          previewLoading ||
          !preview?.ready ||
          typeof preview.review !== 'string'
        "
        @click="applyReview"
        >{{ lifecycleLabel }}</Button
      >
    </template>
  </Dialog>
  <ConfirmDialog
    :model-value="!!controller.confirmation.value"
    :title="tr(controller.confirmation.value ?? '')"
    :locale="adminLocale"
    @confirm="controller.confirm(true)"
    @cancel="controller.confirm(false)"
    @update:model-value="!$event && controller.confirm(false)"
  />
</template>
