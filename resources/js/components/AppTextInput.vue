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
        class="runa-field-control app-input app-textarea"
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
        class="runa-field-control app-input"
        @input="update"
        @compositionend="update" /></template
  ></AppFormField>
</template>
<style scoped>
.app-textarea {
  min-height: 112px;
  resize: vertical;
  line-height: 1.6;
}
</style>
