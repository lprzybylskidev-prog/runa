<script setup lang="ts">
import { reactive, watch } from 'vue';
import { instantTimestamp } from '@/runa/ui';
import { DateTimeInput, NumberInput, Select, TextInput } from '@/runa/ui';
import type { TableQuery } from '@/runa/ui';
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
const drafts = reactive<Record<string, { from?: string; until?: string }>>({});
watch(
  () => props.resetKey,
  () => {
    for (const key of Object.keys(drafts)) delete drafts[key];
  },
);
function ordered(value: { from?: string; until?: string }): boolean {
  return (
    !value.from ||
    !value.until ||
    instantTimestamp(value.from) <= instantTimestamp(value.until)
  );
}
function range(key: string): { from?: string; until?: string } {
  if (drafts[key]) return drafts[key];
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
function date(key: string, side: 'from' | 'until', value: string | null) {
  const next = { ...range(key) };
  if (value) next[side] = value;
  else delete next[side];
  props.setValidity(key + ':range', ordered(next));
  if (!ordered(next)) {
    drafts[key] = next;
    return;
  }
  delete drafts[key];
  set(key, Object.keys(next).length ? JSON.stringify(next) : null);
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
      <fieldset
        v-if="field.type === 'datetime'"
        class="min-w-0 sm:col-span-2 lg:col-span-3"
      >
        <legend class="mb-1 text-sm font-medium">{{ tr(field.label) }}</legend>
        <div class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2">
          <DateTimeInput
            :key="field.key + ':from:' + resetKey"
            :label="tr({ pl: 'Od', en: 'From' })"
            :model-value="range(field.key).from ?? null"
            :locale="locale"
            @validity="setValidity(field.key + ':from', $event)"
            @update:model-value="date(field.key, 'from', $event)"
          />
          <DateTimeInput
            :key="field.key + ':until:' + resetKey"
            :error="
              ordered(range(field.key))
                ? ''
                : tr({
                    pl: 'Koniec zakresu musi przypadać po jego początku.',
                    en: 'The range end must follow its start.',
                  })
            "
            :label="tr({ pl: 'Do', en: 'Until' })"
            :model-value="range(field.key).until ?? null"
            :locale="locale"
            @validity="setValidity(field.key + ':until', $event)"
            @update:model-value="date(field.key, 'until', $event)"
          />
        </div>
      </fieldset>
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
