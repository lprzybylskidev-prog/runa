<script setup lang="ts">
import AppToast from './AppToast.vue';
import type { ToastMessage } from '../runa/ui/feedback';
withDefaults(
  defineProps<{
    messages: readonly ToastMessage[];
    label?: string;
    closeLabel?: string;
  }>(),
  { label: 'Notifications', closeLabel: 'Close' },
);
defineEmits<{ dismiss: [id: string] }>();
function leaving(element: globalThis.Element) {
  if (element instanceof globalThis.HTMLElement) element.inert = true;
}
</script>
<template>
  <Teleport to="body"
    ><section
      class="toast-viewport"
      :aria-label="label"
      aria-live="polite"
      aria-relevant="additions"
    >
      <TransitionGroup name="toast-fade" @before-leave="leaving"
        ><AppToast
          v-for="message in messages"
          :key="message.id"
          :message="message"
          :close-label="closeLabel"
          @close="$emit('dismiss', $event)"
      /></TransitionGroup></section
  ></Teleport>
</template>
<style scoped>
.toast-viewport {
  position: fixed;
  top: 16px;
  right: 16px;
  z-index: 60;
  display: flex;
  flex-direction: column;
  gap: 12px;
  width: min(380px, calc(100vw - 32px));
  max-height: calc(100dvh - 32px);
  overflow: auto;
  pointer-events: none;
}
.toast-fade-enter-active,
.toast-fade-leave-active {
  transition:
    opacity 140ms ease-out,
    translate 140ms ease-out;
}
.toast-fade-enter-from,
.toast-fade-leave-to {
  opacity: 0;
  translate: 12px 0;
}
@media (prefers-reduced-motion: reduce) {
  .toast-fade-enter-active,
  .toast-fade-leave-active {
    transition: none;
  }
}
</style>
