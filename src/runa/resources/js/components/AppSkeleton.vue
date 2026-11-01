<script setup lang="ts">
import { computed } from 'vue';
const props = withDefaults(
  defineProps<{ lines?: number; shape?: 'text' | 'block' | 'circle' }>(),
  { lines: 3, shape: 'text' },
);
const count = computed(() =>
  props.shape === 'text' && Number.isFinite(props.lines)
    ? Math.max(1, Math.min(10, Math.floor(props.lines)))
    : 1,
);
</script>
<template>
  <div aria-hidden="true" class="app-skeleton" :data-shape="shape">
    <span v-for="line in count" :key="line" class="runa-content-pulse" />
  </div>
</template>
<style scoped>
.app-skeleton {
  display: grid;
  gap: 12px;
}
.app-skeleton span {
  display: block;
  height: 12px;
  border-radius: 4px;
  background: var(--runa-surface-hover);
}
.app-skeleton[data-shape='text'] span:last-child {
  width: 65%;
}
.app-skeleton[data-shape='block'] span {
  height: 112px;
}
.app-skeleton[data-shape='circle'] span {
  width: 44px;
  height: 44px;
  border-radius: 50%;
}
</style>
<style src="../../css/content-loading.css"></style>
