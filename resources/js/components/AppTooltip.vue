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
    <Transition name="tooltip-fade">
      <span
        v-show="visible"
        :id="descriptionId"
        role="tooltip"
        class="tooltip-content"
        ><span>{{ text }}</span></span
      >
    </Transition>
  </span>
</template>

<style scoped>
.app-tooltip {
  position: relative;
  display: inline-flex;
}
.tooltip-content {
  position: absolute;
  z-index: 20;
  top: 100%;
  right: 0;
  padding-top: 8px;
  width: max-content;
  max-width: min(280px, 75vw);
}
.tooltip-content > span {
  display: block;
  padding: 8px 12px;
  background: var(--runa-surface-raised);
  color: var(--runa-text);
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  font-size: 11px;
  line-height: 1.6;
  box-shadow: 0 4px 12px
    color-mix(
      in srgb,
      var(--runa-shadow) var(--runa-shadow-opacity),
      transparent
    );
}

.tooltip-fade-enter-active,
.tooltip-fade-leave-active {
  transition: opacity 140ms ease-out;
}
.tooltip-fade-enter-from,
.tooltip-fade-leave-to {
  opacity: 0;
}
.tooltip-fade-leave-active {
  pointer-events: none;
}
@media (prefers-reduced-motion: reduce) {
  .tooltip-fade-enter-active,
  .tooltip-fade-leave-active {
    transition: none;
  }
}
</style>
