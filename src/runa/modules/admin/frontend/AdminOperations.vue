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
  Tabs,
  Spinner,
  Skeleton,
  Badge,
  useIdentity,
  getModulePresentation,
  usePageResolver,
  Alert,
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
import AdminOperationalEditor from './AdminOperationalEditor.vue';
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
function sectionHref(section: boolean | 'settings' | 'integrations') {
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
  href: sectionHref(editor.value ?? applicationSettings.value),
}));
function navigateSection(href: string, event: MouseEvent) {
  void pageNavigation.navigate(href, event);
}
watch(applicationSettings, () => {
  editor.value = undefined;
});
const editor = ref<'settings' | 'integrations'>();
const work = shallowRef<AdminOperationData>();
const preview = shallowRef<AdminOperationData>();
const result = shallowRef<AdminOperationData>();
const intent = ref('disable');
const has = (permission: string) => capabilities.value.includes(permission);
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
    }
    const section = url.searchParams.get('section');
    editor.value =
      selected.value && section === 'settings' && has('settings.read')
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
  preview.value = undefined;
  controller.dirty.value = false;
}
watch(filter, () => {
  if (capabilitiesReady.value) void refresh(true);
});
const selectedModule = computed(() =>
  modules.value.find((module) => module.id === selected.value),
);
const moduleTabs = computed(() => [
  { id: 'overview', label: tr('Overview') },
  ...(has('settings.read')
    ? [
        {
          id: 'settings',
          label: tr('Settings'),
        },
      ]
    : []),
  ...(has('integrations.manage')
    ? [
        {
          id: 'integrations',
          label: tr('Integrations'),
        },
      ]
    : []),
]);
async function changeModuleTab(tab: string) {
  if (
    controller.busy.value ||
    !moduleTabs.value.some((item) => item.id === tab)
  )
    return;
  await pageNavigation.navigate(
    sectionHref(
      tab === 'overview' ? false : (tab as 'settings' | 'integrations'),
    ),
  );
}
async function closeModule() {
  const url = new URL(pageNavigation.href.value);
  url.searchParams.delete('module');
  url.searchParams.delete('section');
  if (await pageNavigation.navigate(url.pathname + url.search + url.hash)) {
    clearWork();
    preview.value = undefined;
    result.value = undefined;
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
  const own = ++commandRequest;
  const context = controller.contextRevision.value;
  pendingCommand.value = name;
  if (name === 'health.diagnose') result.value = undefined;
  try {
    const response = await controller.execute(name, input, destructive);
    if (
      response &&
      own === commandRequest &&
      context === controller.contextRevision.value
    ) {
      result.value = response;
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
  } finally {
    if (own === commandRequest) pendingCommand.value = undefined;
  }
}
async function review() {
  preview.value = await controller.execute('modules.preview', {
    module: selected.value,
    intent: intent.value,
  });
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
  [selected, editor, capabilitiesReady],
  () => {
    clearWork();
    needsWork.value =
      !!selected.value && !editor.value && capabilitiesReady.value;
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
      !editor.value &&
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
    if (selected.value && !editor.value) needsWork.value = true;
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
    editor.value = undefined;
    clearWork();
    preview.value = undefined;
    result.value = undefined;
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
        }}<Button @click="refresh()">{{ tr('Retry') }}</Button></Alert
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
          :aria-busy="diagnosticsLoading"
          data-module-diagnostics
        >
          <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="py-3 text-base font-semibold">{{ tr('Modules') }}</h2>
            <div class="flex flex-wrap items-center gap-4">
              <span
                v-if="diagnosticsLoading && !filterLoading"
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
              !filterLoading && !modules.length && !controller.refreshing.value
            "
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
      <Tabs
        :model-value="editor ?? 'overview'"
        :items="moduleTabs"
        :label="tr('Module operations')"
        @update:model-value="changeModuleTab"
      >
        <template #overview>
          <div v-if="!editor" class="space-y-6">
            <div class="flex flex-wrap justify-end gap-3">
              <Button
                v-if="has('health.diagnose')"
                variant="outline"
                :busy="pendingCommand === 'health.diagnose'"
                :disabled="controller.busy.value"
                @click="command('health.diagnose', { module: selected })"
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
            <AdminOperationResult v-if="result" :result="result" />
            <component
              v-if="has('modules.manage') || has('settings.manage')"
              :is="presentation.operationHistory ?? AdminOperationHistory"
              :key="selected"
              :controller="controller"
              :module="selected"
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
                <Button
                  variant="outline"
                  size="sm"
                  :busy="workLoading"
                  :disabled="
                    controller.busy.value ||
                    controller.refreshing.value ||
                    !!pendingCommand
                  "
                  @click="readWork"
                  >{{ tr('Refresh tasks') }}</Button
                >
              </div>
              <Table
                v-model:query="workQuery"
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
                      row.cancellationRequested
                        ? 'warning'
                        : workTone(row.status)
                    "
                    >{{
                      row.cancellationRequested
                        ? tr(
                            'Cancellation requested; completion is not confirmed.',
                          )
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
                    :disabled="
                      controller.busy.value || controller.refreshing.value
                    "
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
            @saved="refresh()"
          />
        </template>
      </Tabs>

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
