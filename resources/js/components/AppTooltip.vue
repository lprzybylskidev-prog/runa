<script setup lang="ts">
import { onMounted, onScopeDispose, ref, useId } from 'vue';
defineProps<{ text: string }>();
const descriptionId = useId();
const visible = ref(false);
function dismiss(event: { key: string }) {
  if (event.key === 'Escape') visible.value = false;
}
onMounted(() => document.addEventListener('keydown', dismiss));
onScopeDispose(() => document.removeEventListener('keydown', dismiss));
</script>

<template>
  <span
    class="app-tooltip"
    @pointerenter="visible = true"
    @pointerleave="visible = false"
    @focusin="visible = true"
    @focusout="visible = false"
  >
    <slot :description-id="descriptionId" />
    <span
      v-show="visible"
      :id="descriptionId"
      role="tooltip"
      class="tooltip-content"
      ><span>{{ text }}</span></span
    >
  </span>
</template>
