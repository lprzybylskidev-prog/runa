<script setup lang="ts">
import { computed, onMounted, ref, useAttrs, watch } from 'vue';
import AppFormField from './AppFormField.vue';
import AppPopover from './AppPopover.vue';
import AppButton from './AppButton.vue';
import AppCalendarPicker from './AppCalendarPicker.vue';
import AppTimePicker from './AppTimePicker.vue';
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
const rawDraft = ref('');
const open = ref(false);
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const pickerLabel = computed(
  () => `${text('Wybierz', 'Choose')}: ${props.label}`,
);
const placeholder = computed(() =>
  props.kind === 'time'
    ? 'HH:mm'
    : `${props.locale === 'pl' ? 'DD.MM.RRRR' : 'YYYY-MM-DD'}${props.kind === 'datetime-local' ? ' HH:mm' : ''}`,
);
function parseDraft(value: string) {
  const normalized = value.trim().replace(' ', 'T');
  if (props.kind === 'time' || normalized.split('T').length > 2)
    return normalized;
  const [date, time] = normalized.split('T');
  const match = /^(\d{2})\.(\d{2})\.(\d{4})$/.exec(date ?? '');
  const canonical = match
    ? `${match[3]}-${match[2]}-${match[1]}`
    : (date ?? '');
  return canonical + (time !== undefined ? `T${time}` : '');
}
function formatDraft(value: string) {
  if (!value || props.kind === 'time') return value;
  const [date, time] = value.split('T');
  const localized =
    props.locale === 'pl' && date && isDateOnly(date)
      ? date.split('-').reverse().join('.')
      : date;
  return `${localized ?? ''}${time !== undefined ? ` ${time}` : ''}`;
}
function zonedToday() {
  try {
    return toLocalDateTime(new Date().toISOString(), props.timeZone).slice(
      0,
      10,
    );
  } catch {
    return undefined;
  }
}
const parsedDraft = computed(() => parseDraft(rawDraft.value));
const selectedDate = computed(() => {
  const value = parsedDraft.value.split('T')[0] ?? '';
  return isDateOnly(value) ? value : null;
});
const selectedTime = computed(() => {
  const value =
    props.kind === 'time'
      ? parsedDraft.value
      : (parsedDraft.value.split('T')[1] ?? '');
  return isTimeOnly(value) ? value : null;
});
function setPicker(value: string) {
  rawDraft.value = formatDraft(value);
  if (control.value) control.value.value = rawDraft.value;
  change();
}
function pickDate(value: string) {
  if (props.disabled || props.readonly) return;
  setPicker(
    props.kind === 'date' ? value : `${value}T${selectedTime.value ?? '00:00'}`,
  );
  if (props.kind === 'date') open.value = false;
}
function pickTime(value: string) {
  if (props.disabled || props.readonly) return;
  if (props.kind === 'time') setPicker(value);
  else if (selectedDate.value) setPicker(`${selectedDate.value}T${value}`);
}
function clear() {
  setPicker('');
  open.value = false;
}
function keyboard(event: globalThis.KeyboardEvent) {
  if (
    event.altKey &&
    event.key === 'ArrowDown' &&
    !props.disabled &&
    !props.readonly
  ) {
    event.preventDefault();
    open.value = true;
  }
}

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
  rawDraft.value = formatDraft(display());
  if (control.value) control.value.value = rawDraft.value;
  emit('validity', !invalid.value);
}
onMounted(reset);
watch(
  () => [props.modelValue, props.kind, props.timeZone, props.locale] as const,
  ([value, kind, zone, locale], [, oldKind, oldZone, oldLocale]) => {
    if (
      value !== emitted ||
      kind !== oldKind ||
      zone !== oldZone ||
      locale !== oldLocale
    )
      reset();
  },
);
watch(
  () => props.disambiguation,
  () => {
    if (control.value) change();
  },
);
function change(event?: Event) {
  if (event instanceof globalThis.InputEvent && event.isComposing) return;
  if (!control.value || props.disabled || props.readonly) return;
  rawDraft.value = control.value.value;
  const raw = parseDraft(rawDraft.value);
  emit('draft', rawDraft.value);
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
watch(
  () => [props.disabled, props.readonly],
  ([disabled, readonly]) => {
    if (disabled || readonly) open.value = false;
  },
);
defineExpose({ reset, focus: () => control.value?.focus() });
</script>
<template>
  <AppFormField
    :label="label"
    :helper="description"
    :error="messages"
    :required="required"
  >
    <template #default="{ id, describedBy, invalid: fieldInvalid }">
      <div class="temporal-field-row">
        <input
          v-bind="attrs"
          :id="id"
          ref="control"
          type="text"
          :placeholder="placeholder"
          :lang="locale"
          :disabled="disabled"
          :readonly="readonly"
          :required="required"
          autocomplete="off"
          spellcheck="false"
          :aria-invalid="fieldInvalid || undefined"
          :aria-describedby="
            [attrs['aria-describedby'], describedBy]
              .filter(Boolean)
              .join(' ') || undefined
          "
          :data-time-error="reason || undefined"
          class="runa-field-control"
          @keydown="keyboard"
          @input="change"
          @compositionend="change"
        />
        <AppPopover
          v-model="open"
          :label="pickerLabel"
          picker
          initial-focus="[data-picker-initial]"
        >
          <template #trigger="{ toggle, panelId }"
            ><AppButton
              :disabled="disabled || readonly"
              :aria-label="pickerLabel"
              aria-haspopup="dialog"
              :aria-expanded="open"
              :aria-controls="open ? panelId : undefined"
              class="temporal-trigger"
              @click="toggle"
              ><svg
                aria-hidden="true"
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.6"
              >
                <template v-if="kind === 'time'">
                  <circle cx="12" cy="12" r="9" />
                  <path d="M12 7v5l3 2" />
                </template>
                <template v-else>
                  <rect x="3" y="5" width="18" height="16" rx="2" />
                  <path d="M3 10h18M8 3v4m8-4v4M7 14h3m4 0h3m-10 3h3" />
                </template></svg></AppButton
          ></template>
          <template #default="{ close }">
            <p class="mb-3 text-xs font-semibold text-ink">{{ label }}</p>
            <AppCalendarPicker
              v-if="kind !== 'time'"
              :model-value="selectedDate"
              :today="kind === 'datetime-local' ? zonedToday() : undefined"
              :locale="locale"
              @update:model-value="
                pickDate($event);
                if (kind === 'date') close();
              "
            />
            <div
              v-if="kind !== 'date'"
              :class="{
                'mt-4 border-t border-line pt-4': kind === 'datetime-local',
              }"
            >
              <AppTimePicker
                v-if="kind === 'time' || selectedDate"
                :model-value="selectedTime"
                :locale="locale"
                @update:model-value="pickTime"
              />
              <p v-else class="text-xs text-muted">
                {{ text('Najpierw wybierz datę.', 'Choose a date first.') }}
              </p>
            </div>
            <p v-if="kind === 'datetime-local'" class="mt-3 text-xs text-muted">
              {{ zoneLabel }}: {{ timeZone }}
            </p>
            <p
              v-if="invalid"
              role="status"
              class="mt-3 text-xs text-[var(--runa-danger-text)]"
            >
              {{ invalidMessage }}
            </p>
            <div
              class="mt-3 flex justify-between gap-2 border-t border-line pt-3"
            >
              <AppButton
                size="sm"
                variant="ghost"
                @click="
                  clear();
                  close();
                "
                >{{ text('Wyczyść', 'Clear') }}</AppButton
              ><AppButton size="sm" variant="soft" @click="close()">{{
                text('Gotowe', 'Done')
              }}</AppButton>
            </div>
          </template>
        </AppPopover>
      </div>
    </template>
  </AppFormField>
</template>
<style scoped>
.temporal-field-row {
  display: flex;
  min-width: 0;
}
.temporal-field-row > input {
  flex: 1;
  border-top-right-radius: 0;
  border-bottom-right-radius: 0;
}
.temporal-trigger.app-button {
  padding: 0;
  width: 44px;
  height: 44px;
  min-height: 44px;
  border-left: 0;
  border-top-left-radius: 0;
  border-bottom-left-radius: 0;
}
</style>
