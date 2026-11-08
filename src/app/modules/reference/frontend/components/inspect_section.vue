<script setup lang="ts">
import { ref } from 'vue';
import { AdminSection, useAdminLocale } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import { actionIcons, Button, Card } from '@/runa/ui';
const props = defineProps<AdminSectionPresentationProps>();
const refreshing = ref(false);
async function refresh() {
  if (refreshing.value || props.controller.busy.value) return;
  refreshing.value = true;
  try {
    await props.controller.refresh();
  } finally {
    refreshing.value = false;
  }
}
const { t } = useAdminLocale();
</script>

<template>
  <Card>
    <template #header
      ><h2>{{ t(section.title) }}</h2></template
    >
    <template #actions>
      <Button
        :icon="actionIcons.refresh"
        intent="inspect"
        size="sm"
        :busy="refreshing || controller.busy.value"
        @click="refresh"
      >
        {{ t({ pl: 'Odśwież', en: 'Refresh' }) }}
      </Button>
    </template>
    <AdminSection :controller="controller" :section="section" embedded />
  </Card>
</template>
