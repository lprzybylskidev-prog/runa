<script setup lang="ts">
import { computed } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type { AdminWidgetData } from '@/runa/admin';
import { Card } from '@/runa/ui';
defineProps<{ widget: AdminWidgetData }>();
const { t, locale } = useAdminLocale();
const copy = computed(() =>
  locale.value === 'pl'
    ? { label: 'Etykieta', unavailable: 'Odpowiedź API jest niedostępna.' }
    : { label: 'Label', unavailable: 'The API response is unavailable.' },
);
</script>

<template>
  <Card>
    <template #header
      ><h2>{{ t(widget.title) }}</h2></template
    >
    <p v-if="widget.diagnostic" class="text-sm text-muted">
      {{ copy.unavailable }}
    </p>
    <dl v-else class="min-w-0 space-y-1">
      <dt class="text-xs text-muted">{{ copy.label }}</dt>
      <dd class="break-words text-ink">{{ widget.values.message }}</dd>
    </dl>
  </Card>
</template>
