<script setup lang="ts">
import { computed, provide, toRaw } from 'vue';
import type { VNode } from 'vue';
import type { AdminPresentation } from './contracts';
import { adminPresentationKey, useAdminPresentation } from './presentation';
const props = defineProps<{ components: AdminPresentation }>();
defineSlots<{ default?(): VNode[] }>();
const parent = useAdminPresentation();
provide(
  adminPresentationKey,
  computed(() =>
    Object.fromEntries(
      Object.entries({ ...parent.value, ...props.components })
        .filter(([, v]) => v !== undefined)
        .map(([k, v]) => [k, toRaw(v)]),
    ),
  ),
);
</script>
<template><slot /></template>
