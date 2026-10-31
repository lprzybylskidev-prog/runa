<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale, t: tr } = useAdminLocale();
import { computed } from 'vue';
import { Dialog } from '@/runa/ui';
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
  <Dialog
    :model-value="table.selected.value !== undefined && !!section"
    :title="tr('Details')"
    :locale="locale"
    size="xl"
    :busy="form.busy.value"
    @update:model-value="!$event && close()"
  >
    <AdminSection
      v-if="section"
      :key="table.selected.value ?? 'new'"
      :controller="form"
      :section="section"
      :components="components"
    />
  </Dialog>
</template>
