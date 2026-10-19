<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AppTextInput from './AppTextInput.vue';
const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    label: string;
    helper?: string;
    error?: string | readonly string[];
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    pickLabel?: string;
    invalidMessage?: string;
  }>(),
  {
    helper: '',
    error: '',
    disabled: false,
    readonly: false,
    required: false,
    pickLabel: 'Choose color',
    invalidMessage: 'Enter a color in #RRGGBB format.',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  validity: [valid: boolean];
}>();
const draft = ref(props.modelValue);
watch(
  () => props.modelValue,
  (value) => {
    draft.value = value;
  },
);
const valid = computed(
  () => draft.value === null || /^#[\da-f]{6}$/i.test(draft.value),
);
watch(valid, (value) => emit('validity', value), { immediate: true });
const messages = computed(() => [
  ...(typeof props.error === 'string'
    ? props.error
      ? [props.error]
      : []
    : props.error),
  ...(!valid.value ? [props.invalidMessage] : []),
]);
function update(value: string | null) {
  if (props.disabled || props.readonly) return;
  draft.value = value === '' ? null : value;
  emit('update:modelValue', draft.value);
}
function pick(event: Event) {
  if (event.target instanceof globalThis.HTMLInputElement)
    update(event.target.value);
}
</script>
<template>
  <AppTextInput
    :model-value="draft"
    :label="label"
    :helper="helper"
    :error="messages"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    nullable
    spellcheck="false"
    autocomplete="off"
    @update:model-value="update"
    ><template #suffix
      ><input
        type="color"
        :value="valid && draft ? draft : '#000000'"
        :disabled="disabled || readonly"
        :aria-label="pickLabel"
        class="color-swatch"
        @input="pick" /></template
  ></AppTextInput>
</template>
<style scoped>
.color-swatch {
  appearance: none;
  width: 28px;
  height: 28px;
  padding: 0;
  border: 1px solid var(--runa-control-border);
  border-radius: 3px;
  cursor: pointer;
}
.color-swatch::-webkit-color-swatch-wrapper {
  padding: 0;
}
.color-swatch::-webkit-color-swatch {
  border: 0;
  border-radius: 2px;
}
.color-swatch::-moz-color-swatch {
  border: 0;
  border-radius: 2px;
}
.color-swatch:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}
</style>
