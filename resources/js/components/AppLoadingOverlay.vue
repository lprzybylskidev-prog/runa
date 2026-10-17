<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
const props = defineProps<{ active: boolean; label: string }>();
const fullyCovered = ref(false);
watch(
  () => props.active,
  () => {
    fullyCovered.value = false;
  },
);
async function covered() {
  fullyCovered.value = true;
  await nextTick();
  emit('covered');
}
const emit = defineEmits<{ covered: []; revealed: [] }>();
</script>

<template>
  <Teleport to="body">
    <Transition
      name="screen-cover"
      @after-enter="covered"
      @after-leave="emit('revealed')"
    >
      <div
        v-if="active"
        class="loading-overlay"
        :class="{ 'loading-overlay-covered': fullyCovered }"
        role="status"
        aria-live="polite"
        :aria-label="label"
      >
        <span class="loading-spinner" aria-hidden="true" />
        <span class="sr-only">{{ label }}</span>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.loading-overlay {
  position: fixed;
  inset: 0;
  z-index: 50;
  display: grid;
  place-items: center;
  background: var(--runa-surface);
  color: var(--runa-primary-text);
  cursor: progress;
}
.loading-spinner {
  width: 44px;
  height: 44px;
  border: 2px solid var(--runa-border);
  border-top-color: currentColor;
  border-right-color: currentColor;
  border-radius: 50%;
  animation: loading-spin 700ms linear infinite;
}
.screen-cover-enter-active,
.screen-cover-leave-active {
  transition: opacity 180ms ease-in-out;
}
.screen-cover-enter-from,
.screen-cover-leave-to {
  opacity: 0;
}
.loading-overlay-covered {
  opacity: 1;
  transition: none;
}
@keyframes loading-spin {
  to {
    transform: rotate(360deg);
  }
}
@media (prefers-reduced-motion: reduce) {
  .loading-spinner {
    animation: none;
  }
  .screen-cover-enter-active,
  .screen-cover-leave-active {
    transition: none;
  }
}
</style>
