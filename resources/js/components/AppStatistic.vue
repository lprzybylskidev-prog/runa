<script setup lang="ts">
import AppIcon from './AppIcon.vue';
import type { IconName } from '../runa/ui/icons';
import type { FeedbackTone } from '../runa/ui/feedback';
withDefaults(
  defineProps<{
    label: string;
    value: string | number;
    description?: string;
    icon?: IconName | undefined;
    trend?: string;
    tone?: FeedbackTone;
  }>(),
  { tone: 'neutral', description: '', icon: undefined, trend: '' },
);
</script>
<template>
  <div class="runa-feedback app-statistic" :data-tone="tone">
    <dl>
      <dt class="flex items-start justify-between gap-3 text-xs text-muted">
        <slot name="label">{{ label }}</slot
        ><span v-if="icon || $slots.icon" class="app-statistic-icon"
          ><slot name="icon"
            ><AppIcon v-if="icon" :name="icon" :size="20" /></slot
        ></span>
      </dt>
      <dd class="mt-4 break-words text-3xl font-semibold tracking-tight">
        <slot name="value">{{ value }}</slot>
      </dd>
    </dl>
    <p v-if="description" class="mt-2 text-xs leading-relaxed text-muted">
      {{ description }}
    </p>
    <div v-if="trend || $slots.trend" class="app-statistic-trend mt-3 text-xs">
      <slot name="trend">{{ trend }}</slot>
    </div>
    <div v-if="$slots.visualization" class="mt-4">
      <slot name="visualization" />
    </div>
  </div>
</template>
<style scoped>
.app-statistic {
  min-width: 0;
  padding: 20px;
  border: 1px solid var(--runa-border);
  border-radius: 4px;
  background: var(--runa-surface-raised);
}
.app-statistic-icon,
.app-statistic-trend {
  color: var(--feedback-text);
}
.app-statistic[data-tone='neutral'] {
  --feedback-text: var(--runa-text-secondary);
}
.app-statistic:not([data-tone='neutral']) {
  border-top: 2px solid var(--feedback-text);
  padding-top: 19px;
}
@media (forced-colors: active) {
  .app-statistic {
    border-color: CanvasText;
  }
}
</style>
