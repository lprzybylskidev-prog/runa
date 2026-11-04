<script setup lang="ts">
import { computed, inject } from 'vue';
import { formActivity } from '../composables/formActivity';
import { useInheritedFeedbackTone } from '../composables/useFeedbackTone';
import AppSpinner from './AppSpinner.vue';
import type {
  ControlTone,
  ControlSize,
  ButtonVariant,
} from '../runa/ui/controls';
const props = withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset';
    compact?: boolean;
    disabled?: boolean;
    busy?: boolean | undefined;
    size?: ControlSize;
    variant?: ButtonVariant | undefined;
    tone?: ControlTone | undefined;
    iconOnly?: boolean;
  }>(),
  {
    tone: undefined,
    variant: undefined,
    type: 'button',
    compact: false,
    disabled: false,
    busy: undefined,
    size: 'md',
    iconOnly: false,
  },
);
const formBusy = inject(formActivity, undefined);
const busy = computed(
  () => props.busy ?? (props.type === 'submit' && formBusy?.value === true),
);
const contextTone = useInheritedFeedbackTone();
const tone = computed(
  () =>
    props.tone ??
    (contextTone.value === 'neutral' ? 'primary' : contextTone.value),
);
const variant = computed(
  () =>
    props.variant ?? (contextTone.value === 'neutral' ? 'neutral' : 'outline'),
);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || busy"
    :aria-busy="busy || undefined"
    :data-tone="tone"
    :data-variant="variant"
    :data-size="size"
    class="app-button"
    :class="{ 'app-button-compact': compact || iconOnly }"
  >
    <AppSpinner
      v-if="busy"
      class="button-spinner"
      label=""
      size="sm"
      decorative
    />
    <slot v-if="!busy || !(compact || iconOnly)" />
  </button>
</template>

<style scoped src="../../css/button.css"></style>
