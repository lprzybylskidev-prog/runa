<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { computed } from 'vue';
import { Button } from '@/runa/ui';
import type {
  AdminComponentRegistry,
  AdminFormController,
  AdminPageController,
} from './contracts';
import { useAdminTable } from './useAdminTable';
import AdminResourceTable from './AdminResourceTable.vue';
import AdminSection from './AdminSection.vue';
const props = defineProps<{
  page: AdminPageController;
  section: string;
  form: AdminFormController;
  components?: AdminComponentRegistry | undefined;
}>();
// The owning dispatcher keys this scope by section identity and view.
const table = useAdminTable(props.page, props.section, {
  select: (id) => props.form.editSection(props.section, id),
});
const section = computed(() =>
  props.page.page.value?.sections.find(
    (section) => section.key === props.section,
  ),
);
async function close() {
  if (await props.form.closeSection(props.section))
    table.selected.value = undefined;
}
</script>
<template>
  <AdminResourceTable :controller="table" />
  <div
    v-if="table.selected.value !== undefined && section"
    class="mt-6 space-y-4"
  >
    <Button size="sm" @click="close">{{ tr('Close record') }}</Button>
    <AdminSection
      :key="table.selected.value ?? 'new'"
      :controller="form"
      :section="section"
      :components="components"
    />
  </div>
</template>
