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
      <div class="app-input-row" :class="{ 'has-suffix': $slots.suffix }">
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
          class="runa-field-control app-input app-textarea"
          @input="update"
          @compositionend="update"
        /><input
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
          class="runa-field-control app-input"
          @input="update"
          @compositionend="update"
        /><span v-if="$slots.suffix" class="app-input-suffix"
          ><slot name="suffix"
        /></span></div></template
  ></AppFormField>
</template>
<style scoped>
.app-input-row {
  display: flex;
  min-width: 0;
  align-items: stretch;
}
.has-suffix > .app-input {
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}
.app-input-suffix {
  display: flex;
  flex-shrink: 0;
  align-items: center;
  padding: 0 12px;
  border: 1px solid var(--runa-control-border);
  border-left: 0;
  border-radius: 0 4px 4px 0;
  background: var(--runa-surface-muted);
  color: var(--runa-text-secondary);
  font-size: 12px;
}
.app-input-row:has(> .app-input[aria-invalid='true']) > .app-input-suffix {
  border-color: var(--runa-danger-solid);
}
.app-input-row:has(> .app-input:focus) > .app-input-suffix {
  border-color: var(--runa-focus-ring);
}
.app-input-row:has(:disabled) > .app-input-suffix {
  background: var(--runa-disabled-background);
  color: var(--runa-disabled-text);
}

.app-textarea {
  min-height: 112px;
  resize: vertical;
  line-height: 1.6;
}
</style>
