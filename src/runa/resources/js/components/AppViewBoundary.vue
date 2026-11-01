<script setup lang="ts">
import type { VNode } from 'vue';
import type { ViewReadiness } from '../composables/useViewReadiness';
import AppAlert from './AppAlert.vue';
import AppContentLoading from './AppContentLoading.vue';
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
    :class="readiness.phase.value === 'refreshing' ? 'runa-content-pulse' : ''"
    :aria-busy="['loading', 'refreshing'].includes(readiness.phase.value)"
    :data-view-phase="readiness.phase.value"
  >
    <slot v-if="readiness.ready.value" />
    <slot v-else-if="readiness.phase.value === 'error'" name="error">
      <AppAlert tone="danger">{{ errorMessage }}</AppAlert>
    </slot>
    <slot v-else name="loading"><AppContentLoading :label="label" /></slot>
  </div>
</template>
