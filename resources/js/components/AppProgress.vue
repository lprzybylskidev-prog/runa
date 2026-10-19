<script setup lang="ts">
import { computed } from 'vue';
import type { FeedbackTone } from '../runa/ui/feedback';
const props = withDefaults(
  defineProps<{
    label: string;
    value?: number | null;
    max?: number;
    valueText?: string;
    tone?: FeedbackTone;
  }>(),
  { max: 100, tone: 'neutral', value: null, valueText: '' },
);
const limit = computed(() =>
  Number.isFinite(props.max) && props.max > 0 ? props.max : 100,
);
const amount = computed(() =>
  typeof props.value === 'number' && Number.isFinite(props.value)
    ? Math.min(limit.value, Math.max(0, props.value))
    : undefined,
);
const ratio = computed(() =>
  amount.value === undefined ? 0 : amount.value / limit.value,
);
</script>
<template>
  <div class="runa-feedback app-progress" :data-tone="tone">
    <div class="mb-2 flex items-center justify-between gap-3 text-xs">
      <span>{{ label }}</span
      ><span v-if="amount !== undefined" aria-hidden="true" class="text-muted"
        >{{ Math.round(ratio * 100) }}%</span
      >
    </div>
    <div
      role="progressbar"
      :aria-label="label"
      :aria-valuenow="amount"
      aria-valuemin="0"
      :aria-valuemax="limit"
      :aria-valuetext="valueText || undefined"
      class="app-progress-track"
      :class="{ 'is-indeterminate': amount === undefined }"
    >
      <span
        :style="
          amount === undefined ? undefined : { transform: `scaleX(${ratio})` }
        "
      />
    </div>
  </div>
</template>
<style scoped>
.app-progress-track {
  height: 8px;
  overflow: hidden;
  border-radius: 4px;
  background: var(--runa-surface-hover);
}
.app-progress-track span {
  display: block;
  width: 100%;
  height: 100%;
  background: var(--feedback-text);
  transform-origin: left;
  transition: transform 180ms ease-out;
}
.app-progress[data-tone='neutral'] {
  --feedback-text: var(--runa-primary-solid);
}
.is-indeterminate span {
  width: 35%;
  animation: progress-travel 1200ms ease-in-out infinite alternate;
}
@keyframes progress-travel {
  to {
    transform: translateX(185%);
  }
}
@media (prefers-reduced-motion: reduce) {
  .app-progress-track span {
    transition: none;
    animation: none;
  }
}
</style>
