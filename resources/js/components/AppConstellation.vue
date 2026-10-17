<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ variant: 'one' | 'two' | 'three' }>();
const patterns = {
  one: [
    [24, 22],
    [72, 48],
    [94, 99],
    [153, 128],
    [184, 82],
  ],
  two: [
    [20, 136],
    [61, 99],
    [105, 119],
    [143, 68],
    [192, 34],
  ],
  three: [
    [37, 28],
    [81, 75],
    [68, 126],
    [135, 145],
    [177, 103],
  ],
} as const;
const points = computed(() => patterns[props.variant]);
const line = computed(() =>
  points.value.map(([x, y]) => `${x},${y}`).join(' '),
);
</script>

<template>
  <svg
    class="app-constellation"
    viewBox="0 0 220 180"
    fill="none"
    aria-hidden="true"
    focusable="false"
  >
    <polyline
      :points="line"
      stroke="currentColor"
      stroke-width=".6"
      stroke-opacity=".2"
    />
    <g
      v-for="([x, y], index) in points"
      :key="index"
      :transform="`translate(${x} ${y})`"
    >
      <circle r="8" fill="currentColor" opacity=".025" />
      <circle r="4" fill="currentColor" opacity=".07" />
      <circle r="1.3" fill="currentColor" opacity=".55" />
    </g>
  </svg>
</template>

<style scoped>
.app-constellation {
  position: absolute;
  width: 180px;
  color: var(--runa-primary-text);
  opacity: 0.65;
}
</style>
