<script setup lang="ts">
import { useAdminLocale } from './localization';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { adminBooleanTone } from './booleanTone';
import { computed } from 'vue';
import type { TableColumn } from '@/runa/ui';
import {
  actionIcons,
  Table,
  BooleanBadge,
  formatApplicationInstant,
  Button,
  FormNotifications,
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
function displayCell(column: TableColumn<AdminRecord>, row: AdminRecord) {
  const field = props.controller.section.value?.fields.find(
    (field) => field.key === column.key,
  );
  const value = column.value(row);
  if (field?.sensitive) return value ?? '—';
  const raw = row.values[column.key];
  if (field?.type === 'datetime')
    return typeof raw === 'string'
      ? formatApplicationInstant(raw, adminLocale.value)
      : '—';
  if (typeof raw === 'string' && field?.choices.includes(raw)) return tr(raw);
  if (Array.isArray(raw) && field?.choices.length)
    return raw
      .map((item) =>
        typeof item === 'string' && field.choices.includes(item)
          ? tr(item)
          : String(item),
      )
      .join(', ');
  return value ?? '—';
}
</script>
<template>
  <FormNotifications
    :locale="adminLocale"
    :error="controller.error.value ? tr(controller.error.value.message) : ''"
  >
    <template #actions
      ><Button
        :icon="actionIcons.refresh"
        intent="inspect"
        :busy="controller.resource.busy.value"
        size="sm"
        :disabled="controller.resource.busy.value"
        @click="controller.refresh"
        >{{ tr('Retry') }}</Button
      >
    </template></FormNotifications
  >
  <Table
    mode="server"
    :refresh="controller.refresh"
    :export-allowed="
      controller.section.value?.export_allowed === true &&
      !controller.adapters.value.exports
    "
    :export-rows="controller.exportRows"
    :export-columns="exportColumns"
    :english="adminLocale === 'en'"
    :searchable="false"
    :loading="controller.resource.busy.value"
    :columns="controller.columns.value"
    :column-state="controller.views.columns.value"
    :provider="controller.provider.value"
    :query="controller.query.value"
    :row-key="(row: AdminRecord) => row.public_id!"
    :row-class="rowClass"
    :caption="tr(controller.section.value?.title ?? tr('Records'))"
    :page-sizes="[10, 25, 50, 100]"
    :selectable="
      selectable === true ||
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
    <template v-if="$slots.afterFilters" #afterFilters
      ><slot name="afterFilters"
    /></template>
    <template #toolbar="scope">
      <slot name="toolbar" v-bind="scope">
        <TablePreferences
          :controller="controller.views"
          :columns="controller.columns.value"
          :english="adminLocale === 'en'"
        />
        <Button
          v-if="
            controller.section.value?.commands.some(
              (command) => command.intent === 'create' && command.available,
            )
          "
          :icon="actionIcons.create"
          intent="commit"
          size="sm"
          @click="controller.select(null)"
          >{{ tr('New record') }}</Button
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
          :english="adminLocale === 'en'"
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
        ><Button
          :icon="actionIcons.inspect"
          intent="inspect"
          size="sm"
          @click="controller.select(scope.row.public_id)"
          >{{ tr('Details') }}</Button
        ></slot
      >
    </template>
    <template
      v-for="column in controller.columns.value.filter(
        (field) =>
          $slots[`cell-${field.key}`] ||
          controller.section.value?.fields.some(
            (declared) =>
              declared.key === field.key &&
              (['datetime', 'boolean'].includes(declared.type) ||
                declared.choices.length > 0),
          ),
      )"
      :key="column.key"
      #[`cell-${column.key}`]="scope"
    >
      <slot :name="`cell-${column.key}`" v-bind="scope">
        <BooleanBadge
          v-if="typeof scope.row.values[column.key] === 'boolean'"
          :value="scope.row.values[column.key] as boolean"
          :locale="adminLocale"
          :tone="
            adminBooleanTone(
              column.key,
              scope.row.values[column.key] as boolean,
            )
          "
        />
        <template v-else>{{ displayCell(column, scope.row) }}</template>
      </slot>
    </template>
  </Table>
  <ConfirmDialog
    :locale="adminLocale"
    :model-value="!!controller.confirmation.value"
    :confirm-intent="controller.confirmation.value?.intent"
    :title="
      tr(controller.confirmation.value?.title ?? tr('Confirm bulk action'))
    "
    :description="tr('Continue with this action for the selected records?')"
    @confirm="controller.confirm(true)"
    @cancel="controller.confirm(false)"
    @update:model-value="!$event && controller.confirm(false)"
  />
</template>
