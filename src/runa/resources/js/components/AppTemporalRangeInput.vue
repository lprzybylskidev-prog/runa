<script setup lang="ts">
import { actionIcons } from '../runa/ui/actionIcons';
import { computed, ref, watch } from 'vue';
import AppFormField from './AppFormField.vue';
import AppPopover from './AppPopover.vue';
import AppButton from './AppButton.vue';
import AppDateInput from './AppDateInput.vue';
import AppDateTimeInput from './AppDateTimeInput.vue';
import {
  formatDateOnly,
  instantTimestamp,
  isInstant,
  validDateRange,
} from '../lib/temporal';
import { formatApplicationInstant } from '../runa/ui/displayDefaults';
import type { TemporalRangeInputProps, DateRangeValue } from '../runa/ui/forms';
const props = withDefaults(
  defineProps<TemporalRangeInputProps & { kind: 'date' | 'datetime' }>(),
  {
    locale: 'pl',
    size: 'md',
    startLabel: '',
    endLabel: '',
    helper: '',
    error: '',
    invalidMessage: '',
    disabled: false,
    readonly: false,
    disambiguation: 'reject',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: DateRangeValue];
  validity: [valid: boolean];
}>();
const open = ref(false);
const draft = ref<DateRangeValue>({ ...props.modelValue });
const endpointValid = ref({ start: true, end: true });
const trigger = ref<globalThis.HTMLButtonElement>();
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const startLabel = computed(() => props.startLabel || text('Od', 'From'));
const endLabel = computed(() => props.endLabel || text('Do', 'Until'));
function valid(value: DateRangeValue): boolean {
  if (props.kind === 'date') return validDateRange(value);
  return (
    (!value.start || isInstant(value.start)) &&
    (!value.end || isInstant(value.end)) &&
    (!value.start ||
      !value.end ||
      instantTimestamp(value.start) <= instantTimestamp(value.end))
  );
}
const draftValid = computed(
  () =>
    endpointValid.value.start && endpointValid.value.end && valid(draft.value),
);
const message = computed(
  () =>
    props.invalidMessage ||
    text('Sprawdź zakres dat.', 'Check the date range.'),
);
function format(value: string) {
  return props.kind === 'date'
    ? formatDateOnly(value, props.locale)
    : props.timeZone
      ? new Date(instantTimestamp(value)).toLocaleString(
          props.locale === 'pl' ? 'pl-PL' : 'en-GB',
          { timeZone: props.timeZone },
        )
      : formatApplicationInstant(value, props.locale);
}
const summary = computed(() => {
  const { start, end } = props.modelValue;
  if (start && end) return `${format(start)} – ${format(end)}`;
  if (start) return `${startLabel.value}: ${format(start)}`;
  if (end) return `${endLabel.value}: ${format(end)}`;
  return `${startLabel.value} – ${endLabel.value}`;
});
function reset() {
  open.value = false;
  draft.value = { ...props.modelValue };
  endpointValid.value = { start: true, end: true };
}
watch(open, () => {
  draft.value = { ...props.modelValue };
  endpointValid.value = { start: true, end: true };
});
watch(
  () => props.modelValue,
  () => {
    if (!open.value) draft.value = { ...props.modelValue };
  },
  { deep: true },
);
watch(
  () => valid(props.modelValue),
  (value) => emit('validity', value),
  { immediate: true },
);
watch(
  () => [props.disabled, props.readonly],
  () => {
    if (props.disabled || props.readonly) reset();
  },
);
function apply(close: () => void) {
  if (!draftValid.value) return;
  emit('update:modelValue', { ...draft.value });
  close();
}
function clear(close: () => void) {
  emit('update:modelValue', { start: null, end: null });
  close();
}
defineExpose({ reset, focus: () => trigger.value?.focus() });
</script>
<template>
  <AppFormField
    :label="label"
    :helper="helper"
    :error="error || (!valid(modelValue) ? message : '')"
  >
    <template #default="{ id, describedBy, invalid }">
      <div class="range-anchor min-w-0">
        <AppPopover v-model="open" :label="label" picker initial-focus="input">
          <template #trigger="{ toggle, panelId }">
            <button
              :id="id"
              ref="trigger"
              type="button"
              class="runa-field-control w-full min-w-0 text-left"
              :data-size="size"
              :disabled="disabled"
              :aria-readonly="readonly || undefined"
              :aria-invalid="invalid || undefined"
              :aria-describedby="describedBy"
              aria-haspopup="dialog"
              :aria-expanded="open"
              :aria-controls="open ? panelId : undefined"
              :title="summary"
              @click="!readonly && toggle()"
            >
              <span class="flex min-w-0 items-center gap-2">
                <span
                  class="min-w-0 flex-1 truncate"
                  :class="
                    !modelValue.start && !modelValue.end ? 'text-muted' : ''
                  "
                  >{{ summary }}</span
                >
                <svg
                  aria-hidden="true"
                  class="shrink-0"
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                >
                  <rect x="3" y="5" width="18" height="16" rx="2" />
                  <path d="M3 10h18M8 3v4m8-4v4M7 14h3m4 0h3m-10 3h3" />
                </svg>
              </span>
            </button>
          </template>
          <template #default="{ close }">
            <div class="space-y-3">
              <component
                :is="kind === 'date' ? AppDateInput : AppDateTimeInput"
                v-for="side in ['start', 'end'] as const"
                :key="side"
                v-model="draft[side]"
                :label="side === 'start' ? startLabel : endLabel"
                :locale="locale"
                :invalid-message="message"
                v-bind="kind === 'datetime' ? { timeZone, disambiguation } : {}"
                @validity="endpointValid[side] = $event"
              />
              <p
                v-if="!draftValid"
                role="status"
                class="text-xs text-[var(--runa-danger-text)]"
              >
                {{ message }}
              </p>
              <div class="flex flex-wrap justify-end gap-2">
                <AppButton
                  :icon="actionIcons.cancel"
                  intent="neutral"
                  size="sm"
                  @click="clear(close)"
                  >{{ text('Wyczyść', 'Clear') }}</AppButton
                >
                <AppButton
                  :icon="actionIcons.cancel"
                  intent="cancel"
                  size="sm"
                  @click="close()"
                  >{{ text('Anuluj', 'Cancel') }}</AppButton
                >
                <AppButton
                  :icon="actionIcons.confirm"
                  intent="commit"
                  size="sm"
                  :disabled="!draftValid"
                  @click="apply(close)"
                  >{{ text('Zastosuj', 'Apply') }}</AppButton
                >
              </div>
            </div>
          </template>
        </AppPopover>
      </div>
    </template>
  </AppFormField>
</template>
<style scoped>
.range-anchor > :deep(.app-popover-anchor) {
  display: flex;
  width: 100%;
  min-width: 0;
}
</style>
