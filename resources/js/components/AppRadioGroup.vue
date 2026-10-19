<script setup lang="ts">
import { useId } from 'vue';
import AppFormField from './AppFormField.vue';
import type { RadioOption } from '../runa/ui/forms';
withDefaults(
  defineProps<{
    modelValue: string | null;
    label: string;
    options: readonly RadioOption[];
    name?: string;
    helper?: string;
    error?: string | readonly string[];
    required?: boolean;
    disabled?: boolean;
    readonly?: boolean;
  }>(),
  {
    name: '',
    helper: '',
    error: '',
    required: false,
    disabled: false,
    readonly: false,
  },
);
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const group = useId();
</script>
<template>
  <AppFormField
    :label="label"
    :helper="helper"
    :error="error"
    :required="required"
    ><template #label="{ id }"
      ><p :id="`${id}-label`" class="text-xs font-semibold text-muted">
        {{ label }}<span v-if="required" aria-hidden="true"> *</span>
      </p></template
    ><template #default="{ id, describedBy, invalid }"
      ><div
        role="radiogroup"
        tabindex="-1"
        :aria-labelledby="`${id}-label`"
        :aria-describedby="describedBy"
        :aria-invalid="invalid || undefined"
        :aria-readonly="readonly || undefined"
        class="flex flex-wrap gap-2"
      >
        <label
          v-for="option in options"
          :key="option.value"
          class="runa-choice-row"
          ><input
            type="radio"
            :name="name || group"
            :value="option.value"
            :checked="modelValue === option.value"
            :disabled="disabled || option.disabled"
            :required="required"
            class="runa-choice-input"
            @click="readonly && $event.preventDefault()"
            @change="
              !readonly &&
              !disabled &&
              !option.disabled &&
              emit('update:modelValue', option.value)
            "
          /><span>{{ option.label }}</span></label
        >
      </div></template
    ></AppFormField
  >
</template>
