<script setup lang="ts">
import AdminOperations from './AdminOperations.vue';
import AdminDashboardWidgets from './AdminDashboardWidgets.vue';
import type {
  AdminBootstrap,
  AdminComponentRegistry,
  AdminController,
} from './contracts';
import { useAdminPresentation } from './presentation';
defineProps<{
  bootstrap: AdminBootstrap;
  admin?: AdminController | undefined;
  components?: AdminComponentRegistry | undefined;
}>();
const presentation = useAdminPresentation();
</script>
<template>
  <component
    :is="presentation.operations ?? AdminOperations"
    v-if="admin"
    :admin="admin"
  >
    <template #dashboard>
      <AdminDashboardWidgets :bootstrap="bootstrap" :components="components" />
    </template>
  </component>
  <AdminDashboardWidgets
    v-else
    :bootstrap="bootstrap"
    :components="components"
  />
</template>
