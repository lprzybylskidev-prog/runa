<script setup lang="ts">
import { useContentMotion } from '../composables/useContentMotion';
import { nextTick, ref, watch } from 'vue';
import AppButton from './AppButton.vue';
import AppProgress from './AppProgress.vue';
import type { WizardStep } from '../runa/ui/advancedForms';
const props = withDefaults(
  defineProps<{
    steps: readonly WizardStep[];
    modelValue: number;
    label: string;
    busy?: boolean;
    invalid?: boolean;
    progress?: boolean;
    locale?: 'pl' | 'en';
  }>(),
  { busy: false, invalid: false, progress: true, locale: 'pl' },
);
const emit = defineEmits<{
  'update:modelValue': [index: number];
  next: [];
  previous: [];
  finish: [];
}>();
const root = ref<globalThis.HTMLElement>();
const heading = ref<globalThis.HTMLElement>();
const text = (pl: string, en: string) => (props.locale === 'en' ? en : pl);
function focusInvalid() {
  (
    root.value?.querySelector<globalThis.HTMLElement>('[aria-invalid=true]') ??
    heading.value
  )?.focus();
}
watch(
  () => props.invalid,
  async (invalid) => {
    if (invalid) {
      await nextTick();
      focusInvalid();
    }
  },
);
watch(
  () => props.modelValue,
  async () => {
    await nextTick();
    if (props.invalid) focusInvalid();
    else heading.value?.focus();
  },
);
defineExpose({ focusInvalid });
const content = ref<globalThis.HTMLElement>();
useContentMotion(content, () => props.modelValue);
</script>
<template>
  <section
    ref="root"
    :aria-label="label"
    :aria-busy="busy"
    class="min-w-0 space-y-5"
  >
    <ol :aria-label="text('Kroki', 'Steps')" class="flex flex-wrap gap-2">
      <li v-for="(step, index) in steps" :key="step.key">
        <AppButton
          :disabled="busy"
          :aria-current="index === modelValue ? 'step' : undefined"
          :variant="index === modelValue ? 'soft' : 'ghost'"
          :tone="index === modelValue ? 'primary' : 'secondary'"
          @click="emit('update:modelValue', index)"
          >{{ index + 1 }}. {{ step.label }}</AppButton
        >
      </li>
    </ol>
    <AppProgress
      v-if="progress"
      :value="modelValue + 1"
      :max="steps.length"
      :label="text('Postęp formularza', 'Form progress')"
    />
    <h3
      ref="heading"
      tabindex="-1"
      class="text-sm font-semibold focus-visible:outline-2 focus-visible:outline-[var(--runa-focus-ring)]"
    >
      {{ steps[modelValue]?.label }}
    </h3>
    <p v-if="steps[modelValue]?.description" class="text-xs text-muted">
      {{ steps[modelValue]?.description }}
    </p>
    <fieldset ref="content" :disabled="busy" class="min-w-0">
      <slot :step="steps[modelValue]" :index="modelValue" />
    </fieldset>
    <slot name="feedback" />
    <div class="flex flex-wrap gap-3 border-t border-line pt-5">
      <AppButton
        :disabled="busy || modelValue <= 0"
        @click="emit('previous')"
        >{{ text('Wstecz', 'Back') }}</AppButton
      >
      <AppButton
        v-if="modelValue < steps.length - 1"
        :busy="busy"
        @click="emit('next')"
        >{{ text('Dalej', 'Next') }}</AppButton
      >
      <AppButton v-else :busy="busy" variant="solid" @click="emit('finish')">{{
        text('Zakończ', 'Finish')
      }}</AppButton>
      <slot name="actions" />
    </div>
  </section>
</template>
