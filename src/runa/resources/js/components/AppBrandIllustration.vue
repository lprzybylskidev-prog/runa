<script setup lang="ts">
import { ref } from 'vue';
import { useDecorativeMotion } from '../composables/useDecorativeMotion';
import RunaLogo from './RunaLogo.vue';
const props = withDefaults(defineProps<{ animated?: boolean }>(), {
  animated: false,
});
const illustration = ref<globalThis.HTMLElement>();
useDecorativeMotion(
  () => props.animated,
  () => {
    return [
      ...(illustration.value?.querySelectorAll<globalThis.HTMLElement>(
        '.art-orbit-point',
      ) ?? []),
    ].map((point) => {
      const start = point.classList.contains('orbit-point-two')
        ? -Math.PI / 3
        : Math.PI;
      const frames = Array.from({ length: 73 }, (_, step) => {
        const progress = step / 72;
        const angle = start + progress * Math.PI * 2;
        return {
          left: `${50 + 50 * Math.cos(angle)}%`,
          top: `${50 + 50 * Math.sin(angle)}%`,
          opacity: 0.7 + 0.25 * Math.sin(progress * Math.PI * 3) ** 2,
        };
      });
      return point.animate(frames, { duration: 6000, easing: 'ease-in-out' });
    });
  },
  6000,
  [8000, 14000],
);
</script>
<template>
  <div ref="illustration" class="runa-art" aria-hidden="true">
    <span class="art-grid" />
    <div class="art-composition">
      <span
        v-for="orbit in ['one', 'two', 'three']"
        :key="orbit"
        class="art-orbit"
        :class="`orbit-${orbit}`"
      >
        <span class="art-orbit-point orbit-point-one" />
        <span class="art-orbit-point orbit-point-two" />
      </span>
      <span class="art-core"><RunaLogo /></span>
    </div>
    <span class="art-coordinate">RUNA</span><span class="art-plus">+</span>
  </div>
</template>
