<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Badge, Button, Card, Dialog, Tabs, TablePreferences } from '@/runa/ui';
import {
  AdminComponentBoundary,
  AdminForm,
  AdminResourceTable,
  AdminSectionContent,
  useAdminLocale,
  useAdminTable,
} from '@/runa/admin';
import type { AdminPageProps, AdminSectionData } from '@/runa/admin';
const props = defineProps<AdminPageProps>();
const { locale: adminLocale, t: tr } = useAdminLocale();
const open = ref(false);
const mode = ref<'record' | 'create' | 'bulk'>('record');
const tab = ref('lifecycle');
const visited = ref(new Set<string>());
watch(tab, (key) => {
  visited.value = new Set([...visited.value, key]);
});
const table = useAdminTable(props.controller, 'directory', {
  select: async (id) => {
    if (!(await props.form.selectSubject(id))) return false;
    if (id !== null) {
      // Shared Save requires the complete authorized atomic participant set,
      // even while its presentation lives in another tab.
      await Promise.all(
        (props.controller.page.value?.sections ?? [])
          .filter((section) =>
            section.commands.some(
              (command) =>
                command.mode === 'atomic' && command.intent === 'save',
            ),
          )
          .map((section) => props.form.activateSection(section.key)),
      );
    }
    mode.value = id === null ? 'create' : 'record';
    tab.value = sections.value.some((s) => s.key === 'lifecycle')
      ? 'lifecycle'
      : (sections.value[0]?.key ?? '');
    visited.value = new Set([tab.value]);
    open.value = true;
    return true;
  },
});
// Keep the default table readable; every authorized extra column stays available
// in the shared column picker and the account detail tabs.
let initializedColumns = false;
watch(
  table.columns,
  (columns) => {
    if (initializedColumns || !columns.length) return;
    initializedColumns = true;
    const visible = new Set([
      'display_name',
      'email',
      'active',
      'logged_in',
      'verified',
      'state',
      'created_at',
    ]);
    table.views.setColumns(
      columns.map((column) => ({
        key: column.key,
        visible: visible.has(column.key),
      })),
    );
  },
  { immediate: true },
);
const directory = computed(() => table.section.value);
const bulk = computed(() =>
  props.controller.page.value?.sections.find((s) => s.key === 'bulk_sessions'),
);
const canBulk = computed(() => bulk.value?.commands.some((c) => c.available));
const bulkIds = ref<string[]>([]);
const selection = computed(() => {
  const selected = table.selection.value;
  return selected.mode === 'explicit' ? selected.publicIds : [];
});
const sections = computed(() =>
  (props.controller.page.value?.sections ?? []).filter((section) => {
    if (mode.value === 'bulk') return section.key === 'bulk_sessions';
    if (mode.value === 'create') return section.key === 'creation';
    return !['directory', 'creation', 'bulk_sessions'].includes(section.key);
  }),
);
const tabs = computed(() =>
  sections.value.map((s) => ({
    id: s.key,
    label: tr(
      s.key === 'lifecycle'
        ? { pl: 'Stan konta', en: 'Account status' }
        : s.title,
    ),
  })),
);
const canCreate = computed(() =>
  props.controller.page.value?.sections.some(
    (s) => s.key === 'creation' && s.commands.some((c) => c.available),
  ),
);
const title = computed(() => {
  if (mode.value === 'create') return tr('Create account');
  if (mode.value === 'bulk')
    return tr({
      pl: 'Unieważnij sesje wybranych kont',
      en: 'Revoke selected account sessions',
    });
  const result = table.resource.result.value;
  const row =
    result && 'items' in result && !('field' in result)
      ? result.items.find((r) => r.public_id === table.selected.value)
      : null;
  return typeof row?.values.display_name === 'string'
    ? row.values.display_name
    : tr('Details');
});
function presentation(section: AdminSectionData): AdminSectionData {
  if (section.key !== 'bulk_sessions') return section;
  // Selection comes from the table; the operator cannot accidentally change its scope.
  return {
    ...section,
    fields: section.fields.filter((f) => f.key !== 'public_ids'),
  };
}
async function openBulk() {
  if (!canBulk.value || !selection.value.length || selection.value.length > 100)
    return;
  const ids = [...selection.value];
  if (!(await props.form.beforeLeave())) return;
  if (!(await props.form.editSection('bulk_sessions', null))) return;
  props.form.set(props.form.fieldPath('bulk_sessions', 'public_ids'), ids);
  bulkIds.value = ids;
  mode.value = 'bulk';
  open.value = true;
}
async function close() {
  if (!(await props.form.beforeLeave())) return;
  if (
    mode.value === 'bulk' &&
    !(await props.form.closeSection('bulk_sessions'))
  )
    return;
  props.form.reset();
  open.value = false;
  table.selected.value = undefined;
}
watch(
  () => props.controller.state.value,
  (state) => {
    if (state !== 'ready') open.value = false;
  },
);
</script>
<template>
  <div class="space-y-6">
    <h1 class="text-xl font-semibold">
      {{ tr(controller.page.value?.title ?? '') }}
    </h1>
    <Card v-if="directory">
      <template #header
        ><h2>{{ tr('User directory') }}</h2></template
      >
      <AdminResourceTable :controller="table" :selectable="!!canBulk">
        <template #toolbar>
          <TablePreferences
            :controller="table.views"
            :columns="table.columns.value"
            :english="adminLocale === 'en'"
          />
          <Button
            v-if="canCreate"
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(null)"
            >{{ tr('Create account') }}</Button
          >
          <Button
            v-if="canBulk"
            size="sm"
            variant="outline"
            :disabled="
              form.busy.value || !selection.length || selection.length > 100
            "
            @click="openBulk"
          >
            {{ tr({ pl: 'Unieważnij sesje', en: 'Revoke sessions' }) }} ({{
              selection.length
            }})
          </Button>
          <p
            v-if="
              canBulk &&
              (table.selection.value.mode !== 'explicit' ||
                selection.length > 100)
            "
            class="text-sm text-muted"
          >
            {{
              tr({
                pl: 'Zaznacz konkretne konta — maksymalnie 100 na operację.',
                en: 'Select specific accounts — at most 100 per operation.',
              })
            }}
          </p>
        </template>
        <template
          v-for="key in [
            'active',
            'approved',
            'logged_in',
            'verified',
            'security_locked',
          ]"
          :key="key"
          #[`cell-${key}`]="{ row }"
        >
          <Badge
            v-if="typeof row.values[key] === 'boolean'"
            size="sm"
            :tone="
              row.values[key]
                ? key === 'security_locked'
                  ? 'danger'
                  : 'success'
                : 'neutral'
            "
          >
            {{ tr(row.values[key] ? 'Yes' : 'No') }}
          </Badge>
          <span v-else>—</span>
        </template>
        <template #actions="{ row }">
          <Button
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(row.public_id)"
            >{{ tr('Details') }}</Button
          >
        </template>
      </AdminResourceTable>
    </Card>
    <Dialog
      :model-value="open"
      :title="title"
      :locale="adminLocale"
      size="xl"
      :busy="form.busy.value"
      @update:model-value="!$event && close()"
    >
      <AdminForm :controller="form">
        <p v-if="mode === 'bulk'" class="mb-4 text-sm text-muted">
          {{ tr({ pl: 'Liczba wybranych kont', en: 'Selected accounts' }) }}:
          {{ bulkIds.length }}
        </p>
        <Tabs
          class="min-w-0"
          v-if="mode === 'record'"
          v-model="tab"
          :items="tabs"
          :label="tr('Details')"
        >
          <template
            v-for="section in sections"
            :key="section.key"
            #[section.key]
          >
            <AdminComponentBoundary
              v-if="visited.has(section.key)"
              :identity="controller.page.value!.revision + ':' + section.key"
              :on-failure="(failed) => form.setAvailable(section.key, !failed)"
            >
              <AdminSectionContent
                :page="controller"
                :section="section"
                :controller="form"
                :components="components"
              />
            </AdminComponentBoundary>
          </template>
        </Tabs>
        <div v-else class="space-y-6">
          <AdminComponentBoundary
            v-for="section in sections"
            :key="section.key"
            :identity="controller.page.value!.revision + ':' + section.key"
            :on-failure="(failed) => form.setAvailable(section.key, !failed)"
          >
            <AdminSectionContent
              :page="controller"
              :section="presentation(section)"
              :controller="form"
              :components="components"
            />
          </AdminComponentBoundary>
        </div>
        <template v-if="mode !== 'record'" #actions><span /></template>
      </AdminForm>
    </Dialog>
  </div>
</template>
