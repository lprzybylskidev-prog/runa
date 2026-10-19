<script setup lang="ts">
import AppFormField from './AppFormField.vue';
import type { BooleanInputProps } from '../runa/ui/forms';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<BooleanInputProps & { kind: 'checkbox' | 'switch' }>(),
  { helper: '', error: '', disabled: false, readonly: false, required: false },
);
const emit = defineEmits<{ 'update:modelValue': [value: boolean] }>();
function change(event: Event) {
  if (
    !props.disabled &&
    !props.readonly &&
    event.target instanceof globalThis.HTMLInputElement &&
    !event.target.matches(':disabled')
  )
    emit('update:modelValue', event.target.checked);
}
</script>
<template>
  <AppFormField
    :label="label"
    :helper="helper"
    :error="error"
    :required="required"
    ><template #label /><template #default="{ id, describedBy, invalid }"
      ><label
        class="runa-choice-row"
        :class="{ 'runa-choice-compact': compact }"
        :for="id"
        ><input
          :id="id"
          v-bind="$attrs"
          type="checkbox"
          :role="kind === 'switch' ? 'switch' : undefined"
          :checked="modelValue === true"
          :indeterminate.prop="kind === 'checkbox' && modelValue === null"
          :aria-checked="
            kind === 'checkbox' && modelValue === null ? 'mixed' : undefined
          "
          :aria-describedby="describedBy"
          :aria-invalid="invalid || undefined"
          :aria-readonly="readonly || undefined"
          :disabled="disabled"
          :required="required"
          class="runa-choice-input"
          :class="{ 'runa-switch-input': kind === 'switch' }"
          @click="readonly && $event.preventDefault()"
          @change="change"
        /><span :class="hideLabel ? 'sr-only' : undefined"
          >{{ label }}<span v-if="required" aria-hidden="true"> *</span></span
        ></label
      ></template
    ></AppFormField
  >
</template>
