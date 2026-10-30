<script setup lang="ts">
import { AdminForm, AdminSection } from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
defineProps<AdminPageProps>();
</script>
<template>
  <div data-customer-admin-editor>
    <h1>Customer editor</h1>
    <AdminForm :controller="form">
      <AdminSection
        v-for="section in controller.page.value?.sections ?? []"
        :key="section.key"
        :controller="form"
        :section="section"
        :components="components"
      />
    </AdminForm>
  </div>
</template>
