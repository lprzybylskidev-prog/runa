<script setup lang="ts">
withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset';
    compact?: boolean;
    disabled?: boolean;
  }>(),
  { type: 'button', compact: false, disabled: false },
);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled"
    class="app-button"
    :class="{ 'app-button-compact': compact }"
  >
    <slot />
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
.app-button:focus-visible {
  outline: 3px solid var(--runa-focus-ring);
  outline-offset: 4px;
}
.app-button:disabled {
  background: var(--runa-disabled-background);
  color: var(--runa-disabled-text);
  cursor: not-allowed;
}
@media (prefers-reduced-motion: reduce) {
  .app-button {
    transition: none;
  }
}
</style>
