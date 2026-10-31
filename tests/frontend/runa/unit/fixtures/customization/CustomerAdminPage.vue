<script setup lang="ts">
import { toRef } from 'vue';
import {
  AdminLoading,
  AdminPage,
  useAdminForm,
  useAdminPage,
} from '@/runa/admin';
import type {
  AdminComponentRegistry,
  AdminController,
  AdminLocation,
} from '@/runa/admin';
import { Alert, Button } from '@/runa/ui';
const props = defineProps<{
  admin: AdminController;
  location: AdminLocation;
  components?: AdminComponentRegistry | undefined;
}>();
const page = useAdminPage(props.admin, toRef(props, 'location'));
const form = useAdminForm(page, {
  components: props.components,
  autoRead: false,
});
</script>
<template>
  <div data-customer-page>
    <AdminLoading v-if="page.state.value === 'loading'" />
    <AdminPage
      v-else-if="page.state.value === 'ready' && page.page.value"
      :controller="page"
      :form="form"
      :components="components"
    />
    <Alert v-else tone="warning">
      This application administration page is unavailable.
      <Button @click="page.refresh">Retry</Button>
    </Alert>
  </div>
</template>
