<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { t: tr } = useAdminLocale();
import { reactive } from 'vue';
import { Button, TextInput } from '@/runa/ui';
import { AdminRecordBrowser } from '@/runa/admin';
import type { AdminPageProps, AdminTableController } from '@/runa/admin';
defineProps<AdminPageProps>();
const labels: Record<string, string> = {
  module: tr('Module'),
  action: tr('Action'),
  actor_public_id: tr('Actor UUID'),
  actor_kind: tr('Actor kind'),
  outcome: tr('Outcome'),
  correlation_id: tr('Correlation'),
  entity_module: tr('Entity module'),
  entity_type: tr('Entity type'),
  entity_public_id: tr('Entity UUID'),
  from: tr('From (UTC ISO-8601)'),
  until: tr('Until (UTC ISO-8601)'),
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
          :model-value="filters[key] ?? null"
          @update:model-value="filters[key] = $event"
          :label="label"
          :maxlength="128"
          @keydown.enter.prevent="apply(table)"
        />
      </div>
      <Button class="mb-4" @click="apply(table)">{{
        tr('Filter history')
      }}</Button>
    </template>
  </AdminRecordBrowser>
</template>
