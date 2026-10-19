<script setup lang="ts">
import { useFeedbackTone } from '../composables/useFeedbackTone';
import type { BadgeTone } from '../runa/ui/feedback';
const props = withDefaults(
  defineProps<{ tone?: BadgeTone | undefined; announce?: boolean }>(),
  {
    tone: undefined,
    announce: false,
  },
);
const tone = useFeedbackTone(() => props.tone);
</script>
<template>
  <span
    class="runa-feedback app-status"
    :data-tone="tone"
    :role="announce ? 'status' : undefined"
    ><span aria-hidden="true" class="status-dot" /><slot
  /></span>
</template>
<style scoped>
.app-status {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: var(--feedback-text);
  font-size: 12px;
}
.status-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: currentColor;
  flex-shrink: 0;
}
</style>
