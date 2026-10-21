<script setup lang="ts">
import type { ControlSize } from '../runa/ui/controls';
withDefaults(
  defineProps<{ label: string; size?: ControlSize; decorative?: boolean }>(),
  { size: 'md', decorative: false },
);
</script>
<template>
  <span
    class="app-spinner"
    :data-size="size"
    :role="decorative ? undefined : 'status'"
    :aria-label="decorative ? undefined : label"
    :aria-hidden="decorative || undefined"
    ><span class="app-spinner-ring" aria-hidden="true"
  /></span>
</template>
<style scoped>
.app-spinner {
  display: inline-flex;
  flex-shrink: 0;
  vertical-align: middle;
}
.app-spinner-ring {
  display: block;
  width: 24px;
  height: 24px;
  border: 2px solid var(--loading-spinner-border, var(--runa-border));
  border-top-color: currentColor;
  border-right-color: currentColor;
  border-radius: 50%;
  animation: loading-spin 700ms linear infinite;
}
.app-spinner[data-size='sm'] .app-spinner-ring {
  width: 14px;
  height: 14px;
  border-color: currentColor;
  border-right-color: transparent;
}
.app-spinner[data-size='lg'] .app-spinner-ring {
  width: 44px;
  height: 44px;
}
@keyframes loading-spin {
  to {
    transform: rotate(360deg);
  }
}
@media (prefers-reduced-motion: reduce) {
  .app-spinner-ring {
    animation: none;
  }
}
</style>
