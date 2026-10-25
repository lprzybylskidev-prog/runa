<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import AppSkeleton from './AppSkeleton.vue';
const props = withDefaults(
  defineProps<{
    src?: string;
    initials: string;
    size?: number;
    loading?: boolean;
  }>(),
  { src: '', size: 40, loading: false },
);
const failed = ref(false);
const loaded = ref(false);
const waiting = computed(
  () => props.loading || (!!props.src && !failed.value && !loaded.value),
);
watch(
  () => props.src,
  () => {
    failed.value = false;
    loaded.value = false;
  },
  { flush: 'sync' },
);
</script>
<template>
  <span
    class="runa-avatar"
    :class="{ 'avatar-loading': waiting }"
    :style="{ width: `${size}px`, height: `${size}px` }"
    aria-hidden="true"
  >
    <img
      v-if="src && !failed"
      v-show="!waiting"
      :key="src"
      :src="src"
      alt=""
      @load="loaded = true"
      @error="failed = true"
    />
    <AppSkeleton v-if="waiting" shape="circle" class="avatar-skeleton" />
    <span v-else-if="!src || failed">{{ initials }}</span>
  </span>
</template>
<style scoped>
.runa-avatar {
  position: relative;
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
.runa-avatar.avatar-loading {
  background: transparent;
}
.runa-avatar .avatar-skeleton {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  border-radius: inherit;
}
.runa-avatar .avatar-skeleton :deep(span) {
  width: 100%;
  height: 100%;
  border-radius: inherit;
}
</style>
