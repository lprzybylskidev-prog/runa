<script setup lang="ts">
import { computed, ref, useAttrs, useId, watch } from 'vue';
import AppTextInput from './AppTextInput.vue';
import { parseDecimal, formatDecimal } from '../lib/decimal';
import type { NumericKind } from '../runa/ui/forms';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    label: string;
    size?: 'sm' | 'md';
    hideLabel?: boolean;
    kind?: NumericKind;
    locale?: 'pl' | 'en';
    scale?: number | undefined;
    error?: string | readonly string[];
    invalidMessage?: string;
    unit?: string;
    disabled?: boolean;
    readonly?: boolean;
  }>(),
  {
    size: 'md',
    hideLabel: false,
    kind: 'decimal',
    locale: 'pl',
    scale: undefined,
    error: '',
    invalidMessage: 'Enter a valid number.',
    unit: '',
    disabled: false,
    readonly: false,
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  validity: [valid: boolean];
  blur: [event: globalThis.FocusEvent];
}>();
const attrs = useAttrs();
const unitId = useId();
const descriptions = computed(
  () =>
    [
      typeof attrs['aria-describedby'] === 'string'
        ? attrs['aria-describedby']
        : '',
      props.unit ? unitId : '',
    ]
      .filter(Boolean)
      .join(' ') || undefined,
);
function display(value: string | null) {
  return parseDecimal(value).valid
    ? formatDecimal(value, props.locale)
    : (value ?? '');
}
const draft = ref(display(props.modelValue));
let emitted: string | null | undefined;
watch(
  () => [props.modelValue, props.locale] as const,
  ([value, locale], [, previousLocale]) => {
    if (value !== emitted || locale !== previousLocale)
      draft.value = display(value);
  },
);
const result = computed(() =>
  parseDecimal(draft.value, {
    kind: props.kind,
    locale: props.locale,
    ...(props.scale === undefined ? {} : { scale: props.scale }),
  }),
);
const messages = computed(() => [
  ...(typeof props.error === 'string'
    ? props.error
      ? [props.error]
      : []
    : props.error),
  ...(!result.value.valid ? [props.invalidMessage] : []),
]);
function update(value: string | null) {
  draft.value = value ?? '';
  emitted = result.value.valid ? result.value.value : draft.value;
  emit('update:modelValue', emitted);
  emit('validity', result.value.valid);
}
function blur(event: globalThis.FocusEvent) {
  if (result.value.valid)
    draft.value = formatDecimal(result.value.value, props.locale);
  emit('blur', event);
}
</script>
<template>
  <div class="min-w-0">
    <AppTextInput
      v-bind="attrs"
      :aria-describedby="descriptions"
      :model-value="draft"
      :label="label"
      :size="size"
      :hide-label="hideLabel"
      :disabled="disabled"
      :readonly="readonly"
      :error="messages"
      :inputmode="kind === 'integer' ? 'numeric' : 'decimal'"
      @update:model-value="update"
      @blur="blur"
      ><template v-if="unit" #suffix
        ><span :id="unitId">{{ unit }}</span></template
      ></AppTextInput
    >
  </div>
</template>
