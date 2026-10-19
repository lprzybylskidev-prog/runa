<script setup lang="ts">
import { computed, ref, useId, watch } from 'vue';
import AppDateInput from './AppDateInput.vue';
import { validDateRange } from '../lib/temporal';
import type { DateRangeValue } from '../runa/ui/forms';
const props = withDefaults(
  defineProps<{
    modelValue: DateRangeValue;
    label: string;
    startLabel: string;
    endLabel: string;
    locale?: 'pl' | 'en';
    error?: string;
    invalidMessage?: string;
    disabled?: boolean;
    readonly?: boolean;
  }>(),
  {
    locale: 'pl',
    error: '',
    invalidMessage: 'Check the date range.',
    disabled: false,
    readonly: false,
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: DateRangeValue];
  validity: [valid: boolean];
}>();
const validStart = ref(true),
  validEnd = ref(true);
const id = useId();
const valid = computed(
  () => validStart.value && validEnd.value && validDateRange(props.modelValue),
);
watch(valid, (value) => emit('validity', value), { immediate: true });
</script>
<template>
  <fieldset class="min-w-0 space-y-3">
    <legend class="mb-2 text-xs font-semibold text-muted">{{ label }}</legend>
    <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
      <AppDateInput
        :model-value="modelValue.start"
        :label="startLabel"
        :locale="locale"
        :disabled="disabled"
        :readonly="readonly"
        :aria-describedby="!valid || error ? id : undefined"
        @update:model-value="
          emit('update:modelValue', { ...modelValue, start: $event })
        "
        @validity="validStart = $event"
      /><AppDateInput
        :model-value="modelValue.end"
        :label="endLabel"
        :locale="locale"
        :disabled="disabled"
        :readonly="readonly"
        :aria-describedby="!valid || error ? id : undefined"
        @update:model-value="
          emit('update:modelValue', { ...modelValue, end: $event })
        "
        @validity="validEnd = $event"
      />
    </div>
    <p
      v-if="!valid || error"
      :id="id"
      role="status"
      class="text-xs text-[var(--runa-danger-text)]"
    >
      {{ error || invalidMessage }}
    </p>
  </fieldset>
</template>
