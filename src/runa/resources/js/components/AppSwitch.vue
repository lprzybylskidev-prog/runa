<script setup lang="ts">
import AppBooleanInput from './AppBooleanInput.vue';
import type { BooleanInputProps } from '../runa/ui/forms';
withDefaults(
  defineProps<
    Omit<BooleanInputProps, 'modelValue'> & { modelValue: boolean }
  >(),
  { helper: '', error: '', disabled: false, readonly: false, required: false },
);
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
</script>
<template>
  <AppBooleanInput
    v-bind="$props"
    kind="switch"
    @update:model-value="emit('update:modelValue', $event)"
  />
</template>
