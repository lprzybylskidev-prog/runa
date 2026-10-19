<script setup lang="ts">
import { useContentMotion } from '../composables/useContentMotion';
import { computed, nextTick, ref } from 'vue';
import AppButton from './AppButton.vue';
import { isDateOnly } from '../lib/temporal';
const props = defineProps<{
  modelValue: string | null;
  locale: 'pl' | 'en';
  today?: string | undefined;
}>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const calendarNow = new Date();
function iso(date: Date) {
  return `${String(date.getUTCFullYear()).padStart(4, '0')}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`;
}
function dateOf(value: string) {
  const d = new Date(0);
  const [y, m, day] = value.split('-').map(Number);
  d.setUTCFullYear(y!, m! - 1, day!);
  d.setUTCHours(0, 0, 0, 0);
  return d;
}
const todayValue =
  props.today && isDateOnly(props.today)
    ? props.today
    : `${calendarNow.getFullYear()}-${String(calendarNow.getMonth() + 1).padStart(2, '0')}-${String(calendarNow.getDate()).padStart(2, '0')}`;
const focused = ref(
  props.modelValue && isDateOnly(props.modelValue)
    ? props.modelValue
    : todayValue,
);
const shown = ref(focused.value.slice(0, 7));
const mode = ref<'days' | 'months' | 'years'>('days');
const grid = ref<globalThis.HTMLElement>();
const root = ref<globalThis.HTMLElement>();
async function chooseMonth(index: number) {
  show(year.value, index);
  mode.value = 'days';
  await nextTick();
  grid.value?.querySelector<globalThis.HTMLElement>('[tabindex="0"]')?.focus();
}
async function chooseYear(value: number) {
  show(value, month.value);
  mode.value = 'months';
  await nextTick();
  root.value
    ?.querySelector<globalThis.HTMLElement>(`[data-month="${month.value}"]`)
    ?.focus();
}
const year = computed(() => Number(shown.value.slice(0, 4)));
const month = computed(() => Number(shown.value.slice(5, 7)) - 1);
const localeTag = computed(() => (props.locale === 'pl' ? 'pl-PL' : 'en-GB'));
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const months = computed(() =>
  Array.from({ length: 12 }, (_, i) =>
    new Intl.DateTimeFormat(localeTag.value, {
      month: 'long',
      timeZone: 'UTC',
    }).format(new Date(Date.UTC(2000, i, 1))),
  ),
);
const weekdays = computed(() =>
  Array.from({ length: 7 }, (_, i) =>
    new Intl.DateTimeFormat(localeTag.value, {
      weekday: 'short',
      timeZone: 'UTC',
    }).format(new Date(Date.UTC(2024, 0, 1 + i))),
  ),
);
const days = computed(() => {
  const start = dateOf(`${shown.value}-01`);
  const offset = (start.getUTCDay() + 6) % 7;
  start.setUTCDate(1 - offset);
  return Array.from({ length: 42 }, (_, i) => {
    const d = new Date(start);
    d.setUTCDate(start.getUTCDate() + i);
    return {
      value: iso(d),
      day: d.getUTCDate(),
      inside: d.getUTCMonth() === month.value,
      valid: d.getUTCFullYear() >= 1 && d.getUTCFullYear() <= 9999,
    };
  });
});
const years = computed(() => {
  const first = Math.max(
    1,
    Math.min(9988, Math.floor((year.value - 1) / 12) * 12 + 1),
  );
  return Array.from({ length: 12 }, (_, i) => first + i);
});
function show(y: number, m: number) {
  const d = dateOf(`${String(y).padStart(4, '0')}-01-01`);
  d.setUTCMonth(m);
  if (d.getUTCFullYear() < 1 || d.getUTCFullYear() > 9999) return;
  shown.value = iso(d).slice(0, 7);
  focused.value = `${shown.value}-01`;
}
function navigate(delta: number) {
  show(
    year.value +
      (mode.value === 'years'
        ? delta * 12
        : mode.value === 'months'
          ? delta
          : 0),
    month.value + (mode.value === 'days' ? delta : 0),
  );
}
function choose(value: string) {
  focused.value = value;
  shown.value = value.slice(0, 7);
  emit('update:modelValue', value);
}
async function move(event: globalThis.KeyboardEvent, value: string) {
  const d = dateOf(value);
  const day = (d.getUTCDay() + 6) % 7;
  let next: Date | undefined;
  if (
    ['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(
      event.key,
    )
  ) {
    const delta =
      event.key === 'ArrowLeft'
        ? -1
        : event.key === 'ArrowRight'
          ? 1
          : event.key === 'ArrowUp'
            ? -7
            : event.key === 'ArrowDown'
              ? 7
              : event.key === 'Home'
                ? -day
                : 6 - day;
    d.setUTCDate(d.getUTCDate() + delta);
    next = d;
  }
  if (event.key === 'PageUp' || event.key === 'PageDown') {
    const dir = event.key === 'PageUp' ? -1 : 1;
    const original = d.getUTCDate();
    d.setUTCDate(1);
    d.setUTCMonth(d.getUTCMonth() + dir * (event.shiftKey ? 12 : 1));
    const last = new Date(d);
    last.setUTCMonth(last.getUTCMonth() + 1);
    last.setUTCDate(0);
    d.setUTCDate(Math.min(original, last.getUTCDate()));
    next = d;
  }
  if (!next) return;
  event.preventDefault();
  if (next.getUTCFullYear() < 1 || next.getUTCFullYear() > 9999) return;
  focused.value = iso(next);
  shown.value = focused.value.slice(0, 7);
  await nextTick();
  grid.value
    ?.querySelector<globalThis.HTMLElement>(`[data-date="${focused.value}"]`)
    ?.focus();
}
const fullLabel = (value: string) =>
  new Intl.DateTimeFormat(localeTag.value, {
    dateStyle: 'full',
    timeZone: 'UTC',
  }).format(dateOf(value));
useContentMotion(root, () => `${mode.value}:${shown.value}`);
</script>
<template>
  <div ref="root" class="calendar-picker space-y-3">
    <div class="flex items-center justify-between gap-1">
      <AppButton
        size="sm"
        icon-only
        :aria-label="text('Poprzedni', 'Previous')"
        @click="navigate(-1)"
        >‹</AppButton
      >
      <div class="flex min-w-0 items-center gap-1" aria-live="polite">
        <AppButton
          size="sm"
          variant="ghost"
          :aria-label="text('Wybierz miesiąc', 'Choose month')"
          @click="mode = mode === 'months' ? 'days' : 'months'"
          >{{ months[month] }}</AppButton
        >
        <AppButton
          size="sm"
          variant="ghost"
          :aria-label="text('Wybierz rok', 'Choose year')"
          @click="mode = mode === 'years' ? 'days' : 'years'"
          >{{ year }}</AppButton
        >
      </div>
      <AppButton
        size="sm"
        icon-only
        :aria-label="text('Następny', 'Next')"
        @click="navigate(1)"
        >›</AppButton
      >
    </div>
    <div
      v-if="mode === 'days'"
      ref="grid"
      role="grid"
      :aria-label="`${months[month]} ${year}`"
    >
      <div role="row" class="grid grid-cols-7 gap-1 mb-1">
        <span
          v-for="day in weekdays"
          :key="day"
          role="columnheader"
          class="py-2 text-center text-xs text-muted"
          >{{ day }}</span
        >
      </div>
      <div
        v-for="week in 6"
        :key="week"
        role="row"
        class="grid grid-cols-7 gap-1 mb-1"
      >
        <AppButton
          v-for="day in days.slice((week - 1) * 7, week * 7)"
          :key="day.value"
          role="gridcell"
          size="sm"
          :variant="modelValue === day.value ? 'solid' : 'ghost'"
          :disabled="!day.valid"
          :aria-label="fullLabel(day.value)"
          :aria-selected="modelValue === day.value"
          :aria-current="day.value === todayValue ? 'date' : undefined"
          :tabindex="day.value === focused ? 0 : -1"
          :data-date="day.value"
          :data-picker-initial="day.value === focused ? true : undefined"
          :class="{ 'calendar-outside': !day.inside }"
          class="calendar-day"
          @click="choose(day.value)"
          @keydown="move($event, day.value)"
          >{{ day.day }}</AppButton
        >
      </div>
    </div>
    <div
      v-else-if="mode === 'months'"
      class="grid grid-cols-3 gap-2"
      :aria-label="text('Miesiące', 'Months')"
    >
      <AppButton
        v-for="(name, i) in months"
        :key="name"
        size="sm"
        :aria-pressed="i === month"
        :data-month="i"
        @click="chooseMonth(i)"
        >{{ name }}</AppButton
      >
    </div>
    <div
      v-else
      class="grid grid-cols-3 gap-2"
      :aria-label="text('Lata', 'Years')"
    >
      <AppButton
        v-for="y in years"
        :key="y"
        size="sm"
        :aria-pressed="y === year"
        @click="chooseYear(y)"
        >{{ y }}</AppButton
      >
    </div>
    <AppButton
      size="sm"
      variant="soft"
      class="w-full"
      @click="choose(todayValue)"
      >{{ text('Dzisiaj', 'Today') }}</AppButton
    >
  </div>
</template>
<style scoped>
.calendar-day.app-button {
  min-width: 0;
  width: 100%;
  padding: 4px 0;
  min-height: 40px;
}
.calendar-day[aria-current='date'] {
  text-decoration: underline;
  text-underline-offset: 4px;
}
.calendar-outside:not([aria-selected='true']) {
  color: var(--runa-text-muted);
}
.calendar-day:focus-visible {
  outline-offset: 0;
  position: relative;
  z-index: 1;
}
</style>
