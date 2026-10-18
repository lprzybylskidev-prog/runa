<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { ControlSize } from '../runa/ui/controls';
const props = withDefaults(
  defineProps<{
    name: string;
    initials?: string | undefined;
    src?: string | undefined;
    size?: ControlSize;
    shape?: 'square' | 'circle';
    decorative?: boolean;
  }>(),
  {
    initials: undefined,
    src: undefined,
    size: 'md',
    shape: 'square',
    decorative: false,
  },
);
const failed = ref(false);
watch(
  () => props.src,
  () => {
    failed.value = false;
  },
);
const fallback = computed(
  () =>
    props.initials?.trim().slice(0, 3) ||
    props.name
      .trim()
      .split(/\s+/u)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => Array.from(part)[0])
      .join('')
      .toLocaleUpperCase() ||
    '?',
);
const safeSource = computed(() =>
  props.src && (/^\/(?!\/)/u.test(props.src) || /^https:\/\//u.test(props.src))
    ? props.src
    : undefined,
);
</script>
<template>
  <span
    class="app-avatar"
    :data-size="size"
    :data-shape="shape"
    :role="decorative ? undefined : 'img'"
    :aria-label="decorative ? undefined : name.trim() || fallback"
    :aria-hidden="decorative || undefined"
  >
    <img
      v-if="safeSource && !failed"
      :src="safeSource"
      alt=""
      @error="failed = true"
    />
    <span v-else>{{ fallback }}</span>
  </span>
</template>
<style scoped>
.app-avatar {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border: 1px solid var(--runa-control-border);
  border-radius: 4px;
  color: var(--runa-primary-text);
  background: var(--runa-primary-subtle);
  font-size: 12px;
  font-weight: 600;
  overflow: hidden;
}
.app-avatar[data-size='sm'] {
  width: 32px;
  height: 32px;
  font-size: 10px;
}
.app-avatar[data-size='lg'] {
  width: 56px;
  height: 56px;
  font-size: 16px;
}
.app-avatar[data-shape='circle'] {
  border-radius: 50%;
}
.app-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
</style>
