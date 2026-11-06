<script setup lang="ts">
import { AdminResourceTable } from '@/runa/admin';
import type { AdminResourceTableProps } from '@/runa/admin';
import { TextInput } from '@/runa/ui';
defineProps<AdminResourceTableProps>();
</script>
<template>
  <AdminResourceTable :controller="controller">
    <template #filters="{ query, update }">
      <TextInput
        label="Name filter"
        :model-value="query.filters.name ?? ''"
        @update:model-value="
          update({ ...query, filters: { name: $event ?? '' } })
        "
      />
    </template>
    <template #cell-name="{ value }"
      ><strong>Customer: {{ value }}</strong></template
    >
    <template #actions="{ row }"
      ><span :data-public-id="row.public_id">Details</span></template
    >
  </AdminResourceTable>
</template>
