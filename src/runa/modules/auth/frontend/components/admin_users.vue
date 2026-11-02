<script setup lang="ts">
import AuthDirectoryFilters from '../AuthDirectoryFilters.vue';
import { computed, ref, watch } from 'vue';
import { Badge, Button, Card, Dialog, Tabs, Select } from '@/runa/ui';
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
      'two_factor_enabled',
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
const bulkChoices = computed(() =>
  (props.controller.page.value?.sections ?? [])
    .filter((s) => ['bulk_sessions', 'bulk_users'].includes(s.key))
    .flatMap((section) =>
      section.commands
        .filter((c) => c.available)
        .map((command) => ({
          section,
          command,
          value: section.key + ':' + command.key,
          label: tr(command.title),
        })),
    ),
);
const canBulk = computed(() => bulkChoices.value.length > 0);
const bulkChoice = ref('');
const activeBulk = ref('bulk_sessions:revoke');
const chosenBulk = computed(() =>
  bulkChoices.value.find((c) => c.value === bulkChoice.value),
);
const openedBulk = computed(() =>
  bulkChoices.value.find((c) => c.value === activeBulk.value),
);
const bulkLimit = computed(() =>
  chosenBulk.value?.section.key === 'bulk_users' ? 99 : 100,
);
watch(
  bulkChoices,
  (choices) => {
    if (!choices.some((c) => c.value === bulkChoice.value))
      bulkChoice.value = choices[0]?.value ?? '';
  },
  { immediate: true },
);
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
    return openedBulk.value
      ? tr(openedBulk.value.command.title)
      : tr('Details');
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
  const command = openedBulk.value?.command;
  return {
    ...section,
    fields: section.fields.filter(
      (f) =>
        f.key === 'summary' ||
        (f.key !== 'public_ids' && command?.inputs.includes(f.key)),
    ),
    commands: command ? [command] : [],
  };
}
async function openBulk() {
  const chosen = chosenBulk.value;
  if (
    !chosen ||
    !selection.value.length ||
    selection.value.length > bulkLimit.value
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
      <AdminResourceTable :controller="table" :selectable="!!canBulk">
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
            class="flex min-w-0 flex-wrap items-end gap-3 border-b border-line px-4 py-3"
            data-auth-bulk-actions
          >
            <div v-if="canBulk" class="w-full sm:w-96">
              <Select
                v-model="bulkChoice"
                :label="tr({ pl: 'Akcja zbiorcza', en: 'Bulk action' })"
                :options="
                  bulkChoices.map((c) => ({ value: c.value, label: c.label }))
                "
                :locale="adminLocale"
                :disabled="form.busy.value"
              />
            </div>
            <Button
              v-if="canBulk"
              size="sm"
              variant="outline"
              :disabled="
                form.busy.value ||
                !selection.length ||
                selection.length > bulkLimit
              "
              @click="openBulk"
            >
              {{
                tr({ pl: 'Wykonaj dla zaznaczonych', en: 'Apply to selected' })
              }}
              ({{ selection.length }})
            </Button>
            <p
              v-if="
                canBulk &&
                (table.selection.value.mode !== 'explicit' ||
                  selection.length > bulkLimit)
              "
              class="text-sm text-muted"
            >
              {{
                tr({
                  pl: 'Zaznacz konkretne konta. Limit na operację:',
                  en: 'Select specific accounts. Per-operation limit:',
                })
              }}
              {{ bulkLimit }}.
            </p>
          </div>
        </template>
        <template
          v-for="key in [
            'active',
            'approved',
            'logged_in',
            'verified',
            'security_locked',
            'two_factor_enabled',
            'two_factor_required',
            'totp_enrolled',
            'email_factor_enrolled',
            'password_set',
            'password_expired',
            'invitation_pending',
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
