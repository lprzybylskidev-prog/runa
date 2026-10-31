<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { computed } from 'vue';
import { Button, Card } from '@/runa/ui';
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
const table = useAdminTable(props.controller, 'catalogue', {
  select: (id) => props.form.selectSubject(id),
});
if (props.initialFilters && Object.keys(props.initialFilters).length)
  table.query.value = { ...table.query.value, filters: props.initialFilters };
const sections = computed(
  () =>
    props.controller.page.value?.sections.filter(
      (section) => section.key !== 'catalogue',
    ) ?? [],
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
        class="mb-4"
        :disabled="form.busy.value"
        @click="table.select(null)"
        >{{ tr('New custom definition') }}</Button
      >
      <AdminResourceTable :controller="table">
        <template #actions="{ row }"
          ><Button
            size="sm"
            :disabled="form.busy.value"
            @click="table.select(row.public_id)"
            >{{ tr('Open record') }}</Button
          ></template
        >
      </AdminResourceTable>
    </Card>
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
  </div>
</template>
