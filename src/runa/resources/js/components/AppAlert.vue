<script setup lang="ts">
import { useFeedbackTone } from '../composables/useFeedbackTone';
import AppButton from './AppButton.vue';
import type { FeedbackTone } from '../runa/ui/feedback';
const props = withDefaults(
  defineProps<{
    tone?: FeedbackTone | undefined;
    title?: string;
    alignment?: 'start' | 'center';
    dismissible?: boolean;
    closeLabel?: string;
    announce?: boolean;
  }>(),
  {
    tone: undefined,
    title: '',
    alignment: 'start',
    dismissible: false,
    closeLabel: 'Close',
    announce: true,
  },
);
defineEmits<{ close: [] }>();
const symbols = {
  neutral: 'i',
  success: '✓',
  info: 'i',
  warning: '!',
  danger: '!',
};
const tone = useFeedbackTone(() => props.tone, 'info');
</script>
<template>
  <div
    class="runa-feedback app-alert"
    :data-tone="tone"
    :data-alignment="alignment"
    :role="announce ? (tone === 'danger' ? 'alert' : 'status') : undefined"
    :aria-atomic="announce || undefined"
  >
    <span class="alert-symbol" aria-hidden="true">{{ symbols[tone] }}</span>
    <div class="alert-content">
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div v-if="$slots.default"><slot /></div>
      <div
        v-if="$slots.actions"
        class="alert-actions mt-3 flex flex-wrap gap-2"
      >
        <slot name="actions" />
      </div>
    </div>
    <AppButton
      v-if="dismissible"
      icon-only
      size="sm"
      :variant="tone === 'neutral' ? 'neutral' : 'ghost'"
      :tone="tone === 'neutral' ? 'secondary' : tone"
      :aria-label="closeLabel"
      @click="$emit('close')"
      >×</AppButton
    >
  </div>
</template>
<style scoped>
.app-alert {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 16px;
  border: 1px solid var(--feedback-text);
  border-left-width: 3px;
  border-radius: 4px;
  background: var(--feedback-subtle);
  color: var(--feedback-text);
  font-size: 12px;
  line-height: 1.7;
}
.app-alert[data-alignment='center'] {
  align-items: center;
}
.app-alert[data-alignment='center'] .alert-content {
  text-align: center;
}
.app-alert[data-alignment='center'] .alert-actions {
  justify-content: center;
}
.app-alert[data-tone='neutral'] {
  --feedback-text: var(--runa-text-secondary);
  --feedback-subtle: var(--runa-surface-raised);
  border-color: var(--runa-control-border);
  border-left-width: 1px;
}
.alert-symbol {
  align-self: center;
  display: inline-flex;
  width: 20px;
  height: 20px;
  align-items: center;
  justify-content: center;
  border: 1px solid currentColor;
  border-radius: 50%;
  font-weight: 600;
  flex-shrink: 0;
}
.alert-content {
  flex: 1;
  min-width: 0;
  overflow-wrap: anywhere;
}
.alert-content > p + div {
  margin-top: 4px;
}
</style>
