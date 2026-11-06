<script setup lang="ts">
import { ref } from 'vue';
import AppTemporalRangeInput from './AppTemporalRangeInput.vue';
import type { TemporalRangeInputProps, DateRangeValue } from '../runa/ui/forms';
defineProps<TemporalRangeInputProps>();
const emit = defineEmits<{
  'update:modelValue': [value: DateRangeValue];
  validity: [valid: boolean];
}>();
const control = ref<InstanceType<typeof AppTemporalRangeInput>>();
defineExpose({
  reset: () => control.value?.reset(),
  focus: () => control.value?.focus(),
});
</script>
<template>
  <AppTemporalRangeInput
    ref="control"
    v-bind="$props"
    kind="datetime"
    @update:model-value="emit('update:modelValue', $event)"
    @validity="emit('validity', $event)"
  />
</template>
