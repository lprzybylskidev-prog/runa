<script setup lang="ts">
import { computed, nextTick, onMounted, ref } from 'vue';
import AppButton from './AppButton.vue';
import { isTimeOnly } from '../lib/temporal';
const props = defineProps<{ modelValue: string | null; locale: 'pl' | 'en' }>();
const emit = defineEmits<{ 'update:modelValue': [value: string] }>();
const root = ref<globalThis.HTMLElement>();
const parts = computed(() =>
  (props.modelValue && isTimeOnly(props.modelValue)
    ? props.modelValue
    : '00:00'
  ).split(':'),
);
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
onMounted(() => {
  for (const column of root.value?.querySelectorAll<globalThis.HTMLElement>(
    '[role=listbox]',
  ) ?? []) {
    const selected = column.querySelector<globalThis.HTMLElement>(
      '[aria-selected=true]',
    );
    if (selected) column.scrollTop = selected.offsetTop - column.offsetTop - 72;
  }
});
const pad = (n: number) => String(n).padStart(2, '0');
function choose(kind: 0 | 1, value: number) {
  const next = [...parts.value];
  next[kind] = pad(value);
  emit('update:modelValue', next.join(':'));
}
async function key(
  event: globalThis.KeyboardEvent,
  kind: 0 | 1,
  value: number,
) {
  let target = value;
  const max = kind === 0 ? 23 : 59;
  if (event.key === 'ArrowDown' || event.key === 'ArrowRight')
    target = (value + 1) % (max + 1);
  else if (event.key === 'ArrowUp' || event.key === 'ArrowLeft')
    target = (value + max) % (max + 1);
  else if (event.key === 'Home') target = 0;
  else if (event.key === 'End') target = max;
  else return;
  event.preventDefault();
  choose(kind, target);
  await nextTick();
  root.value
    ?.querySelector<globalThis.HTMLElement>(`[data-time="${kind}-${target}"]`)
    ?.focus({ preventScroll: true });
  root.value
    ?.querySelector<globalThis.HTMLElement>(`[data-time="${kind}-${target}"]`)
    ?.scrollIntoView({ block: 'nearest' });
}
</script>
<template>
  <div ref="root" class="space-y-3">
    <p
      class="text-center font-semibold text-ink tabular-nums"
      aria-live="polite"
    >
      {{ parts.join(':') }}
    </p>
    <div class="grid grid-cols-2 gap-3">
      <div
        v-for="kind in [0, 1] as const"
        :key="kind"
        class="min-w-0 space-y-2"
      >
        <p class="text-center text-xs font-semibold text-muted">
          {{ kind === 0 ? text('Godzina', 'Hour') : text('Minuta', 'Minute') }}
        </p>
        <div
          class="time-options"
          role="listbox"
          tabindex="-1"
          :aria-label="
            kind === 0 ? text('Godzina', 'Hour') : text('Minuta', 'Minute')
          "
        >
          <AppButton
            v-for="n in kind === 0 ? 24 : 60"
            :key="n"
            role="option"
            :aria-selected="Number(parts[kind]) === n - 1"
            :tabindex="Number(parts[kind]) === n - 1 ? 0 : -1"
            :data-picker-initial="
              kind === 0 && Number(parts[kind]) === n - 1 ? true : undefined
            "
            :data-time="`${kind}-${n - 1}`"
            size="sm"
            :variant="Number(parts[kind]) === n - 1 ? 'solid' : 'ghost'"
            class="w-full tabular-nums"
            @click="choose(kind, n - 1)"
            @keydown="key($event, kind, n - 1)"
            >{{ pad(n - 1) }}</AppButton
          >
        </div>
      </div>
    </div>
    <p class="text-xs text-muted">
      {{
        text(
          'Format 24-godzinny. Sekundy możesz dopisać w polu.',
          '24-hour format. Seconds can be entered in the field.',
        )
      }}
    </p>
  </div>
</template>
<style scoped>
.time-options {
  display: grid;
  gap: 4px;
  max-height: 184px;
  overflow-y: auto;
  padding: 4px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  scrollbar-color: var(--runa-control-border) var(--runa-surface);
}
.time-options :focus-visible {
  outline-offset: 0;
}
</style>
