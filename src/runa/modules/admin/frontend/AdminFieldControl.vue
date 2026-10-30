<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
  Checkbox,
  FormField,
  NumberInput,
  Select,
  TagsInput,
  TextInput,
} from '@/runa/ui';
import type {
  AdminFieldEmits,
  AdminFieldProps,
  AdminFieldValue,
} from './contracts';
import { validateAdminField } from './fieldValidation';
const props = defineProps<AdminFieldProps>();
const emit = defineEmits<AdminFieldEmits>();
const edited = ref(false);
watch(
  () => props.field.key,
  () => {
    edited.value = false;
  },
);
const messages = computed(() => [
  ...props.errors,
  ...(edited.value && !props.readonly
    ? validateAdminField(props.field, props.modelValue)
    : []),
]);
const secretSet = computed(() => {
  const value = props.modelValue;
  return (
    value !== null &&
    typeof value === 'object' &&
    !Array.isArray(value) &&
    value.set === true
  );
});
const text = computed(() =>
  typeof props.modelValue === 'string' ? props.modelValue : null,
);
const items = computed(() =>
  Array.isArray(props.modelValue)
    ? props.modelValue.filter(
        (item): item is string => typeof item === 'string',
      )
    : [],
);
const options = computed(() =>
  props.field.choices.map((value) => ({ value, label: value })),
);
const display = computed(() => {
  if (props.field.sensitive) return secretSet.value ? 'Set' : 'Not set';
  if (props.modelValue === null || props.modelValue === '') return '—';
  if (typeof props.modelValue === 'boolean')
    return props.modelValue ? 'Yes' : 'No';
  if (Array.isArray(props.modelValue)) return items.value.join(', ') || '—';
  return typeof props.modelValue === 'object'
    ? 'Unavailable'
    : String(props.modelValue);
});
function update(value: AdminFieldValue) {
  if (props.busy || props.readonly || props.field.readonly) return;
  edited.value = true;
  emit('update:modelValue', value);
}
function integer(value: string | null) {
  // Retain incomplete/out-of-range input rather than rounding it or reusing a stale number.
  const parsed = value !== null && /^-?\d+$/.test(value) ? Number(value) : NaN;
  update(Number.isSafeInteger(parsed) ? parsed : value);
}
</script>
<template>
  <FormField
    v-if="readonly || field.readonly"
    :label="field.label"
    :error="errors"
  >
    <template #label="{ id }"
      ><span
        :id="`${id}-label`"
        class="block text-xs font-semibold text-muted"
        >{{ field.label }}</span
      ></template
    >
    <template #default="{ id, describedBy }"
      ><p
        :aria-labelledby="`${id}-label`"
        :aria-describedby="describedBy"
        class="break-words text-sm"
      >
        {{ display }}
      </p></template
    >
  </FormField>
  <TextInput
    v-else-if="field.sensitive"
    :label="field.label"
    :model-value="text"
    type="password"
    autocomplete="new-password"
    :helper="
      secretSet
        ? 'A value is set. Enter a replacement to change it.'
        : text
          ? 'Replacement value.'
          : 'No value is set.'
    "
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="update"
  />
  <FormField
    v-else-if="field.lookup && !lookup"
    :label="field.label"
    :error="errors"
  >
    <p class="text-sm text-muted">This lookup is currently unavailable.</p>
  </FormField>
  <Select
    v-else-if="field.lookup && field.multiple"
    :multiple="true"
    :label="field.label"
    :model-value="items"
    :provider="lookup?.provider"
    :selected-options="lookup?.selectedOptions ?? []"
    locale="en"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    clearable
    @update:model-value="update"
  />
  <Select
    v-else-if="field.lookup || field.choices.length"
    :label="field.label"
    :model-value="text ?? ''"
    :options="options"
    :provider="field.lookup ? lookup?.provider : undefined"
    :selected-options="lookup?.selectedOptions ?? []"
    locale="en"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    clearable
    @update:model-value="update($event || null)"
  />
  <Checkbox
    v-else-if="field.type === 'boolean'"
    :label="field.label"
    :model-value="typeof modelValue === 'boolean' ? modelValue : null"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="update"
  />
  <NumberInput
    v-else-if="field.type === 'integer'"
    :label="field.label"
    :model-value="typeof modelValue === 'number' ? String(modelValue) : text"
    kind="integer"
    locale="en"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="integer"
  />
  <TagsInput
    v-else-if="field.type === 'uuid' && field.multiple"
    :label="field.label"
    :model-value="items"
    :max-tags="100"
    helper="Enter public UUIDs."
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="update([...$event])"
  />
  <TextInput
    v-else-if="['text', 'dictionary', 'uuid', 'datetime'].includes(field.type)"
    :label="field.label"
    :model-value="text"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    :nullable="field.type !== 'text'"
    :helper="
      field.type === 'datetime'
        ? 'UTC timestamp, for example 2026-01-01T12:00:00Z.'
        : field.type === 'uuid'
          ? 'Public UUID.'
          : ''
    "
    @update:model-value="update"
  />
  <FormField v-else :label="field.label" :error="errors">
    <p class="text-sm text-muted">This field is currently unavailable.</p>
  </FormField>
</template>
