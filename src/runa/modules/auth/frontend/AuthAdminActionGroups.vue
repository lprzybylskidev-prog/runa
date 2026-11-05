<script setup lang="ts" generic="T">
import { useAdminLocale } from '@/runa/admin';
import type { AdminText } from '@/runa/admin';
defineProps<{ groups: { key: string; title: AdminText; items: T[] }[] }>();
defineSlots<{ default(props: { item: T }): unknown }>();
const { t } = useAdminLocale();
</script>
<template>
  <div class="divide-y divide-line" data-auth-action-groups>
    <section
      v-for="group in groups"
      :key="group.key"
      class="py-3 first:pt-0 last:pb-0"
      :aria-label="t(group.title)"
      :data-action-group="group.key"
    >
      <h3 class="mb-2 text-xs font-semibold text-muted">
        {{ t(group.title) }}
      </h3>
      <div class="flex min-w-0 flex-wrap gap-2">
        <template v-for="(item, index) in group.items" :key="index">
          <slot :item="item" />
        </template>
      </div>
    </section>
  </div>
</template>
