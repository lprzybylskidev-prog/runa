<script setup lang="ts">
import { useAdminLocale } from './localization';
const { t: tr } = useAdminLocale();
import { toRef } from 'vue';
import { useAdminInitialReadiness } from './initialReadiness';
import { Alert, Button } from '@/runa/ui';
import type {
  AdminComponentRegistry,
  AdminController,
  AdminLocation,
} from './contracts';
import { useAdminPage } from './useAdminPage';
import { resolveAdminComponent } from './components';
import { useAdminPresentation } from './presentation';
import AdminComponentBoundary from './AdminComponentBoundary.vue';
import AdminLoading from './AdminLoading.vue';
import AdminPage from './AdminPage.vue';
import { useAdminForm } from './useAdminForm';
const props = defineProps<{
  admin: AdminController;
  location: AdminLocation;
  components?: AdminComponentRegistry | undefined;
}>();
const controller = useAdminPage(props.admin, toRef(props, 'location'));
const { page, state } = controller;
useAdminInitialReadiness(() => state.value !== 'loading');
const form = useAdminForm(controller, {
  components: props.components,
  autoRead: false,
});
const presentation = useAdminPresentation();
</script>
<template>
  <component
    :is="presentation.loading ?? AdminLoading"
    v-if="state === 'loading'"
  />
  <Alert v-else-if="state !== 'ready' || !page" tone="warning">
    {{
      tr(
        controller.error.value?.message ??
          tr('This administration page is unavailable.'),
      )
    }}
    <Button intent="inspect" @click="controller.refresh">{{
      tr('Retry')
    }}</Button>
  </Alert>
  <AdminComponentBoundary
    v-else
    :identity="page.owner + ':' + page.key + ':' + page.revision"
  >
    <component
      :is="
        resolveAdminComponent(page.component, components).component ??
        presentation.page
      "
      v-if="page.component || presentation.page"
      :controller="controller"
      :form="form"
    />
    <AdminPage
      v-else
      :controller="controller"
      :form="form"
      :components="components"
    />
  </AdminComponentBoundary>
</template>
