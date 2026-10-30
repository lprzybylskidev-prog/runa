<script setup lang="ts">
import { toRef } from 'vue';
import { Alert, Button, Card } from '@/runa/ui';
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
const props = defineProps<{
  admin: AdminController;
  location: AdminLocation;
  components?: AdminComponentRegistry | undefined;
}>();
const controller = useAdminPage(props.admin, toRef(props, 'location'));
const { page, state } = controller;
const presentation = useAdminPresentation();
</script>
<template>
  <component
    :is="presentation.loading ?? AdminLoading"
    v-if="state === 'loading'"
  />
  <Alert v-else-if="state !== 'ready' || !page" tone="warning">
    {{
      controller.error.value?.message ??
      'This administration page is unavailable.'
    }}
    <Button @click="controller.refresh">Retry</Button>
  </Alert>
  <AdminComponentBoundary
    v-else
    :identity="page.owner + ':' + page.key + ':' + page.revision"
  >
    <component
      v-if="page.component || presentation.page"
      :is="
        resolveAdminComponent(page.component, components).component ??
        presentation.page
      "
      :controller="controller"
    />
    <div v-else class="space-y-6">
      <h1 class="text-xl font-semibold">{{ page.title }}</h1>
      <p v-if="!page.sections.length" class="text-sm text-muted">
        No sections are available.
      </p>
      <AdminComponentBoundary
        v-for="section in page.sections"
        :key="section.key"
        :identity="page.revision + ':' + section.key"
      >
        <component
          v-if="section.component || presentation.section"
          :is="
            resolveAdminComponent(section.component, components).component ??
            presentation.section
          "
          :section="section"
        />
        <Card v-else>
          <template #header
            ><h2>{{ section.title }}</h2></template
          >
          <Alert v-if="section.diagnostic" tone="warning"
            >This section is currently unavailable.</Alert
          >
          <p v-else class="text-sm text-muted">
            No controls are available for this section.
          </p>
        </Card>
      </AdminComponentBoundary>
    </div>
  </AdminComponentBoundary>
</template>
