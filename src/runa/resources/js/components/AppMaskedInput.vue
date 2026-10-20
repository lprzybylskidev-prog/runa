<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import AppTextInput from './AppTextInput.vue';
import { formatMask, parseMaskedInput } from '../lib/mask';
import type { MaskResult } from '../runa/ui/advancedForms';
defineOptions({ inheritAttrs: false });
const props = withDefaults(
  defineProps<{
    modelValue: string | null;
    pattern: string;
    label: string;
    helper?: string;
    warning?: string;
    error?: string | readonly string[];
    disabled?: boolean;
    readonly?: boolean;
    required?: boolean;
  }>(),
  {
    helper: '',
    warning: '',
    error: '',
    disabled: false,
    readonly: false,
    required: false,
  },
);
const emit = defineEmits<{
  'update:modelValue': [value: string | null];
  formatted: [value: string];
  completeness: [complete: boolean];
}>();
const root = ref<globalThis.HTMLElement>();
const state = computed(() => formatMask(props.modelValue, props.pattern));
const draft = ref(state.value.formatted);
let composing = false;
watch(state, (value) => {
  if (!composing) draft.value = value.formatted;
});
function blocked(input: globalThis.HTMLInputElement) {
  return props.disabled || props.readonly || input.matches(':disabled');
}
function commit(
  result: MaskResult,
  input: globalThis.HTMLInputElement,
  rawCaret: number,
) {
  draft.value = result.formatted;
  input.value = result.formatted;
  emit('update:modelValue', result.raw);
  emit('formatted', result.formatted);
  emit('completeness', result.complete);
  const caret =
    result.boundaries[Math.min(rawCaret, result.boundaries.length - 1)] ?? 0;
  void nextTick(() => {
    if (document.activeElement === input) input.setSelectionRange(caret, caret);
  });
}
function input(event: Event) {
  const target = event.target;
  if (!(target instanceof globalThis.HTMLInputElement) || blocked(target))
    return;
  if (
    composing ||
    (event instanceof globalThis.InputEvent && event.isComposing)
  )
    return;
  const caret = target.selectionStart ?? target.value.length;
  const before = parseMaskedInput(target.value.slice(0, caret), props.pattern);
  commit(
    formatMask(target.value, props.pattern),
    target,
    Array.from(before.raw ?? '').length,
  );
}
function beforeInput(event: globalThis.InputEvent) {
  const target = event.target;
  if (
    !(target instanceof globalThis.HTMLInputElement) ||
    blocked(target) ||
    composing ||
    event.isComposing
  )
    return;
  if (
    !['deleteContentBackward', 'deleteContentForward'].includes(event.inputType)
  )
    return;
  const start = target.selectionStart ?? 0;
  const end = target.selectionEnd ?? start;
  if (start !== end) return;
  const current = parseMaskedInput(target.value, props.pattern);
  const boundaries = current.boundaries;
  const index = boundaries.filter((position) => position <= start).length - 1;
  const remove =
    event.inputType === 'deleteContentBackward' ? index - 1 : index;
  const raw = Array.from(current.raw ?? '');
  if (remove < 0 || remove >= raw.length) return;
  event.preventDefault();
  raw.splice(remove, 1);
  commit(formatMask(raw.join(''), props.pattern), target, remove);
}
function end(event: Event) {
  composing = false;
  input(event);
}
defineExpose({ focus: () => root.value?.querySelector('input')?.focus() });
</script>
<template>
  <div ref="root">
    <AppTextInput
      v-bind="$attrs"
      :model-value="draft"
      :label="label"
      :helper="helper"
      :warning="warning"
      :error="error"
      :disabled="disabled"
      :readonly="readonly"
      :required="required"
      @input="input"
      @beforeinput="beforeInput"
      @compositionstart="composing = true"
      @compositionend="end"
    />
  </div>
</template>
