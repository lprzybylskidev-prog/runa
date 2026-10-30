<script setup lang="ts">
import { computed } from 'vue';
import { AdminRecordBrowser } from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
const props = defineProps<AdminPageProps>();
const canCreate = computed(
  () =>
    props.controller.page.value?.sections.some(
      (section) =>
        section.key === 'editor' &&
        section.commands.some(
          (command) => command.key === 'store' && command.available,
        ),
    ) ?? false,
);
</script>
<template>
  <AdminRecordBrowser v-bind="props" :allow-create="canCreate" />
</template>
