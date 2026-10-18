<script setup lang="ts">
import AppButton from './AppButton.vue';
import type { FeedbackTone } from '../runa/ui/feedback';
withDefaults(
  defineProps<{
    tone?: FeedbackTone;
    title?: string;
    dismissible?: boolean;
    closeLabel?: string;
    announce?: boolean;
  }>(),
  {
    tone: 'info',
    title: '',
    dismissible: false,
    closeLabel: 'Close',
    announce: true,
  },
);
defineEmits<{ close: [] }>();
const symbols = { success: '✓', info: 'i', warning: '!', danger: '!' };
</script>
<template>
  <div
    class="runa-feedback app-alert"
    :data-tone="tone"
    :role="announce ? (tone === 'danger' ? 'alert' : 'status') : undefined"
    :aria-atomic="announce || undefined"
  >
    <span class="alert-symbol" aria-hidden="true">{{ symbols[tone] }}</span>
    <div class="alert-content">
      <p v-if="title" class="font-semibold">{{ title }}</p>
      <div><slot /></div>
      <div v-if="$slots.actions" class="mt-3 flex flex-wrap gap-2">
        <slot name="actions" />
      </div>
    </div>
    <AppButton
      v-if="dismissible"
      icon-only
      size="sm"
      variant="ghost"
      :tone="tone"
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
.alert-symbol {
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
