<script setup lang="ts">
import { computed, ref } from 'vue';
import { useApplicationDisplayDefaults } from '../runa/ui/displayDefaults';
import AppTemporalInput from './AppTemporalInput.vue';
import type { TemporalInputProps, TimeDisambiguation } from '../runa/ui/forms';
const props = withDefaults(
  defineProps<
    TemporalInputProps & {
      timeZone?: string | undefined;
      disambiguation?: TimeDisambiguation;
    }
  >(),
  {
    locale: 'pl',
    size: 'md',
    helper: '',
    error: '',
    invalidMessage: 'Check the date or time.',
    disabled: false,
    readonly: false,
    required: false,
    timeZone: undefined,
    disambiguation: 'reject',
  },
);
const defaults = useApplicationDisplayDefaults();
const timeZone = computed(() => props.timeZone ?? defaults.value.timezone);
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
    :time-zone="timeZone"
    kind="datetime-local"
    @update:model-value="emit('update:modelValue', $event)"
    @validity="emit('validity', $event)"
    @draft="emit('draft', $event)"
  />
</template>
