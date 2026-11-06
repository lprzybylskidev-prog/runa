<script setup lang="ts">
import { AdminRecordBrowser } from '@/runa/admin';
import type { AdminPageProps } from '@/runa/admin';
defineProps<AdminPageProps>();
const query = new URLSearchParams(window.location.search);
const keys = [
  'module',
  'action',
  'actor_public_id',
  'actor_kind',
  'outcome',
  'correlation_id',
  'entity_module',
  'entity_type',
  'entity_public_id',
  'from',
  'until',
];
const initialFilters = Object.fromEntries(
  keys.flatMap((key) => {
    const value = query.get(key)?.trim();
    return value ? [[key, value]] : [];
  }),
);
</script>
<template>
  <AdminRecordBrowser
    :controller="controller"
    :form="form"
    :components="components"
    :initial-filters="initialFilters"
  />
</template>
