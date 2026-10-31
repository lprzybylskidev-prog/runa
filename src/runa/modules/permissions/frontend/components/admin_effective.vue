<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, ref, watch } from 'vue';
import { Alert, Button, Card, Table } from '@/runa/ui';
import type { TableColumn, TableQuery } from '@/runa/ui';
import { useAdminResource } from '@/runa/admin';
import type { AdminJson, AdminSectionPresentationProps } from '@/runa/admin';
const query = ref<TableQuery>({
  page: 1,
  perPage: 25,
  sort: null,
  filters: {},
});
const props = defineProps<AdminSectionPresentationProps>();
const resource = useAdminResource(props.page, props.section.key);
void props.controller.activateSection(props.section.key);
const record = computed(
  () => props.controller.records.value[props.section.key],
);
watch(record, () => resource.clear());
const current = computed(() => {
  const result = resource.result.value;
  return result && 'values' in result ? result : record.value;
});
const rows = computed<Record<string, AdminJson>[]>(() => {
  const raw = current.value?.values.entries;
  if (typeof raw !== 'string') return [];
  try {
    const parsed: unknown = JSON.parse(raw);
    return Array.isArray(parsed)
      ? parsed.filter(
          (row): row is Record<string, AdminJson> =>
            row !== null &&
            typeof row === 'object' &&
            typeof row.code === 'string',
        )
      : [];
  } catch {
    return [];
  }
});
const columns = computed<TableColumn<Record<string, AdminJson>>[]>(() =>
  ['code', 'direct', 'roles', 'roles_truncated', 'available', 'effective'].map(
    (key) => ({
      key,
      label: tr(key.replaceAll('_', ' ')),
      value: (row) =>
        typeof row[key] === 'boolean'
          ? row[key]
            ? tr('Yes')
            : tr('No')
          : String(row[key] ?? ''),
    }),
  ),
);
async function next() {
  const cursor = current.value?.values.next;
  if (typeof cursor === 'string')
    await resource.read({
      kind: 'record',
      ...(record.value?.public_id ? { public_id: record.value.public_id } : {}),
      cursor,
    });
}
</script>
<template>
  <Card>
    <template #header
      ><h2>{{ tr(section.title) }}</h2></template
    >
    <Alert v-if="resource.error.value" tone="warning">{{
      tr(resource.error.value.message)
    }}</Alert>
    <Table
      mode="client"
      :loading="resource.busy.value || !record"
      :english="adminLocale === 'en'"
      :searchable="false"
      :rows="rows"
      :query="query"
      @update:query="query = $event"
      :columns="columns"
      :row-key="(row: Record<string, AdminJson>) => String(row.code)"
      :caption="tr('Current bounded permission page')"
    />
    <div class="mt-3 flex gap-2">
      <Button
        size="sm"
        :disabled="resource.busy.value || !current?.values.next"
        @click="next"
        >{{ tr('Next permissions') }}</Button
      >
      <Button
        size="sm"
        :disabled="resource.busy.value"
        @click="resource.clear()"
        >{{ tr('First permissions') }}</Button
      >
    </div>
  </Card>
</template>
