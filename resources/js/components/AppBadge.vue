<script setup lang="ts">
import type { BadgeTone } from '../runa/ui/feedback';
withDefaults(defineProps<{ tone?: BadgeTone; size?: 'sm' | 'md' }>(), {
  tone: 'neutral',
  size: 'md',
});
</script>
<template>
  <span class="runa-feedback app-badge" :data-tone="tone" :data-size="size"
    ><slot
  /></span>
</template>
<style scoped>
.app-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  max-width: 100%;
  padding: 3px 8px;
  border: 1px solid var(--feedback-text);
  border-radius: 4px;
  color: var(--feedback-text);
  background: var(--feedback-subtle);
  font-size: 11px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}
.app-badge[data-size='sm'] {
  padding: 1px 6px;
  font-size: 10px;
}
</style>
