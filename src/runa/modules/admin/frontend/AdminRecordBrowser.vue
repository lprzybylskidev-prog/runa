<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale, t: tr } = useAdminLocale();
import { computed, ref, watch } from 'vue';
import { Button, Card, Dialog } from '@/runa/ui';
import type {
  AdminRecordBrowserProps,
  AdminRecordBrowserSlots,
} from './contracts';
import { useAdminTable } from './useAdminTable';
import AdminForm from './AdminForm.vue';
import AdminResourceTable from './AdminResourceTable.vue';
import AdminSectionContent from './AdminSectionContent.vue';
import AdminComponentBoundary from './AdminComponentBoundary.vue';
const props = defineProps<AdminRecordBrowserProps>();
defineSlots<AdminRecordBrowserSlots>();
const open = ref(false);
const table = useAdminTable(props.controller, 'catalogue', {
  select: async (id) => {
    if (!(await props.form.selectSubject(id))) return false;
    open.value = true;
    return true;
  },
});
if (props.initialFilters && Object.keys(props.initialFilters).length)
  table.query.value = { ...table.query.value, filters: props.initialFilters };
const sections = computed(
  () =>
    props.controller.page.value?.sections.filter(
      (section) => section.key !== 'catalogue',
    ) ?? [],
);
async function close() {
  if (!(await props.form.beforeLeave())) return;
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
    <Card>
      <slot name="toolbar" :table="table" />
      <Button
        v-if="allowCreate"
        intent="commit"
        class="mb-4"
        :disabled="form.busy.value"
        @click="table.select(null)"
        >{{ tr('New custom definition') }}</Button
      >
      <AdminResourceTable :controller="table">
        <template #actions="{ row }"
          ><Button
            intent="inspect"
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(row.public_id)"
            >{{ tr('Details') }}</Button
          ></template
        >
      </AdminResourceTable>
    </Card>
    <Dialog
      :model-value="open"
      :title="
        tr(
          sections.length === 1
            ? sections[0]!.title
            : (controller.page.value?.title ?? 'Details'),
        )
      "
      :locale="locale"
      size="xl"
      :busy="form.busy.value"
      @update:model-value="!$event && close()"
    >
      <AdminForm :controller="form">
        <div class="space-y-6">
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
        </div>
      </AdminForm>
    </Dialog>
  </div>
</template>
