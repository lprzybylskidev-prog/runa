<script setup lang="ts">
import { DateTimeRangeInput, NumberInput, Select, TextInput } from '@/runa/ui';
import type { DateRangeValue, TableQuery } from '@/runa/ui';
import type { AdminFieldData } from '@/runa/admin';
import { useAdminLocale } from '@/runa/admin';
const props = defineProps<{
  fields: AdminFieldData[];
  query: TableQuery;
  update: (query: TableQuery) => void;
  resetKey: number;
  setValidity: (key: string, valid: boolean) => void;
}>();
const { locale, t: tr } = useAdminLocale();
function set(key: string, value: string | null) {
  const filters = { ...props.query.filters };
  if (value === null || value === '') delete filters[key];
  else filters[key] = value;
  props.update({ ...props.query, page: 1, filters });
}
function range(key: string): { from?: string; until?: string } {
  const raw = props.query.filters?.[key];
  if (typeof raw !== 'string') return {};
  try {
    const value: unknown = JSON.parse(raw);
    if (!value || typeof value !== 'object' || Array.isArray(value)) return {};
    const result: { from?: string; until?: string } = {};
    if ('from' in value && typeof value.from === 'string')
      result.from = value.from;
    if ('until' in value && typeof value.until === 'string')
      result.until = value.until;
    return result;
  } catch {
    return {};
  }
}
function date(key: string, value: DateRangeValue) {
  const range: { from?: string; until?: string } = {};
  if (value.start) range.from = value.start;
  if (value.end) range.until = value.end;
  set(key, Object.keys(range).length ? JSON.stringify(range) : null);
}
function options(field: AdminFieldData) {
  return [
    { value: '', label: tr('All') },
    ...(field.type === 'boolean'
      ? [
          { value: 'true', label: tr('Yes') },
          { value: 'false', label: tr('No') },
        ]
      : field.choices.map((value) => ({ value, label: tr(value) }))),
  ];
}
</script>
<template>
  <div
    class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
  >
    <template
      v-for="field in fields.filter((f) => f.filterable)"
      :key="field.key"
    >
      <DateTimeRangeInput
        v-if="field.type === 'datetime'"
        :key="field.key + ':' + resetKey"
        :label="tr(field.label)"
        :model-value="{
          start: range(field.key).from ?? null,
          end: range(field.key).until ?? null,
        }"
        :locale="locale"
        @validity="setValidity(field.key, $event)"
        @update:model-value="date(field.key, $event)"
      />
      <Select
        v-else-if="field.type === 'boolean' || field.type === 'dictionary'"
        :label="tr(field.label)"
        :model-value="String(query.filters?.[field.key] ?? '')"
        :options="options(field)"
        :locale="locale"
        @update:model-value="set(field.key, $event)"
      />
      <NumberInput
        v-else-if="field.type === 'integer'"
        :label="tr(field.label)"
        :model-value="
          query.filters?.[field.key] == null
            ? null
            : String(query.filters[field.key])
        "
        kind="integer"
        :locale="locale"
        @update:model-value="set(field.key, $event)"
      />
      <TextInput
        v-else
        :label="tr(field.label)"
        :model-value="String(query.filters?.[field.key] ?? '')"
        @update:model-value="set(field.key, $event)"
      />
    </template>
  </div>
</template>
