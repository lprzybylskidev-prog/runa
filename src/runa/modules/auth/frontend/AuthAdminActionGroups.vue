<script setup lang="ts" generic="T">
import { useAdminLocale } from '@/runa/admin';
import type { AdminText } from '@/runa/admin';
defineProps<{ groups: { key: string; title: AdminText; items: T[] }[] }>();
defineSlots<{ default(props: { item: T }): unknown }>();
const { t } = useAdminLocale();
</script>
<template>
  <div
    class="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2"
    data-auth-action-groups
  >
    <div
      v-for="group in groups"
      :key="group.key"
      class="flex min-w-0 max-w-full flex-wrap items-center gap-2 border-s border-line ps-3 first:border-s-0 first:ps-0"
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
