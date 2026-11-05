<script setup lang="ts">
import { adminBooleanTone } from './booleanTone';
import {
  BooleanBadge,
  DateTimeInput,
  formatApplicationInstant,
} from '@/runa/ui';
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, ref, watch } from 'vue';
import {
  ValuePreview,
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
const props = withDefaults(defineProps<AdminFieldProps>(), { clearable: true });
const emit = defineEmits<AdminFieldEmits>();
const edited = ref(false);
watch(
  () => props.field.key,
  () => {
    edited.value = false;
  },
);
const messages = computed(() => [
  ...props.errors.map(tr),
  ...(edited.value && !props.readonly
    ? validateAdminField(props.field, props.modelValue, adminLocale.value).map(
        tr,
      )
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
const temporalDraft = ref('');
const temporalValue = ref<string | null>(null);
watch(
  () => [props.field, props.modelValue] as const,
  ([field, value]) => {
    if (
      value === null ||
      (typeof value === 'string' && !validateAdminField(field, value).length)
    )
      temporalValue.value = typeof value === 'string' ? value : null;
  },
  { immediate: true, flush: 'sync' },
);
function temporalValidity(valid: boolean) {
  if (!valid && temporalDraft.value) update(temporalDraft.value);
}
const items = computed(() =>
  Array.isArray(props.modelValue)
    ? props.modelValue.filter(
        (item): item is string => typeof item === 'string',
      )
    : [],
);
const options = computed(() =>
  props.field.choices.map((value) => ({ value, label: tr(value) })),
);
const display = computed(() => {
  if (props.field.sensitive) return secretSet.value ? tr('Set') : tr('Not set');
  if (props.modelValue === null || props.modelValue === '') return '—';
  if (props.field.type === 'datetime' && typeof props.modelValue === 'string')
    return formatApplicationInstant(props.modelValue, adminLocale.value);
  if (typeof props.modelValue === 'boolean')
    return props.modelValue ? tr('Yes') : tr('No');
  if (
    typeof props.modelValue === 'string' &&
    props.field.choices.includes(props.modelValue)
  )
    return tr(props.modelValue);
  if (Array.isArray(props.modelValue))
    return (
      items.value
        .map((value) =>
          props.field.choices.includes(value) ? tr(value) : value,
        )
        .join(', ') || '—'
    );
  return typeof props.modelValue === 'object'
    ? tr('Unavailable')
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
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :error="errors.map(tr)"
  >
    <template #label="{ id }"
      ><span
        :id="`${id}-label`"
        :class="
          hideLabel ? 'sr-only' : 'block text-xs font-semibold text-muted'
        "
        >{{ tr(field.label) }}</span
      ></template
    >
    <template #default="{ id, describedBy }"
      ><BooleanBadge
        v-if="typeof modelValue === 'boolean'"
        :value="modelValue"
        :locale="adminLocale"
        :tone="adminBooleanTone(field.key, modelValue)" />
      <ValuePreview
        v-else
        :aria-labelledby="`${id}-label`"
        :aria-describedby="describedBy"
        :text="display"
        :locale="adminLocale"
    /></template>
  </FormField>
  <TextInput
    v-else-if="field.sensitive"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="text"
    type="password"
    autocomplete="new-password"
    :helper="
      secretSet
        ? tr('A value is set. Enter a replacement to change it.')
        : text
          ? tr('Replacement value.')
          : tr('No value is set.')
    "
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="update"
  />
  <FormField
    v-else-if="field.lookup && !lookup"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :error="errors.map(tr)"
  >
    <p class="text-sm text-muted">
      {{ tr('This lookup is currently unavailable.') }}
    </p>
  </FormField>
  <Select
    v-else-if="field.lookup && field.multiple"
    :id="id ?? ''"
    :multiple="true"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="items"
    :provider="lookup?.provider"
    :provider-search="lookup?.searchable ?? true"
    :selected-options="lookup?.selectedOptions ?? []"
    :locale="adminLocale"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    :clearable="clearable ?? true"
    @update:model-value="update"
  />
  <Select
    v-else-if="field.lookup || field.choices.length"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="text ?? ''"
    :options="options"
    :provider="field.lookup ? lookup?.provider : undefined"
    :provider-search="lookup?.searchable ?? true"
    :selected-options="lookup?.selectedOptions ?? []"
    :locale="adminLocale"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    :clearable="clearable ?? true"
    @update:model-value="update($event || null)"
  />
  <Checkbox
    v-else-if="field.type === 'boolean'"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="typeof modelValue === 'boolean' ? modelValue : null"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="update"
  />
  <NumberInput
    v-else-if="field.type === 'integer'"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="typeof modelValue === 'number' ? String(modelValue) : text"
    kind="integer"
    :locale="adminLocale"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="integer"
  />
  <TagsInput
    v-else-if="field.type === 'uuid' && field.multiple"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="items"
    :max-tags="100"
    :helper="tr('Enter public UUIDs.')"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @update:model-value="update([...$event])"
  />
  <DateTimeInput
    v-else-if="field.type === 'datetime'"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="temporalValue"
    :locale="adminLocale"
    :invalid-message="tr('Enter a valid date and time.')"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    @draft="temporalDraft = $event"
    @validity="temporalValidity"
    @update:model-value="update"
  />
  <TextInput
    v-else-if="['text', 'dictionary', 'uuid'].includes(field.type)"
    :id="id ?? ''"
    :label="tr(field.label)"
    :hide-label="hideLabel ?? false"
    :model-value="text"
    :error="messages"
    :disabled="busy"
    :required="field.required"
    :nullable="field.type !== 'text'"
    :helper="field.type === 'uuid' ? tr('Public UUID.') : ''"
    @update:model-value="update"
  />
  <FormField v-else :label="tr(field.label)" :error="errors.map(tr)">
    <p class="text-sm text-muted">
      {{ tr('This field is currently unavailable.') }}
    </p>
  </FormField>
</template>
