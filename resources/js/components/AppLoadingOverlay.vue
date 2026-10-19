<script setup lang="ts">
import AppSpinner from './AppSpinner.vue';
import { nextTick, ref, watch } from 'vue';
import type { CSSProperties } from 'vue';
const props = defineProps<{ active: boolean; label: string }>();
const fullyCovered = ref(false);
const coverStyle = ref<CSSProperties>({});
watch(
  () => props.active,
  (active) => {
    fullyCovered.value = false;
    if (active) {
      const colors = window.getComputedStyle(document.documentElement);
      coverStyle.value = {
        backgroundColor: colors.getPropertyValue('--runa-surface').trim(),
        color: colors.getPropertyValue('--runa-primary-text').trim(),
        '--loading-spinner-border': colors
          .getPropertyValue('--runa-border')
          .trim(),
      };
    }
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
        :style="coverStyle"
        :class="{ 'loading-overlay-covered': fullyCovered }"
        role="status"
        aria-live="polite"
        :aria-label="label"
      >
        <AppSpinner
          class="loading-spinner"
          :label="label"
          size="lg"
          decorative
        />
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
.screen-cover-enter-active,
.screen-cover-leave-active {
  transition: opacity 340ms cubic-bezier(0.4, 0, 0.2, 1);
}
.screen-cover-enter-from,
.screen-cover-leave-to {
  opacity: 0;
}
.loading-overlay-covered:not(.screen-cover-leave-active) {
  opacity: 1;
  transition: none;
}
@media (prefers-reduced-motion: reduce) {
  .screen-cover-enter-active,
  .screen-cover-leave-active {
    transition: none;
  }
}
</style>
