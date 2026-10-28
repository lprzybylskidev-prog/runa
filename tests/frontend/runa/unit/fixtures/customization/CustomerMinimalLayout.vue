<script setup lang="ts">
import { MinimalLayout } from '@/runa/ui';
import type { MinimalLayoutProps, MinimalLayoutSlots } from '@/runa/ui';
const props = withDefaults(defineProps<MinimalLayoutProps>(), {
  homeHref: '/',
  illustration: false,
  constellationGlow: true,
  orbitMotion: true,
});
defineSlots<MinimalLayoutSlots>();
</script>
<template>
  <MinimalLayout v-bind="props" :illustration="false">
    <template #header><span data-customer-brand>Customer brand</span></template>
    <template #header-actions="scope"
      ><slot name="header-actions" v-bind="scope"
    /></template>
    <template #default="scope"><slot v-bind="scope" /></template>
    <template v-if="$slots.content" #content="scope"
      ><slot name="content" v-bind="scope"
    /></template>
    <template v-if="$slots.actions" #actions="scope"
      ><slot name="actions" v-bind="scope"
    /></template>
    <template v-if="$slots.illustration" #illustration="scope"
      ><slot name="illustration" v-bind="scope"
    /></template>
    <template v-if="$slots.footer" #footer="scope"
      ><slot name="footer" v-bind="scope"
    /></template>
  </MinimalLayout>
</template>
