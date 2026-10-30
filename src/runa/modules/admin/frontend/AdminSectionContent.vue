<script setup lang="ts">
import { computed } from 'vue';
import { Alert, Card } from '@/runa/ui';
import type {
  AdminComponentRegistry,
  AdminSectionPresentationProps,
} from './contracts';
import { resolveAdminComponent } from './components';
import { useAdminPresentation } from './presentation';
import AdminTableSection from './AdminTableSection.vue';
import AdminRecordSection from './AdminRecordSection.vue';
const props = defineProps<
  AdminSectionPresentationProps & {
    components?: AdminComponentRegistry | undefined;
  }
>();
const presentation = useAdminPresentation();
const replacement = computed(
  () =>
    resolveAdminComponent(props.section.component, props.components)
      .component ?? presentation.value.section,
);
</script>
<template>
  <Card v-if="section.diagnostic">
    <template #header
      ><h2>{{ section.title }}</h2></template
    >
    <Alert tone="warning">This section is currently unavailable.</Alert>
  </Card>
  <component
    v-else-if="replacement"
    :is="replacement"
    :section="section"
    :page="page"
  />
  <Card v-else>
    <template #header
      ><h2>{{ section.title }}</h2></template
    >
    <AdminTableSection
      v-if="section.view === 'table'"
      :key="section.key"
      :page="page"
      :section="section.key"
    />
    <AdminRecordSection
      v-else
      :key="section.key"
      :page="page"
      :section="section.key"
      :components="components"
    />
  </Card>
</template>
