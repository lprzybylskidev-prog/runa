<script setup lang="ts">
import { ref, useId, watch } from 'vue';
import { useFloatingLayer } from '../composables/useFloatingLayer';
const props = withDefaults(
  defineProps<{ label: string; align?: 'start' | 'end' }>(),
  { align: 'start' },
);
const open = ref(false);
const anchor = ref<globalThis.HTMLElement>();
const panel = ref<globalThis.HTMLElement>();
const panelId = useId();
function trigger() {
  return anchor.value?.querySelector<globalThis.HTMLElement>(
    'button, a[href], [tabindex]',
  );
}
function close(restore = true) {
  open.value = false;
  if (restore) trigger()?.focus({ preventScroll: true });
}
function toggle() {
  if (open.value) close();
  else open.value = true;
}
const { position, ready } = useFloatingLayer({
  open,
  anchor,
  panel,
  placement: () => 'bottom',
  align: () => props.align,
  dismiss: close,
});
watch(
  ready,
  (value) => {
    if (value && open.value)
      (
        panel.value?.querySelector<globalThis.HTMLElement>(
          'button:not(:disabled), a[href], input, select, textarea, [tabindex="0"]',
        ) ?? panel.value
      )?.focus({ preventScroll: true });
  },
  { flush: 'post' },
);
function tab(event: globalThis.KeyboardEvent) {
  if (event.key !== 'Tab') return;
  const items = Array.from(
    panel.value?.querySelectorAll<globalThis.HTMLElement>(
      'button:not(:disabled), a[href], input, select, textarea, [tabindex="0"]',
    ) ?? [],
  ).filter((item) => item.getClientRects().length);
  const active = document.activeElement;
  if (event.shiftKey && (active === items[0] || active === panel.value)) {
    event.preventDefault();
    close();
  } else if (!event.shiftKey && (active === items.at(-1) || !items.length)) {
    event.preventDefault();
    const all = Array.from(
      document.querySelectorAll<globalThis.HTMLElement>(
        'button:not(:disabled), a[href], input, select, textarea, [tabindex="0"]',
      ),
    ).filter(
      (item) =>
        item.getClientRects().length &&
        !panel.value?.contains(item) &&
        !item.closest('[inert]'),
    );
    const next = all[all.indexOf(trigger()!) + 1];
    close(false);
    (next ?? trigger())?.focus();
  }
}
function leaving(element: globalThis.Element) {
  if (element instanceof globalThis.HTMLElement) element.inert = true;
}
</script>
<template>
  <span ref="anchor" class="app-popover-anchor"
    ><slot name="trigger" :toggle="toggle" :open="open" :panel-id="panelId"
  /></span>
  <Teleport to="body">
    <Transition name="popover-fade" @before-leave="leaving">
      <section
        v-if="open"
        :id="panelId"
        ref="panel"
        role="dialog"
        :aria-label="label"
        tabindex="-1"
        class="app-popover-panel"
        :class="{ 'popover-ready': ready }"
        :style="position"
        @keydown="tab"
      >
        <slot :close="close" />
      </section>
    </Transition>
  </Teleport>
</template>
<style scoped>
.app-popover-anchor {
  display: inline-flex;
}
.app-popover-panel {
  position: fixed;
  z-index: 40;
  width: min(280px, calc(100vw - 16px));
  max-height: calc(100dvh - 16px);
  overflow: auto;
  padding: 20px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-surface-raised);
  color: var(--runa-text);
  font-size: 12px;
  box-shadow: 0 8px 24px
    color-mix(
      in srgb,
      var(--runa-shadow) var(--runa-shadow-opacity),
      transparent
    );
  visibility: hidden;
}
.popover-ready {
  visibility: visible;
}
.app-popover-panel:focus-visible {
  outline: 3px solid var(--runa-focus-ring);
  outline-offset: 2px;
}
.popover-fade-enter-active,
.popover-fade-leave-active {
  transition:
    opacity 140ms ease-out,
    translate 140ms ease-out;
}
.popover-fade-enter-from,
.popover-fade-leave-to {
  opacity: 0;
  translate: 0 4px;
}
.popover-fade-leave-active {
  pointer-events: none;
}
@media (prefers-reduced-motion: reduce) {
  .popover-fade-enter-active,
  .popover-fade-leave-active {
    transition: none;
  }
}
</style>
