<script setup lang="ts">
import { ref, watch } from 'vue';
const props = withDefaults(
  defineProps<{ src?: string; initials: string; size?: number }>(),
  { src: '', size: 40 },
);
const failed = ref(false);
watch(
  () => props.src,
  () => {
    failed.value = false;
  },
);
</script>
<template>
  <span
    class="runa-avatar"
    :style="{ width: `${size}px`, height: `${size}px` }"
    aria-hidden="true"
  >
    <img
      v-if="src && !failed"
      :key="src"
      :src="src"
      alt=""
      @error="failed = true"
    />
    <span v-else>{{ initials }}</span>
  </span>
</template>
<style scoped>
.runa-avatar {
  display: inline-grid;
  place-items: center;
  flex-shrink: 0;
  overflow: hidden;
  border-radius: 50%;
  background: var(--runa-primary-subtle);
  color: var(--runa-primary-text);
  font-size: inherit;
  font-weight: 600;
}
.runa-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
