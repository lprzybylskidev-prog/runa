<script setup lang="ts">
import { AdminSection, useAdminLocale } from '@/runa/admin';
import type { AdminSectionPresentationProps } from '@/runa/admin';
import { Button, Card } from '@/runa/ui';
defineProps<AdminSectionPresentationProps>();
const { t } = useAdminLocale();
</script>

<template>
  <Card>
    <template #header
      ><h2>{{ t(section.title) }}</h2></template
    >
    <template #actions>
      <Button
        intent="inspect"
        size="sm"
        :busy="controller.busy.value"
        @click="controller.refresh"
      >
        {{ t({ pl: 'Odśwież', en: 'Refresh' }) }}
      </Button>
    </template>
    <AdminSection :controller="controller" :section="section" embedded />
  </Card>
</template>
