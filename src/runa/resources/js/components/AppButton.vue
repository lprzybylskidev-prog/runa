<script setup lang="ts">
import { computed, inject } from 'vue';
import { formActivity } from '../composables/formActivity';
import { buttonIntentTones } from '../runa/ui/controls';
import AppSpinner from './AppSpinner.vue';
import AppIcon from './AppIcon.vue';
import type { IconName } from '../runa/ui/icons';
import type {
  ButtonTone,
  ButtonIntent,
  ControlSize,
  ButtonVariant,
} from '../runa/ui/controls';
const props = withDefaults(
  defineProps<{
    type?: 'button' | 'submit' | 'reset';
    disabled?: boolean;
    busy?: boolean | undefined;
    size?: ControlSize;
    variant?: ButtonVariant | undefined;
    tone?: ButtonTone | undefined;
    intent?: ButtonIntent | undefined;
    iconOnly?: boolean;
    icon?: IconName | undefined;
  }>(),
  {
    tone: undefined,
    intent: undefined,
    variant: undefined,
    type: 'button',
    disabled: false,
    busy: undefined,
    size: 'md',
    iconOnly: false,
    icon: undefined,
  },
);
const formBusy = inject(formActivity, undefined);
const busy = computed(
  () => props.busy ?? (props.type === 'submit' && formBusy?.value === true),
);
// Explicit intent wins over a low-level presentation tone. No inherited alert tone.
const tone = computed(() =>
  props.intent ? buttonIntentTones[props.intent] : (props.tone ?? 'neutral'),
);
const iconSize = computed(() => ({ sm: 18, md: 20, lg: 22 })[props.size]);
const variant = computed(
  () => props.variant ?? (tone.value === 'neutral' ? 'neutral' : 'outline'),
);
</script>

<template>
  <button
    :type="type"
    :disabled="disabled || busy"
    :aria-busy="busy || undefined"
    :data-tone="tone"
    :data-intent="intent"
    :data-variant="variant"
    :data-size="size"
    class="app-button"
    :class="{ 'app-button-compact': iconOnly }"
  >
    <AppSpinner
      v-if="busy"
      class="button-spinner"
      label=""
      size="sm"
      decorative
    />
    <AppIcon
      v-else-if="icon"
      :name="icon"
      :size="iconSize"
      class="button-icon"
    />
    <slot v-if="!busy || !iconOnly" />
  </button>
</template>

<style scoped src="../../css/button.css"></style>
