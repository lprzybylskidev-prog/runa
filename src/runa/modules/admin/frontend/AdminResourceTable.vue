<script setup lang="ts">
import { Table, Button, Alert } from '@/runa/ui';
import type {
  AdminRecord,
  AdminResourceTableProps,
  AdminResourceTableSlots,
} from './contracts';
defineProps<AdminResourceTableProps>();
defineSlots<AdminResourceTableSlots>();
</script>
<template>
  <Alert v-if="controller.error.value" tone="warning">
    {{ controller.error.value.message }}
    <Button
      size="sm"
      :disabled="controller.resource.busy.value"
      @click="controller.refresh"
      >Retry</Button
    >
  </Alert>
  <Table
    mode="server"
    english
    :searchable="false"
    :loading="controller.resource.busy.value"
    :columns="controller.columns.value"
    :provider="controller.provider.value"
    :query="controller.query.value"
    :row-key="(row: AdminRecord) => row.public_id!"
    :caption="controller.section.value?.title ?? 'Records'"
    :page-sizes="[10, 25, 50, 100]"
    @update:query="controller.query.value = $event"
  >
    <template v-if="$slots.filters" #filters="scope"
      ><slot name="filters" v-bind="scope"
    /></template>
    <template #toolbar="scope">
      <slot name="toolbar" v-bind="scope">
        <Button
          size="sm"
          :disabled="controller.resource.busy.value"
          @click="controller.refresh"
          >Refresh</Button
        >
      </slot>
    </template>
    <template v-if="$slots.actions" #actions="scope"
      ><slot name="actions" v-bind="scope"
    /></template>
    <template
      v-for="column in controller.columns.value.filter(
        (field) => $slots[`cell-${field.key}`],
      )"
      :key="column.key"
      #[`cell-${column.key}`]="scope"
    >
      <slot :name="`cell-${column.key}`" v-bind="scope" />
    </template>
  </Table>
</template>
