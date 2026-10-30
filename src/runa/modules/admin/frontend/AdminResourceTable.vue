<script setup lang="ts">
import { computed } from 'vue';
import {
  Table,
  Button,
  Alert,
  ConfirmDialog,
  TableActions,
  TablePreferences,
} from '@/runa/ui';
import type {
  AdminRecord,
  AdminResourceTableProps,
  AdminResourceTableSlots,
} from './contracts';
const props = defineProps<AdminResourceTableProps>();
defineSlots<AdminResourceTableSlots>();
const exportColumns = computed(() =>
  props.controller.columns.value.filter(
    (column) =>
      props.controller.views.columns.value.some(
        (state) => state.key === column.key && state.visible,
      ) &&
      !props.controller.section.value?.fields.find(
        (field) => field.key === column.key,
      )?.sensitive,
  ),
);
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
    :column-state="controller.views.columns.value"
    :provider="controller.provider.value"
    :query="controller.query.value"
    :row-key="(row: AdminRecord) => row.public_id!"
    :caption="controller.section.value?.title ?? 'Records'"
    :page-sizes="[10, 25, 50, 100]"
    :selectable="
      !!controller.adapters.value.bulk ||
      !!controller.adapters.value.exports?.capabilities.scopes.includes(
        'selected',
      )
    "
    :selection="controller.selection.value"
    @update:query="controller.query.value = $event"
    @update:selection="controller.selection.value = $event"
  >
    <template v-if="$slots.filters" #filters="scope"
      ><slot name="filters" v-bind="scope"
    /></template>
    <template #toolbar="scope">
      <slot name="toolbar" v-bind="scope">
        <TablePreferences
          :controller="controller.views"
          :columns="controller.columns.value"
          english
        />
        <Button
          size="sm"
          :disabled="controller.resource.busy.value"
          @click="controller.refresh"
          >Refresh</Button
        >
        <Button
          v-if="
            controller.section.value?.commands.some(
              (command) => command.intent === 'create' && command.available,
            )
          "
          size="sm"
          @click="controller.select(null)"
          >New record</Button
        >
        <TableActions
          v-if="
            controller.adapters.value.exports ||
            controller.adapters.value.bulk ||
            controller.adapters.value.print
          "
          :provider="controller.adapters.value.exports"
          :bulk-provider="controller.adapters.value.bulk"
          :print-adapter="controller.adapters.value.print"
          :query="controller.query.value"
          :selection="controller.selection.value"
          :columns="exportColumns"
          :feedback-target="scope.feedbackTarget"
          english
        />
      </slot>
    </template>
    <template
      v-if="
        $slots.actions ||
        controller.section.value?.commands.some(
          (command) => command.intent === 'save' || command.intent === 'delete',
        )
      "
      #actions="scope"
    >
      <slot name="actions" v-bind="scope"
        ><Button size="sm" @click="controller.select(scope.row.public_id)"
          >Open record</Button
        ></slot
      >
    </template>
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
  <ConfirmDialog
    locale="en"
    :model-value="!!controller.confirmation.value"
    :title="controller.confirmation.value ?? 'Confirm bulk action'"
    description="Continue with this action for the selected records?"
    @confirm="controller.confirm(true)"
    @cancel="controller.confirm(false)"
    @update:model-value="!$event && controller.confirm(false)"
  />
</template>
