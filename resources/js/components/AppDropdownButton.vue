<script setup lang="ts">
import { nextTick, onScopeDispose, ref, useId, watch } from 'vue';
import AppButton from './AppButton.vue';
import type {
  ButtonAction,
  ButtonVariant,
  ControlSize,
  ControlTone,
} from '../runa/ui/controls';
const props = withDefaults(
  defineProps<{
    label: string;
    actions: readonly ButtonAction[];
    disabled?: boolean;
    busy?: boolean;
    variant?: ButtonVariant;
    tone?: ControlTone;
    size?: ControlSize;
    split?: boolean;
  }>(),
  {
    disabled: false,
    busy: false,
    variant: 'neutral',
    tone: 'primary',
    size: 'md',
    split: false,
  },
);
const emit = defineEmits<{
  select: [id: string];
  click: [event: globalThis.MouseEvent];
}>();
const open = ref(false);
const offset = ref(0);
const root = ref<globalThis.HTMLElement>();
const panel = ref<globalThis.HTMLElement>();
const id = useId();
function trigger() {
  return root.value?.querySelector<globalThis.HTMLButtonElement>(
    '[aria-controls]',
  );
}
function items() {
  return Array.from(
    panel.value?.querySelectorAll<globalThis.HTMLButtonElement>(
      'button:not(:disabled)',
    ) ?? [],
  );
}
async function toggle() {
  open.value = !open.value;
  if (open.value) {
    offset.value = 0;
    await nextTick();
    const bounds = panel.value?.getBoundingClientRect();
    if (bounds)
      offset.value =
        bounds.left < 8
          ? 8 - bounds.left
          : Math.min(0, window.innerWidth - 8 - bounds.right);
    items()[0]?.focus();
  }
}
function close(restore = false) {
  open.value = false;
  if (restore) trigger()?.focus();
}
function keydown(event: globalThis.KeyboardEvent) {
  if (event.key === 'Escape' && open.value) {
    event.preventDefault();
    event.stopPropagation();
    close(true);
  }
  if (['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) {
    event.preventDefault();
    if (!open.value) {
      void toggle();
      return;
    }
    const buttons = items();
    const index = buttons.indexOf(
      document.activeElement as globalThis.HTMLButtonElement,
    );
    buttons[
      event.key === 'Home'
        ? 0
        : event.key === 'End'
          ? buttons.length - 1
          : (index + (event.key === 'ArrowUp' ? -1 : 1) + buttons.length) %
            buttons.length
    ]?.focus();
  }
}
function resized() {
  close();
}
function outside(event: globalThis.PointerEvent) {
  if (
    event.target instanceof globalThis.Node &&
    !root.value?.contains(event.target)
  )
    close();
}
function focusout(event: globalThis.FocusEvent) {
  if (
    !(event.relatedTarget instanceof globalThis.Node) ||
    !root.value?.contains(event.relatedTarget)
  )
    close();
}
watch(open, (value) => {
  if (value) {
    document.addEventListener('pointerdown', outside);
    window.addEventListener('resize', resized);
  } else {
    document.removeEventListener('pointerdown', outside);
    window.removeEventListener('resize', resized);
  }
});
watch(
  () => props.disabled || props.busy,
  (value) => {
    if (value) close();
  },
);
onScopeDispose(() => {
  document.removeEventListener('pointerdown', outside);
  window.removeEventListener('resize', resized);
});
</script>
<template>
  <div
    ref="root"
    class="dropdown-button"
    @keydown="keydown"
    @focusout="focusout"
  >
    <div class="dropdown-triggers">
      <AppButton
        v-if="split"
        :variant="variant"
        :tone="tone"
        :size="size"
        :disabled="disabled"
        :busy="busy"
        @click="emit('click', $event)"
        >{{ label }}</AppButton
      >
      <AppButton
        :variant="variant"
        :tone="tone"
        :size="size"
        :disabled="disabled || busy"
        :busy="busy && !split"
        :aria-expanded="open"
        :aria-controls="id"
        :aria-label="split ? label : undefined"
        :icon-only="split"
        @click="toggle"
        >{{ split ? '▾' : `${label} ▾` }}</AppButton
      >
    </div>
    <div
      v-if="open"
      :id="id"
      ref="panel"
      class="dropdown-actions"
      :style="{ transform: `translateX(${offset}px)` }"
      :aria-label="label"
      role="group"
    >
      <AppButton
        v-for="action in actions"
        :key="action.id"
        :disabled="!!action.disabled"
        variant="ghost"
        @click="
          emit('select', action.id);
          close(true);
        "
        >{{ action.label }}</AppButton
      >
      <slot v-if="!actions.length" name="empty" />
    </div>
  </div>
</template>
<style scoped>
.dropdown-button {
  position: relative;
  display: inline-block;
  max-width: 100%;
}
.dropdown-triggers {
  display: flex;
  gap: 1px;
}
.dropdown-actions {
  position: absolute;
  top: calc(100% + 6px);
  left: 0;
  z-index: 20;
  display: flex;
  flex-direction: column;
  min-width: 180px;
  max-width: min(320px, 85vw);
  padding: 4px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-surface-raised);
  box-shadow: 0 8px 24px color-mix(in srgb, var(--runa-shadow) 15%, transparent);
}
.dropdown-actions :deep(button) {
  justify-content: flex-start;
  text-align: left;
  overflow-wrap: anywhere;
}
</style>
