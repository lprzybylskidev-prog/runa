<script setup lang="ts">
import { computed } from 'vue';
const props = defineProps<{ variant: 'one' | 'two' | 'three' }>();
const patterns = {
  one: {
    points: [
      [22, 24],
      [66, 53],
      [89, 95],
      [139, 121],
      [185, 78],
      [162, 164],
      [116, 40],
    ],
    path: 'M22 24L66 53L89 95L139 121L185 78 M139 121L162 164 M66 53L116 40',
  },
  two: {
    points: [
      [18, 137],
      [59, 88],
      [105, 112],
      [157, 73],
      [194, 131],
      [170, 23],
    ],
    path: 'M18 137L59 88L105 112L157 73L194 131L105 112 M157 73L170 23',
  },
  three: {
    points: [
      [25, 39],
      [72, 22],
      [111, 61],
      [94, 108],
      [140, 151],
      [192, 129],
      [165, 74],
      [43, 130],
    ],
    path: 'M25 39L72 22L111 61L94 108L140 151L192 129 M111 61L165 74 M94 108L43 130',
  },
} as const;
const pattern = computed(() => patterns[props.variant]);
</script>

<template>
  <svg
    class="app-constellation"
    viewBox="0 0 220 180"
    fill="none"
    aria-hidden="true"
    focusable="false"
  >
    <path
      :d="pattern.path"
      stroke="currentColor"
      stroke-width=".6"
      vector-effect="non-scaling-stroke"
      class="constellation-links"
    />
    <g
      v-for="([x, y], index) in pattern.points"
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
.constellation-links {
  stroke-opacity: var(--runa-constellation-line-opacity);
}
</style>
