<script setup lang="ts">
import { useAttrs } from 'vue';
import AppFormField from './AppFormField.vue';
import type { TextInputType } from '../runa/ui/forms';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    label: string;
    id?: string;
    type?: TextInputType;
    helper?: string;
    warning?: string;
    error?: string | readonly string[];
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
    nullable?: boolean;
  }>(),
  {
    id: '',
    type: 'text',
    helper: '',
    warning: '',
    error: '',
    required: false,
    disabled: false,
    readonly: false,
    nullable: false,
  },
);
const attrs = useAttrs();
const emit = defineEmits<{ 'update:modelValue': [value: string | null] }>();
function update(event: Event) {
  if (event instanceof globalThis.InputEvent && event.isComposing) return;
  const target = event.target;
  if (
    (target instanceof globalThis.HTMLInputElement ||
      target instanceof globalThis.HTMLTextAreaElement) &&
    !props.disabled &&
    !props.readonly
  )
    emit(
      'update:modelValue',
      props.nullable && target.value === '' ? null : target.value,
    );
}
function descriptions(own: string | undefined) {
  return (
    [
      typeof attrs['aria-describedby'] === 'string'
        ? attrs['aria-describedby']
        : '',
      own,
    ]
      .filter(Boolean)
      .join(' ') || undefined
  );
}
</script>
<template>
  <AppFormField
    :id="id"
    :label="label"
    :helper="helper"
    :warning="warning"
    :error="error"
    :required="required"
    ><template #default="{ id: controlId, describedBy, invalid }">
      <textarea
        v-if="type === 'textarea'"
        v-bind="attrs"
        :id="controlId"
        :value="modelValue ?? ''"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :aria-invalid="invalid || undefined"
        :aria-describedby="descriptions(describedBy)"
        class="app-input app-textarea"
        @input="update"
        @compositionend="update" /><input
        v-else
        v-bind="attrs"
        :id="controlId"
        :type="type"
        :value="modelValue ?? ''"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :aria-invalid="invalid || undefined"
        :aria-describedby="descriptions(describedBy)"
        class="app-input"
        @input="update"
        @compositionend="update" /></template
  ></AppFormField>
</template>
<style scoped>
.app-input {
  display: block;
  width: 100%;
  min-width: 0;
  min-height: 44px;
  padding: 10px 12px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  background: var(--runa-input-background);
  color: var(--runa-text);
  font: inherit;
  font-size: 13px;
  transition:
    border-color 160ms ease-out,
    background-color 160ms ease-out;
}
.app-input::placeholder {
  color: var(--runa-text-muted);
  opacity: 1;
}
.app-input:hover:not(:disabled):not(:read-only) {
  border-color: var(--runa-secondary-solid);
}
.app-input[aria-invalid='true'] {
  border-color: var(--runa-danger-solid);
}
.app-input:focus-visible {
  outline: 3px solid var(--runa-focus-ring);
  outline-offset: 2px;
}
.app-input:disabled {
  cursor: not-allowed;
  background: var(--runa-disabled-background);
  color: var(--runa-disabled-text);
}
.app-input:read-only:not(:disabled) {
  background: var(--runa-surface-muted);
}
.app-textarea {
  min-height: 112px;
  resize: vertical;
  line-height: 1.6;
}
@media (prefers-reduced-motion: reduce) {
  .app-input {
    transition: none;
  }
}
</style>
