<script setup lang="ts">
import { computed } from 'vue';
import { useImageReadiness } from '../composables/useImageReadiness';
import type { ImageReadiness } from '../composables/useImageReadiness';
import AppSkeleton from './AppSkeleton.vue';
const props = withDefaults(
  defineProps<{
    src?: string;
    initials: string;
    size?: number;
    loading?: boolean;
    image?: ImageReadiness | undefined;
  }>(),
  { src: '', size: 40, loading: false, image: undefined },
);
// A supplied image owner lets shell/dropdown consumers share download and decode.
const local = useImageReadiness(() => (props.image ? '' : props.src));
const image = computed(() => props.image ?? local);
const waiting = computed(
  () => props.loading || (!image.value.src.value && !image.value.settled.value),
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
      v-if="image.src.value"
      v-show="!waiting"
      :key="image.src.value"
      :src="image.src.value"
      alt=""
    />
    <AppSkeleton v-if="waiting" shape="circle" class="avatar-skeleton" />
    <span v-else-if="!image.src.value">{{ initials }}</span>
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
