<script setup lang="ts">
import { computed } from 'vue';
import { useInheritedFeedbackTone } from '../composables/useFeedbackTone';
import AppSpinner from './AppSpinner.vue';
import type {
  ControlTone,
  ControlSize,
  ButtonVariant,
} from '../runa/ui/controls';
const props = withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset';
    compact?: boolean;
    disabled?: boolean;
    busy?: boolean;
    size?: ControlSize;
    variant?: ButtonVariant | undefined;
    tone?: ControlTone | undefined;
    iconOnly?: boolean;
  }>(),
  {
    tone: undefined,
    variant: undefined,
    type: 'button',
    compact: false,
    disabled: false,
    busy: false,
    size: 'md',
    iconOnly: false,
  },
);
const contextTone = useInheritedFeedbackTone();
const tone = computed(
  () =>
    props.tone ??
    (contextTone.value === 'neutral' ? 'primary' : contextTone.value),
);
const variant = computed(
  () =>
    props.variant ?? (contextTone.value === 'neutral' ? 'neutral' : 'outline'),
);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || busy"
    :aria-busy="busy || undefined"
    :data-tone="tone"
    :data-variant="variant"
    :data-size="size"
    class="app-button"
    :class="{ 'app-button-compact': compact || iconOnly }"
  >
    <AppSpinner
      v-if="busy"
      class="button-spinner"
      label=""
      size="sm"
      decorative
    />
    <slot v-if="!busy || !(compact || iconOnly)" />
  </button>
</template>

<style scoped>
.app-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 44px;
  padding: 8px 16px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-surface);
  color: var(--runa-text-secondary);
  cursor: pointer;
  font-size: 12px;
  transition:
    background-color 160ms ease-out,
    border-color 160ms ease-out,
    color 160ms ease-out,
    box-shadow 160ms ease-out;
}
.app-button-compact {
  width: 44px;
  height: 44px;
  padding: 0;
  flex-shrink: 0;
}
.app-button:hover:not(:disabled) {
  background: var(--runa-surface-hover);
  color: var(--runa-primary-text);
  border-color: var(--runa-primary-solid);
}
.app-button:active:not(:disabled) {
  background: var(--runa-primary-subtle);
}
.app-button[aria-pressed='true']:not(:disabled) {
  background: var(--button-subtle);
  color: var(--button-text);
  border-color: var(--button-solid);
  box-shadow: inset 0 -3px var(--button-solid);
}
.app-button:focus-visible {
  outline: 3px solid var(--runa-focus-ring);
  outline-offset: 4px;
}
.app-button:disabled {
  background: var(--runa-disabled-background);
  color: var(--runa-disabled-text);
  cursor: not-allowed;
}
.app-button[data-tone='primary'] {
  --button-solid: var(--runa-primary-solid);
  --button-hover: var(--runa-primary-hover);
  --button-foreground: var(--runa-primary-foreground);
  --button-subtle: var(--runa-primary-subtle);
  --button-text: var(--runa-primary-text);
}
.app-button[data-tone='secondary'] {
  --button-solid: var(--runa-secondary-solid);
  --button-hover: var(--runa-secondary-hover);
  --button-foreground: var(--runa-secondary-foreground);
  --button-subtle: var(--runa-secondary-subtle);
  --button-text: var(--runa-secondary-text);
}
.app-button[data-tone='success'] {
  --button-solid: var(--runa-success-solid);
  --button-hover: var(--runa-success-hover);
  --button-foreground: var(--runa-success-foreground);
  --button-subtle: var(--runa-success-subtle);
  --button-text: var(--runa-success-text);
}
.app-button[data-tone='danger'] {
  --button-solid: var(--runa-danger-solid);
  --button-hover: var(--runa-danger-hover);
  --button-foreground: var(--runa-danger-foreground);
  --button-subtle: var(--runa-danger-subtle);
  --button-text: var(--runa-danger-text);
}
.app-button[data-tone='warning'] {
  --button-solid: var(--runa-warning-solid);
  --button-hover: var(--runa-warning-hover);
  --button-foreground: var(--runa-warning-foreground);
  --button-subtle: var(--runa-warning-subtle);
  --button-text: var(--runa-warning-text);
}
.app-button[data-tone='info'] {
  --button-solid: var(--runa-info-solid);
  --button-hover: var(--runa-info-hover);
  --button-foreground: var(--runa-info-foreground);
  --button-subtle: var(--runa-info-subtle);
  --button-text: var(--runa-info-text);
}
.app-button[data-tone='accent'] {
  --button-solid: var(--runa-accent-solid);
  --button-hover: var(--runa-accent-hover);
  --button-foreground: var(--runa-accent-foreground);
  --button-subtle: var(--runa-accent-subtle);
  --button-text: var(--runa-accent-text);
}
.app-button[data-size='sm'] {
  min-height: 36px;
  padding: 6px 12px;
  font-size: 11px;
}
.app-button[data-size='lg'] {
  min-height: 52px;
  padding: 12px 20px;
  font-size: 14px;
}
.app-button.app-button-compact {
  padding: 0;
}
.app-button-compact[data-size='sm'] {
  width: 36px;
  height: 36px;
}
.app-button-compact[data-size='lg'] {
  width: 52px;
  height: 52px;
}
.app-button[data-variant='solid']:not(:disabled) {
  background: var(--button-solid);
  color: var(--button-foreground);
  border-color: var(--button-solid);
}
.app-button[data-variant='solid']:hover:not(:disabled) {
  background: var(--button-hover);
}
.app-button[data-variant='outline']:not(:disabled) {
  color: var(--button-text);
  border-color: var(--button-text);
}
.app-button[data-variant='soft']:not(:disabled) {
  color: var(--button-text);
  background: var(--button-subtle);
  border-color: transparent;
}
.app-button[data-variant='ghost']:not(:disabled) {
  color: var(--button-text);
  background: transparent;
  border-color: transparent;
}
.app-button[data-variant='soft']:hover:not(:disabled) {
  background: var(--button-solid);
  color: var(--button-foreground);
  border-color: var(--button-solid);
}
.app-button[data-variant='outline']:hover:not(:disabled),
.app-button[data-variant='ghost']:hover:not(:disabled) {
  background: var(--button-subtle);
}
.app-button:active:not(:disabled) {
  box-shadow: inset 0 0 0 2px currentColor;
}
@media (prefers-reduced-motion: reduce) {
  .app-button {
    transition: none;
  }
}
</style>
