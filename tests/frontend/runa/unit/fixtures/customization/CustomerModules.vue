<script setup lang="ts">
import { ModulePage, ModuleSlot } from '@/runa/modules';
import type { ModulePageProps, ModuleSlotProps } from '@/runa/modules';
defineProps<{ page: ModulePageProps; contribution: ModuleSlotProps }>();
</script>
<template>
  <main data-customer-modules>
    <ModulePage v-bind="page">
      <template #unavailable="{ reason }"
        ><p data-page-unavailable>{{ reason }}</p></template
      >
    </ModulePage>
    <ModuleSlot v-bind="contribution"
      ><p data-slot-empty>No contributions</p></ModuleSlot
    >
  </main>
</template>
