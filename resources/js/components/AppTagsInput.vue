<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AppFormField from './AppFormField.vue';
import AppButton from './AppButton.vue';
const props = withDefaults(
  defineProps<{
    modelValue: readonly string[];
    label: string;
    helper?: string;
    error?: string | readonly string[];
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    maxTags?: number | undefined;
    removeLabel?: string;
    limitMessage?: string;
    duplicateMessage?: string;
  }>(),
  {
    helper: '',
    error: '',
    disabled: false,
    readonly: false,
    required: false,
    maxTags: undefined,
    removeLabel: 'Remove',
    limitMessage: 'Tag limit reached.',
    duplicateMessage: 'This tag already exists.',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: readonly string[]];
  validity: [valid: boolean];
}>();
const draft = ref('');
const message = ref('');
const control = ref<globalThis.HTMLInputElement>();
const ready = computed(
  () =>
    !draft.value.trim() &&
    !message.value &&
    (props.maxTags === undefined || props.modelValue.length <= props.maxTags),
);
watch(ready, (value) => emit('validity', value), { immediate: true });
defineExpose({ focus: () => control.value?.focus() });
function add(event: globalThis.KeyboardEvent) {
  if (event.isComposing) return;
  event.preventDefault();
  if (props.disabled || props.readonly) return;
  const tag = draft.value.trim();
  if (!tag) return;
  if (props.modelValue.includes(tag)) {
    message.value = props.duplicateMessage;
    return;
  }
  if (props.maxTags !== undefined && props.modelValue.length >= props.maxTags) {
    message.value = props.limitMessage;
    return;
  }
  emit('update:modelValue', [...props.modelValue, tag]);
  draft.value = '';
  message.value = '';
}
function remove(index: number) {
  if (!props.disabled && !props.readonly) {
    emit(
      'update:modelValue',
      props.modelValue.filter((_, i) => i !== index),
    );
    message.value = '';
  }
}
</script>
<template>
  <AppFormField
    :label="label"
    :helper="helper"
    :required="required"
    :error="[
      ...(typeof error === 'string' ? (error ? [error] : []) : error),
      ...(message ? [message] : []),
    ]"
    ><template #default="{ id, describedBy, invalid }"
      ><ul v-if="modelValue.length" class="flex min-w-0 flex-wrap gap-2">
        <li
          v-for="(tag, index) in modelValue"
          :key="`${index}-${tag}`"
          class="flex min-w-0 max-w-full items-center gap-1 rounded border border-line bg-[var(--runa-surface-muted)] px-2 text-xs"
        >
          <span class="min-w-0 break-all">{{ tag }}</span
          ><AppButton
            v-if="!readonly"
            size="sm"
            variant="ghost"
            :disabled="disabled"
            :aria-label="`${removeLabel}: ${tag}`"
            @click="remove(index)"
            >×</AppButton
          >
        </li>
      </ul>
      <input
        :id="id"
        ref="control"
        v-model="draft"
        type="text"
        :disabled="disabled"
        :readonly="readonly"
        :required="required && !modelValue.length"
        :aria-describedby="describedBy"
        :aria-invalid="invalid || undefined"
        class="runa-field-control"
        @input="message = ''"
        @keydown.enter="add"
        @keydown.esc="
          draft = '';
          message = '';
        " /></template
  ></AppFormField>
</template>
