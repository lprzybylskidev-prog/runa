<script setup lang="ts">
import Badge from './AppBadge.vue';
import type { BadgeTone } from '../runa/ui/feedback';
withDefaults(
  defineProps<{
    value: boolean | null;
    locale?: 'pl' | 'en';
    tone?: BadgeTone | undefined;
    size?: 'sm' | 'md';
  }>(),
  { locale: 'pl', size: 'sm', tone: undefined },
);
</script>
<template>
  <Badge
    v-if="value !== null"
    :tone="tone ?? (value ? 'success' : 'neutral')"
    :size="size"
    >{{
      value ? (locale === 'pl' ? 'Tak' : 'Yes') : locale === 'pl' ? 'Nie' : 'No'
    }}</Badge
  >
  <span v-else>—</span>
</template>
