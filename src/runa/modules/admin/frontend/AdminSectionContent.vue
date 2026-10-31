<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { computed } from 'vue';
import { Alert, Card } from '@/runa/ui';
import type {
  AdminComponentRegistry,
  AdminSectionPresentationProps,
} from './contracts';
import { resolveAdminComponent } from './components';
import { useAdminPresentation } from './presentation';
import AdminTableSection from './AdminTableSection.vue';
import AdminSection from './AdminSection.vue';
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
      ><h2>{{ tr(section.title) }}</h2></template
    >
    <Alert tone="warning">{{
      tr('This section is currently unavailable.')
    }}</Alert>
  </Card>
  <component
    v-else-if="replacement"
    :is="replacement"
    :section="section"
    :page="page"
    :controller="controller"
  />
  <Card v-else>
    <template #header
      ><h2>{{ tr(section.title) }}</h2></template
    >
    <AdminTableSection
      v-if="section.view === 'table'"
      :key="section.key"
      :page="page"
      :section="section.key"
      :form="controller"
      :components="components"
    />
    <AdminSection
      v-else
      :key="section.key"
      :controller="controller"
      :section="section"
      :components="components"
    />
  </Card>
</template>
