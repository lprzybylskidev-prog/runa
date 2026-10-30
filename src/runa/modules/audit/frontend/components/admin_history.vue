<script setup lang="ts">
import { reactive } from 'vue';
import { Button, TextInput } from '@/runa/ui';
import { AdminRecordBrowser } from '@/runa/admin';
import type { AdminPageProps, AdminTableController } from '@/runa/admin';
defineProps<AdminPageProps>();
const labels: Record<string, string> = {
  module: 'Module',
  action: 'Action',
  actor_public_id: 'Actor UUID',
  actor_kind: 'Actor kind',
  outcome: 'Outcome',
  correlation_id: 'Correlation',
  entity_module: 'Entity module',
  entity_type: 'Entity type',
  entity_public_id: 'Entity UUID',
  from: 'From (UTC ISO-8601)',
  until: 'Until (UTC ISO-8601)',
};
const query = new URLSearchParams(window.location.search);
const filters = reactive<Record<string, string | null>>(
  Object.fromEntries(Object.keys(labels).map((key) => [key, query.get(key)])),
);
const initialFilters = Object.fromEntries(
  Object.entries(filters)
    .filter(([, value]) => value?.trim())
    .map(([key, value]) => [key, value!.trim()]),
);
function apply(table: AdminTableController) {
  table.query.value = {
    ...table.query.value,
    page: 1,
    filters: Object.fromEntries(
      Object.entries(filters)
        .filter(([, value]) => value?.trim())
        .map(([key, value]) => [key, value!.trim()]),
    ),
  };
}
</script>
<template>
  <AdminRecordBrowser
    :controller="controller"
    :form="form"
    :components="components"
    :initial-filters="initialFilters"
  >
    <template #toolbar="{ table }">
      <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <TextInput
          v-for="(label, key) in labels"
          :key="key"
          v-model="filters[key]"
          :label="label"
          :maxlength="128"
          @keydown.enter.prevent="apply(table)"
        />
      </div>
      <Button class="mb-4" @click="apply(table)">Filter history</Button>
    </template>
  </AdminRecordBrowser>
</template>
