<script setup lang="ts">
import type { AdminPageProps, AdminPageSlots } from './contracts';
import { useAdminPresentation } from './presentation';
import AdminForm from './AdminForm.vue';
import AdminComponentBoundary from './AdminComponentBoundary.vue';
import AdminSectionContent from './AdminSectionContent.vue';
defineProps<AdminPageProps>();
defineSlots<AdminPageSlots>();
const presentation = useAdminPresentation();
</script>
<template>
  <component :is="presentation.form ?? AdminForm" :controller="form">
    <div class="space-y-6">
      <slot name="heading" :controller="controller" :form="form">
        <h1 class="text-xl font-semibold">
          {{ controller.page.value?.title }}
        </h1>
      </slot>
      <slot :controller="controller" :form="form">
        <p
          v-if="!controller.page.value?.sections.length"
          class="text-sm text-muted"
        >
          No sections are available.
        </p>
        <AdminComponentBoundary
          v-for="section in controller.page.value?.sections ?? []"
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
      </slot>
    </div>
    <template v-if="$slots.actions" #actions
      ><slot name="actions" :controller="controller" :form="form"
    /></template>
  </component>
</template>
