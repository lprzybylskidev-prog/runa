<script setup lang="ts">
import AuthAdminUserDetails from '../AuthAdminUserDetails.vue';
import AuthAdminActionGroups from '../AuthAdminActionGroups.vue';
import { groupAuthActions, authBulkGroup, authActionTone } from '../bulkGroups';
import AuthDirectoryFilters from '../AuthDirectoryFilters.vue';
import { computed, ref, watch } from 'vue';
import { Button, Card, ContentLoading, Dialog } from '@/runa/ui';
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
const selecting = ref(false);
const mode = ref<'record' | 'create' | 'bulk'>('record');
const table = useAdminTable(props.controller, 'directory', {
  omitColumns: ['state'],
  select: async (id) => {
    mode.value = id === null ? 'create' : 'record';
    selecting.value = true;
    open.value = true;
    if (!(await props.form.selectSubject(id))) {
      selecting.value = false;
      open.value = false;
      return false;
    }
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
    selecting.value = false;
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
      'two_factor_enabled',
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
const bulkChoices = computed(() =>
  (props.controller.page.value?.sections ?? [])
    .filter((s) => ['bulk_sessions', 'bulk_users'].includes(s.key))
    .flatMap((section) => {
      const commands = section.commands.filter((command) => command.available);
      const seen = new Set<string>();
      return commands.flatMap((command) => {
        const group =
          section.key === 'bulk_users' ? authBulkGroup(command.key) : undefined;
        const key = group?.key ?? command.key;
        if (seen.has(key)) return [];
        seen.add(key);
        const grouped = group
          ? commands.filter((candidate) =>
              group.commands.some((key) => key === candidate.key),
            )
          : [command];
        return [
          {
            section,
            command,
            commands: grouped,
            value: section.key + ':' + key,
            label: tr(
              group?.title ??
                (section.key === 'bulk_sessions'
                  ? { pl: 'Unieważnij sesje', en: 'Revoke sessions' }
                  : command.title),
            ),
          },
        ];
      });
    }),
);
const bulkGroups = computed(() =>
  groupAuthActions(bulkChoices.value, (choice) => choice.command.key),
);
const canBulk = computed(() => bulkChoices.value.length > 0);
const activeBulk = ref('');
const openedBulk = computed(() =>
  bulkChoices.value.find((c) => c.value === activeBulk.value),
);
function bulkLimit(section: AdminSectionData): number {
  return section.key === 'bulk_users' ? 99 : 100;
}
const bulkIds = ref<string[]>([]);
const selection = computed(() => {
  const selected = table.selection.value;
  return selected.mode === 'explicit' ? selected.publicIds : [];
});
const sections = computed(() =>
  (props.controller.page.value?.sections ?? []).filter((section) => {
    if (mode.value === 'bulk')
      return section.key === openedBulk.value?.section.key;
    if (mode.value === 'create') return section.key === 'creation';
    return !['directory', 'creation', 'bulk_sessions', 'bulk_users'].includes(
      section.key,
    );
  }),
);
const canCreate = computed(() =>
  props.controller.page.value?.sections.some(
    (s) => s.key === 'creation' && s.commands.some((c) => c.available),
  ),
);
const title = computed(() => {
  if (mode.value === 'create') return tr('Create account');
  if (mode.value === 'bulk')
    return openedBulk.value ? openedBulk.value.label : tr('Details');
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
  if (!['bulk_sessions', 'bulk_users'].includes(section.key)) return section;
  const commands = openedBulk.value?.commands ?? [];
  return {
    ...section,
    fields: section.fields.filter(
      (f) =>
        f.key === 'summary' ||
        (f.key !== 'public_ids' &&
          commands.some((command) => command.inputs.includes(f.key))),
    ),
    commands,
  };
}
async function openBulk(chosen: (typeof bulkChoices.value)[number]) {
  if (
    !chosen ||
    !selection.value.length ||
    selection.value.length > bulkLimit(chosen.section)
  )
    return;
  const ids = [...selection.value];
  if (!(await props.form.beforeLeave())) return;
  if (!(await props.form.editSection(chosen.section.key, null))) return;
  props.form.set(props.form.fieldPath(chosen.section.key, 'public_ids'), ids);
  activeBulk.value = chosen.value;
  bulkIds.value = ids;
  mode.value = 'bulk';
  open.value = true;
}
async function close() {
  if (!(await props.form.beforeLeave())) return;
  props.form.reset();
  if (
    mode.value === 'bulk' &&
    !(await props.form.closeSection(
      openedBulk.value?.section.key ?? 'bulk_sessions',
    ))
  )
    return;
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
      <template #actions>
        <Button
          v-if="canCreate"
          size="sm"
          :disabled="form.busy.value"
          @click="table.select(null)"
          >{{ tr('Create account') }}</Button
        >
      </template>
      <AdminResourceTable
        :controller="table"
        :selectable="!!canBulk"
        :row-class="
          (row) =>
            row.values.state === 'deleted' ? 'auth-deleted-row' : undefined
        "
      >
        <template #filters="{ query, update, resetKey, setValidity }">
          <AuthDirectoryFilters
            :fields="table.section.value?.fields ?? []"
            :query="query"
            :update="update"
            :reset-key="resetKey"
            :set-validity="setValidity"
          />
        </template>
        <template #afterFilters>
          <div
            v-if="canBulk"
            class="space-y-3 border-b border-line px-4 py-3"
            data-auth-bulk-actions
          >
            <p class="text-xs text-muted">
              {{ tr({ pl: 'Zaznaczone konta', en: 'Selected accounts' }) }}:
              {{ selection.length }}
            </p>
            <AuthAdminActionGroups :groups="bulkGroups">
              <template #default="{ item: choice }">
                <Button
                  :data-bulk-command="choice.value"
                  size="sm"
                  variant="outline"
                  :tone="authActionTone(choice.command.key, true)"
                  :disabled="
                    form.busy.value ||
                    !selection.length ||
                    selection.length > bulkLimit(choice.section)
                  "
                  @click="openBulk(choice)"
                  >{{ choice.label }}</Button
                >
              </template>
            </AuthAdminActionGroups>
            <p
              v-if="
                table.selection.value.mode !== 'explicit' ||
                selection.length > 99
              "
              class="text-sm text-muted"
            >
              {{
                tr({
                  pl: 'Zaznacz konkretne konta. Limit: 99 kont dla zmian, 100 dla sesji.',
                  en: 'Select specific accounts. Limit: 99 for user changes, 100 for sessions.',
                })
              }}
            </p>
          </div>
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
      :size="mode === 'bulk' ? 'md' : 'xl'"
      :busy="form.busy.value"
      @update:model-value="!$event && close()"
    >
      <ContentLoading
        v-if="selecting"
        shape="form"
        :label="tr('Loading record details')"
      />
      <template v-else-if="mode === 'create'">
        <AdminComponentBoundary
          v-for="section in sections"
          :key="section.key"
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
      <AuthAdminUserDetails
        v-else-if="mode === 'record'"
        :controller="controller"
        :form="form"
        :components="components"
      />
      <AdminForm v-else :controller="form">
        <div class="space-y-6">
          <AdminComponentBoundary
            v-for="section in sections"
            :key="section.key + ':' + activeBulk"
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
        <template #actions><span /></template>
      </AdminForm>
    </Dialog>
  </div>
</template>
<style scoped>
:deep(.auth-deleted-row) {
  color: var(--runa-danger-text);
  background-color: var(--runa-danger-subtle);
}
</style>
