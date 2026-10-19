<script setup lang="ts">
import { computed, onMounted, ref, useAttrs, watch } from 'vue';
import AppFormField from './AppFormField.vue';
import {
  isDateOnly,
  isTimeOnly,
  isInstant,
  resolveLocalDateTime,
  toLocalDateTime,
} from '../lib/temporal';
import type {
  TemporalInputProps,
  TemporalKind,
  TimeDisambiguation,
} from '../runa/ui/forms';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<
    TemporalInputProps & {
      kind: TemporalKind;
      timeZone?: string;
      disambiguation?: TimeDisambiguation;
      zoneLabel?: string;
    }
  >(),
  {
    locale: 'pl',
    helper: '',
    error: '',
    invalidMessage: 'Check the date or time.',
    disabled: false,
    readonly: false,
    required: false,
    timeZone: 'UTC',
    disambiguation: 'reject',
    zoneLabel: 'Time zone',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  validity: [valid: boolean];
  draft: [value: string];
}>();
const attrs = useAttrs();
const control = ref<globalThis.HTMLInputElement>();
const invalid = ref(false);
const reason = ref('');
let emitted: string | null | undefined;
function display() {
  if (props.kind !== 'datetime-local') return props.modelValue ?? '';
  try {
    return toLocalDateTime(props.modelValue, props.timeZone);
  } catch {
    return '';
  }
}
function reset() {
  reason.value = '';
  invalid.value =
    props.modelValue !== null &&
    !(props.kind === 'date'
      ? isDateOnly(props.modelValue)
      : props.kind === 'time'
        ? isTimeOnly(props.modelValue)
        : isInstant(props.modelValue));
  if (props.kind === 'datetime-local') {
    try {
      toLocalDateTime(props.modelValue, props.timeZone);
    } catch {
      invalid.value = true;
    }
  }
  if (control.value) control.value.value = display();
  emit('validity', !invalid.value);
}
onMounted(reset);
watch(
  () => [props.modelValue, props.kind, props.timeZone] as const,
  ([value, kind, zone], [, oldKind, oldZone]) => {
    if (value !== emitted || kind !== oldKind || zone !== oldZone) reset();
  },
);
watch(
  () => props.disambiguation,
  () => {
    if (control.value) change();
  },
);
function change() {
  if (!control.value || props.disabled || props.readonly) return;
  const raw = control.value.value;
  emit('draft', raw);
  if (control.value.validity.badInput) {
    invalid.value = true;
    emit('validity', false);
    return;
  }
  if (props.kind === 'datetime-local') {
    const result = resolveLocalDateTime(
      raw || null,
      props.timeZone,
      props.disambiguation,
    );
    invalid.value = !result.valid;
    reason.value = result.valid ? '' : result.reason;
    if (result.valid) {
      emitted = result.value;
      emit('update:modelValue', result.value);
    }
  } else {
    invalid.value =
      !!raw && !(props.kind === 'date' ? isDateOnly(raw) : isTimeOnly(raw));
    if (!invalid.value) {
      emitted = raw || null;
      emit('update:modelValue', emitted);
    }
  }
  emit('validity', !invalid.value);
}
const messages = computed(() => [
  ...(typeof props.error === 'string'
    ? props.error
      ? [props.error]
      : []
    : props.error),
  ...(invalid.value ? [props.invalidMessage] : []),
]);
const description = computed(() =>
  [
    props.helper,
    props.kind === 'datetime-local'
      ? `${props.zoneLabel}: ${props.timeZone}`
      : '',
  ]
    .filter(Boolean)
    .join(' '),
);
defineExpose({ reset, focus: () => control.value?.focus() });
</script>
<template>
  <AppFormField
    :label="label"
    :helper="description"
    :error="messages"
    :required="required"
    ><template #default="{ id, describedBy, invalid: fieldInvalid }"
      ><input
        v-bind="attrs"
        :id="id"
        ref="control"
        :type="kind"
        :lang="locale"
        :disabled="disabled"
        :readonly="readonly"
        :required="required"
        :aria-invalid="fieldInvalid || undefined"
        :aria-describedby="
          [attrs['aria-describedby'], describedBy].filter(Boolean).join(' ') ||
          undefined
        "
        :data-time-error="reason || undefined"
        :step="kind === 'date' ? undefined : '0.001'"
        class="runa-field-control"
        @input="change" /></template
  ></AppFormField>
</template>
