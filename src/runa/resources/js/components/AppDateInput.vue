<script setup lang="ts">
import { ref } from 'vue';
import AppTemporalInput from './AppTemporalInput.vue';
import type { TemporalInputProps } from '../runa/ui/forms';
withDefaults(defineProps<TemporalInputProps>(), {
  locale: 'pl',
  helper: '',
  error: '',
  invalidMessage: 'Check the date or time.',
  disabled: false,
  readonly: false,
  required: false,
});
const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  validity: [valid: boolean];
  draft: [value: string];
}>();
const control = ref<InstanceType<typeof AppTemporalInput>>();
defineExpose({
  reset: () => control.value?.reset(),
  focus: () => control.value?.focus(),
});
</script>
<template>
  <AppTemporalInput
    ref="control"
    v-bind="$props"
    kind="date"
    @update:model-value="emit('update:modelValue', $event)"
    @validity="emit('validity', $event)"
    @draft="emit('draft', $event)"
  />
</template>
