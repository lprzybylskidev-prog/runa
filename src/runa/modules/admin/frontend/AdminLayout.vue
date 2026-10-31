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
const slots = defineSlots<ApplicationLayoutSlots>();
const slotNames = () =>
  Object.keys(slots).filter((name) => name !== 'sidebar-bottom') as Exclude<
    keyof ApplicationLayoutSlots,
    'sidebar-bottom'
  >[];
</script>
<template>
  <ApplicationLayout v-bind="layoutProps" locale="en">
    <template v-if="slots['sidebar-bottom']" #sidebar-bottom="scope">
      <slot name="sidebar-bottom" v-bind="scope" />
    </template>
    <template v-for="name in slotNames()" #[name]="scope"
      ><slot :name="name" v-bind="scope"
    /></template>
  </ApplicationLayout>
</template>
