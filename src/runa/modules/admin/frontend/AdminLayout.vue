<script setup lang="ts">
import { computed } from 'vue';
import { ApplicationLayout } from '@/runa/ui';
import type { AdminLayoutProps } from './contracts';
import type { ApplicationLayoutSlots } from '@/runa/ui';
const props = withDefaults(defineProps<AdminLayoutProps>(), {
  sidebar: true,
  topbar: true,
});
const layoutProps = computed(() => {
  const { controller, ...layout } = props;
  void controller;
  return layout;
});
defineSlots<ApplicationLayoutSlots>();
</script>
<template>
  <ApplicationLayout v-bind="layoutProps" locale="en">
    <template v-for="(_, name) in $slots" #[name]="scope"
      ><slot :name="name" v-bind="scope"
    /></template>
  </ApplicationLayout>
</template>
