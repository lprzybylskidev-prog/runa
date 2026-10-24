<script setup lang="ts">
import { computed } from 'vue';
const props = withDefaults(
  defineProps<{
    modelValue: number;
    max: number;
    min?: number;
    label: string;
    gradient: string;
    unit?: string;
  }>(),
  { unit: '', min: 0 },
);
const emit = defineEmits<{ 'update:modelValue': [value: number] }>();
const percent = computed(() =>
  Math.max(
    0,
    Math.min(
      100,
      ((props.modelValue - props.min) / (props.max - props.min)) * 100,
    ),
  ),
);
function update(value: number) {
  emit(
    'update:modelValue',
    Math.max(props.min, Math.min(props.max, Math.round(value))),
  );
}
function pointer(event: globalThis.PointerEvent) {
  const target = event.currentTarget as globalThis.HTMLElement;
  if (target.closest('fieldset:disabled, [inert]')) return;
  if (event.type === 'pointerdown') {
    if (event.button !== 0) return;
    target.setPointerCapture(event.pointerId);
    target.focus();
  } else if (!target.hasPointerCapture(event.pointerId)) return;
  const rect = target.getBoundingClientRect();
  update(
    props.min +
      ((event.clientX - rect.left) / rect.width) * (props.max - props.min),
  );
}
function key(event: globalThis.KeyboardEvent) {
  if (
    (event.currentTarget as globalThis.HTMLElement).closest(
      'fieldset:disabled, [inert]',
    )
  )
    return;
  let value = props.modelValue;
  if (['ArrowRight', 'ArrowUp'].includes(event.key)) value += 1;
  else if (['ArrowLeft', 'ArrowDown'].includes(event.key)) value -= 1;
  else if (event.key === 'Home') value = props.min;
  else if (event.key === 'End') value = props.max;
  else if (event.key === 'PageUp') value += 10;
  else if (event.key === 'PageDown') value -= 10;
  else return;
  event.preventDefault();
  update(value);
}
</script>
<template>
  <div>
    <div class="mb-1 flex justify-between gap-3 text-xs text-muted">
      <span>{{ label }}</span
      ><span class="tabular-nums">{{ modelValue }}{{ unit }}</span>
    </div>
    <div
      role="slider"
      tabindex="0"
      :aria-label="label"
      :aria-valuemin="min"
      :aria-valuemax="max"
      :aria-valuenow="modelValue"
      :aria-valuetext="`${modelValue}${unit}`"
      class="picker-slider"
      @pointerdown="pointer"
      @pointermove="pointer"
      @keydown="key"
    >
      <div class="picker-slider-track" :style="{ background: gradient }"></div>
      <span class="picker-slider-thumb" :style="{ left: `${percent}%` }"></span>
    </div>
  </div>
</template>
<style scoped>
.picker-slider {
  position: relative;
  height: 32px;
  margin: 0 8px;
  cursor: crosshair;
  touch-action: none;
  border-radius: 4px;
}
.picker-slider-track {
  position: absolute;
  top: 10px;
  width: 100%;
  height: 12px;
  border: 1px solid var(--runa-control-border);
  border-radius: 3px;
}
.picker-slider-thumb {
  position: absolute;
  top: 7px;
  width: 18px;
  height: 18px;
  border: 2px solid var(--runa-text);
  background: var(--runa-surface-raised);
  border-radius: 50%;
  transform: translateX(-50%);
  box-shadow: 0 0 0 1px var(--runa-surface);
  pointer-events: none;
}
.picker-slider:focus-visible {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 2px;
}
@media (forced-colors: active) {
  .picker-slider-track {
    background: Canvas !important;
  }
  .picker-slider-thumb {
    border-color: Highlight;
  }
}
</style>
