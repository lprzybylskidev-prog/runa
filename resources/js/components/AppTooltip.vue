<script setup lang="ts">
import { onScopeDispose, ref, useId } from 'vue';
import { useFloatingLayer } from '../composables/useFloatingLayer';
const props = withDefaults(
  defineProps<{ text: string; placement?: 'bottom' | 'right' }>(),
  { placement: 'bottom' },
);
const anchor = ref<globalThis.HTMLElement>();
const panel = ref<globalThis.HTMLElement>();
const visible = ref(false);
const descriptionId = useId();
let overAnchor = false;
let overPanel = false;
let dismissed = false;
let timer: ReturnType<typeof globalThis.setTimeout> | undefined;
function keyboardFocus() {
  const active = document.activeElement;
  return (
    !!active &&
    !!anchor.value?.contains(active) &&
    active.matches(':focus-visible')
  );
}
function update() {
  globalThis.clearTimeout(timer);
  visible.value = !dismissed && (overAnchor || overPanel || keyboardFocus());
  if (!overAnchor && !overPanel && !keyboardFocus()) dismissed = false;
}
function leave() {
  timer = globalThis.setTimeout(update, 80);
}
function dismiss() {
  overAnchor = false;
  overPanel = false;
  dismissed = true;
  visible.value = false;
}
function enter() {
  overAnchor = true;
  dismissed = false;
  update();
}
function click() {
  dismiss();
  overAnchor = false;
  overPanel = false;
}
const { position, ready } = useFloatingLayer({
  open: visible,
  anchor,
  panel,
  placement: () => props.placement,
  align: () => 'end',
  dismiss,
  hideOnScroll: true,
});
onScopeDispose(() => globalThis.clearTimeout(timer));
</script>
<template>
  <span
    ref="anchor"
    class="app-tooltip"
    @pointerenter="enter"
    @pointerleave="
      overAnchor = false;
      leave();
    "
    @focusin="update"
    @focusout="leave"
    @click="click"
  >
    <slot :description-id="visible ? descriptionId : undefined" />
    <Teleport to="body">
      <Transition name="tooltip-fade">
        <span
          v-if="visible && text"
          :id="descriptionId"
          ref="panel"
          role="tooltip"
          class="tooltip-content"
          :class="{ 'tooltip-ready': ready }"
          :style="position"
          @pointerenter="
            overPanel = true;
            update();
          "
          @pointerleave="
            overPanel = false;
            leave();
          "
          >{{ text }}</span
        >
      </Transition>
    </Teleport>
  </span>
</template>
<style scoped>
.app-tooltip {
  display: inline-flex;
}
.tooltip-content {
  position: fixed;
  z-index: 50;
  width: max-content;
  max-width: min(280px, calc(100vw - 16px));
  max-height: calc(100dvh - 16px);
  overflow: auto;
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
  visibility: hidden;
}
.tooltip-ready {
  visibility: visible;
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
