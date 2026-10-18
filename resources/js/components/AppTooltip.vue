<script setup lang="ts">
import { onMounted, onScopeDispose, ref, useId } from 'vue';
const props = withDefaults(
  defineProps<{ text: string; placement?: 'bottom' | 'right' }>(),
  { placement: 'bottom' },
);
const anchor = ref<globalThis.HTMLElement>();
const position = ref({ left: '0px', top: '0px' });
function show() {
  if (props.placement === 'right' && anchor.value) {
    const bounds = anchor.value.getBoundingClientRect();
    position.value = {
      left: `${Math.min(bounds.right + 8, window.innerWidth - 210)}px`,
      top: `${Math.max(8, Math.min(bounds.top, window.innerHeight - 100))}px`,
    };
  }
  visible.value = true;
}
function hideOnScroll() {
  if (props.placement === 'right') visible.value = false;
}
const descriptionId = useId();
const visible = ref(false);
function dismiss(event: { key: string }) {
  if (event.key === 'Escape') visible.value = false;
}
onMounted(() => {
  document.addEventListener('keydown', dismiss);
  document.addEventListener('scroll', hideOnScroll, true);
});
onScopeDispose(() => {
  document.removeEventListener('keydown', dismiss);
  document.removeEventListener('scroll', hideOnScroll, true);
});
</script>

<template>
  <span
    ref="anchor"
    class="app-tooltip"
    @pointerenter="show"
    @pointerleave="visible = false"
    @focusin="show"
    @focusout="visible = false"
  >
    <slot :description-id="descriptionId" />
    <Teleport to="body" :disabled="placement !== 'right'">
      <Transition name="tooltip-fade">
        <span
          v-show="visible && text"
          :id="descriptionId"
          role="tooltip"
          :class="[
            'tooltip-content',
            { 'tooltip-right': placement === 'right' },
          ]"
          :style="placement === 'right' ? position : undefined"
          ><span>{{ text }}</span></span
        >
      </Transition>
    </Teleport>
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
.tooltip-content.tooltip-right {
  position: fixed;
  right: auto;
  padding-top: 0;
  z-index: 40;
  max-width: 200px;
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
