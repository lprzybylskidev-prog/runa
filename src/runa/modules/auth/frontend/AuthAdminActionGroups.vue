<script setup lang="ts" generic="T">
import { onUpdated, ref, watch } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type { AdminText } from '@/runa/admin';
defineProps<{ groups: { key: string; title: AdminText; items: T[] }[] }>();
defineSlots<{ default(props: { item: T }): unknown }>();
const { t } = useAdminLocale();
const root = ref<HTMLElement>();
let observeGroups = () => {};
watch(
  root,
  (element, _previous, cleanup) => {
    if (!element) return;
    function measure() {
      if (!element) return;
      const bounds = element.getBoundingClientRect();
      const rtl = getComputedStyle(element).direction === 'rtl';
      for (const group of element.children) {
        const box = group.getBoundingClientRect();
        group.toggleAttribute(
          'data-action-separated',
          Math.abs(rtl ? bounds.right - box.right : box.left - bounds.left) > 1,
        );
      }
    }
    const resize = new ResizeObserver(measure);
    observeGroups = () => {
      resize.disconnect();
      resize.observe(element);
      for (const group of element.children) resize.observe(group);
      measure();
    };
    observeGroups();
    cleanup(() => {
      resize.disconnect();
      observeGroups = () => {};
    });
  },
  { flush: 'post' },
);
onUpdated(() => observeGroups());
</script>
<template>
  <div
    ref="root"
    class="flex min-w-0 flex-wrap items-center gap-x-6 gap-y-2"
    data-auth-action-groups
  >
    <div
      v-for="group in groups"
      :key="group.key"
      class="relative flex min-w-0 max-w-full flex-wrap items-center gap-2 before:invisible before:absolute before:inset-y-0 before:-start-3 before:border-s before:border-line data-[action-separated]:before:visible"
      role="group"
      :aria-label="t(group.title)"
      :data-action-group="group.key"
    >
      <template v-for="(item, index) in group.items" :key="index">
        <slot :item="item" />
      </template>
    </div>
  </div>
</template>
