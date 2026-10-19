<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AppTextInput from './AppTextInput.vue';
import AppPopover from './AppPopover.vue';
import AppButton from './AppButton.vue';
import AppPickerSlider from './AppPickerSlider.vue';
import { hexToHsv, hsvToHex } from '../lib/color';
import type { HsvColor } from '../lib/color';
const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    label: string;
    helper?: string;
    error?: string | readonly string[];
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
    pickLabel?: string;
    locale?: 'pl' | 'en';
    invalidMessage?: string;
  }>(),
  {
    helper: '',
    error: '',
    disabled: false,
    readonly: false,
    required: false,
    pickLabel: '',
    locale: 'pl',
    invalidMessage: 'Enter a color in #RRGGBB format.',
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  validity: [valid: boolean];
}>();
const draft = ref(props.modelValue);
const open = ref(false);
const text = (pl: string, en: string) => (props.locale === 'pl' ? pl : en);
const pickerLabel = computed(
  () =>
    props.pickLabel ||
    `${text('Wybierz kolor', 'Choose color')}: ${props.label}`,
);
const hsv = ref<HsvColor>(
  hexToHsv(
    /^#[\da-f]{6}$/i.test(props.modelValue ?? '')
      ? props.modelValue!
      : '#8650ba',
  ),
);
const preview = computed(() => hsvToHex(hsv.value));
const palette = [
  '#8650ba',
  '#b784df',
  '#5d70c9',
  '#368fc6',
  '#32969e',
  '#40957c',
  '#c3994d',
  '#c2607d',
  '#ffffff',
  '#000000',
];
function syncColor(value: string | null) {
  if (value && /^#[\da-f]{6}$/i.test(value)) hsv.value = hexToHsv(value);
}
function adjust(key: keyof HsvColor, value: number) {
  if (props.disabled || props.readonly) return;
  hsv.value = { ...hsv.value, [key]: value };
  draft.value = preview.value;
  emit('update:modelValue', draft.value);
}
function plane(event: globalThis.PointerEvent) {
  if (props.disabled || props.readonly) return;
  const target = event.currentTarget as globalThis.HTMLElement;
  if (event.type === 'pointerdown') {
    if (event.button !== 0) return;
    target.setPointerCapture(event.pointerId);
  } else if (!target.hasPointerCapture(event.pointerId)) return;
  const rect = target.getBoundingClientRect();
  hsv.value = {
    ...hsv.value,
    s: Math.max(
      0,
      Math.min(100, ((event.clientX - rect.left) / rect.width) * 100),
    ),
    v: Math.max(
      0,
      Math.min(100, 100 - ((event.clientY - rect.top) / rect.height) * 100),
    ),
  };
  draft.value = preview.value;
  emit('update:modelValue', draft.value);
}
watch(
  () => [props.disabled, props.readonly],
  ([disabled, readonly]) => {
    if (disabled || readonly) open.value = false;
  },
);
watch(
  () => props.modelValue,
  (value) => {
    draft.value = value;
    if (value?.toLowerCase() !== preview.value) syncColor(value);
  },
);
const valid = computed(
  () => draft.value === null || /^#[\da-f]{6}$/i.test(draft.value),
);
watch(valid, (value) => emit('validity', value), { immediate: true });
const messages = computed(() => [
  ...(typeof props.error === 'string'
    ? props.error
      ? [props.error]
      : []
    : props.error),
  ...(!valid.value ? [props.invalidMessage] : []),
]);
function update(value: string | null) {
  if (props.disabled || props.readonly) return;
  draft.value = value === '' ? null : value;
  syncColor(draft.value);
  emit('update:modelValue', draft.value);
}
</script>
<template>
  <AppTextInput
    :model-value="draft"
    :label="label"
    :helper="helper"
    :error="messages"
    :disabled="disabled"
    :readonly="readonly"
    :required="required"
    nullable
    spellcheck="false"
    autocomplete="off"
    @update:model-value="update"
    ><template #suffix
      ><AppPopover
        v-model="open"
        :label="pickerLabel"
        picker
        initial-focus="[role=slider]"
      >
        <template #trigger="{ toggle, panelId }"
          ><AppButton
            :disabled="disabled || readonly"
            :aria-label="pickerLabel"
            aria-haspopup="dialog"
            :aria-expanded="open"
            :aria-controls="open ? panelId : undefined"
            class="color-swatch"
            @click="toggle"
            ><span
              class="color-chip"
              :style="{ background: valid && draft ? draft : 'transparent' }"
            ></span></AppButton
        ></template>
        <template #default="{ close }">
          <div class="mb-3 flex items-center justify-between gap-3">
            <p class="text-xs font-semibold text-ink">{{ label }}</p>
            <span class="text-xs tabular-nums">{{ preview }}</span>
          </div>
          <div
            class="color-plane"
            aria-hidden="true"
            :style="{ backgroundColor: `hsl(${hsv.h} 100% 50%)` }"
            @pointerdown="plane"
            @pointermove="plane"
          >
            <span
              class="plane-cursor"
              :style="{
                left: `${hsv.s}%`,
                top: `${100 - hsv.v}%`,
                background: preview,
              }"
            ></span>
          </div>
          <div class="mt-3 space-y-2">
            <AppPickerSlider
              :model-value="Math.round(hsv.h)"
              :max="359"
              :label="text('Odcień', 'Hue')"
              unit="°"
              gradient="linear-gradient(to right,#f00,#ff0,#0f0,#0ff,#00f,#f0f,#f00)"
              @update:model-value="adjust('h', $event)"
            />
            <AppPickerSlider
              :model-value="Math.round(hsv.s)"
              :max="100"
              :label="text('Nasycenie', 'Saturation')"
              unit="%"
              :gradient="`linear-gradient(to right,${hsvToHex({ ...hsv, s: 0 })},${hsvToHex({ ...hsv, s: 100 })})`"
              @update:model-value="adjust('s', $event)"
            />
            <AppPickerSlider
              :model-value="Math.round(hsv.v)"
              :max="100"
              :label="text('Jasność', 'Brightness')"
              unit="%"
              :gradient="`linear-gradient(to right,#000,${hsvToHex({ ...hsv, v: 100 })})`"
              @update:model-value="adjust('v', $event)"
            />
          </div>
          <div
            class="mt-3 grid grid-cols-5 gap-2"
            :aria-label="text('Paleta kolorów', 'Color palette')"
          >
            <AppButton
              v-for="color in palette"
              :key="color"
              size="sm"
              :aria-label="color"
              :aria-pressed="draft?.toLowerCase() === color"
              class="palette-color"
              @click="update(color)"
              ><span class="color-chip" :style="{ background: color }"></span
              ><span v-if="draft?.toLowerCase() === color" class="sr-only">{{
                text('Wybrany', 'Selected')
              }}</span></AppButton
            >
          </div>
          <div
            class="mt-3 flex justify-between gap-2 border-t border-line pt-3"
          >
            <AppButton
              size="sm"
              variant="ghost"
              @click="
                update(null);
                close();
              "
              >{{ text('Wyczyść', 'Clear') }}</AppButton
            ><AppButton size="sm" variant="soft" @click="close()">{{
              text('Gotowe', 'Done')
            }}</AppButton>
          </div>
        </template>
      </AppPopover></template
    >
    ></AppTextInput
  >
</template>
<style scoped>
.color-swatch.app-button {
  padding: 4px;
  width: 36px;
  min-height: 36px;
  height: 36px;
}
.color-chip {
  display: block;
  width: 22px;
  height: 22px;
  border: 1px solid var(--runa-control-border);
  border-radius: 2px;
}
.palette-color.app-button {
  padding: 6px;
}
.color-plane {
  height: 152px;
  position: relative;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  touch-action: none;
  cursor: crosshair;
  background-image:
    linear-gradient(to top, #000, transparent),
    linear-gradient(to right, #fff, transparent);
}
.plane-cursor {
  position: absolute;
  pointer-events: none;
  width: 14px;
  height: 14px;
  border: 2px solid #fff;
  box-shadow: 0 0 0 1px #000;
  border-radius: 50%;
  transform: translate(-50%, -50%);
}
@media (forced-colors: active) {
  .color-chip,
  .color-plane {
    forced-color-adjust: none;
  }
}
</style>
