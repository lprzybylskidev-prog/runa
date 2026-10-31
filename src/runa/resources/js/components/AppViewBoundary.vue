<script setup lang="ts">
import type { VNode } from 'vue';
import type { ViewReadiness } from '../composables/useViewReadiness';
import AppAlert from './AppAlert.vue';
import AppSkeleton from './AppSkeleton.vue';
withDefaults(
  defineProps<{
    readiness: ViewReadiness;
    label?: string;
    errorMessage?: string;
  }>(),
  { label: 'Loading', errorMessage: 'Unable to load content.' },
);
defineSlots<{
  default(): VNode[];
  loading?(): VNode[];
  error?(): VNode[];
}>();
</script>
<template>
  <div
    class="min-w-0"
    :aria-busy="['loading', 'refreshing'].includes(readiness.phase.value)"
    :data-view-phase="readiness.phase.value"
  >
    <slot v-if="readiness.ready.value" />
    <slot v-else-if="readiness.phase.value === 'error'" name="error">
      <AppAlert tone="danger">{{ errorMessage }}</AppAlert>
    </slot>
    <div v-else role="status" :aria-label="label">
      <slot name="loading"><AppSkeleton :lines="3" /></slot>
    </div>
  </div>
</template>
