<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import { computed, watch } from 'vue';
import {
  formatApplicationInstant,
  FormNotifications,
  Button,
  Card,
  ValuePreview,
} from '@/runa/ui';
import { AdminSection, useAdminResource } from '@/runa/admin';
import type { AdminJson, AdminSectionPresentationProps } from '@/runa/admin';
const props = defineProps<AdminSectionPresentationProps>();
const collectionKeys = [
  'addresses',
  'sessions',
  'devices',
  'exceptions',
  'history',
];
const resource = useAdminResource(props.page, props.section.key);
const record = computed(
  () => props.controller.records.value[props.section.key],
);
watch(record, () => resource.clear());
const collection = computed(() =>
  props.section.fields.find((field) => collectionKeys.includes(field.key)),
);
const fields = computed(() => ({
  ...props.section,
  fields: props.section.fields.filter(
    (field) => !collectionKeys.includes(field.key),
  ),
}));
const rows = computed<Record<string, AdminJson>[]>(() => {
  const result = resource.result.value;
  const values =
    result && 'values' in result ? result.values : record.value?.values;
  const raw = collection.value && values?.[collection.value.key];
  if (typeof raw !== 'string') return [];
  try {
    const parsed: unknown = JSON.parse(raw);
    return Array.isArray(parsed)
      ? parsed.filter(
          (row): row is Record<string, AdminJson> =>
            row !== null && typeof row === 'object' && !Array.isArray(row),
        )
      : [];
  } catch {
    return [];
  }
});
const paged = computed(
  () =>
    collection.value &&
    ['sessions', 'devices', 'history'].includes(collection.value.key),
);
const next = computed(() => {
  const last = rows.value.at(-1);
  return collection.value?.key === 'history' ? last?.revision : last?.public_id;
});
const instantKeys = new Set([
  'created_at',
  'updated_at',
  'occurred_at',
  'authenticated_at',
  'activity_at',
  'expires_at',
  'last_used_at',
  'revoked_at',
]);
function display(value: AdminJson, key: string): string {
  if (instantKeys.has(key) && typeof value === 'string')
    return formatApplicationInstant(value, adminLocale.value);
  if (value === null) return '—';
  if (typeof value === 'boolean') return value ? tr('Yes') : tr('No');
  return typeof value === 'object' ? JSON.stringify(value) : String(value);
}
async function older() {
  if (!next.value) return;
  await resource.read({
    kind: 'record',
    ...(record.value?.public_id ? { public_id: record.value.public_id } : {}),
    cursor: String(next.value),
  });
}
function select(row: Record<string, AdminJson>) {
  const targets: Record<string, string> = {
    addresses: 'address_id',
    sessions: 'session_id',
    devices: 'device_id',
    history: 'source_revision',
  };
  const target = targets[collection.value?.key ?? ''];
  if (target)
    props.controller.set(
      props.controller.fieldPath(props.section.key, target),
      target === 'source_revision'
        ? (row.revision ?? null)
        : (row.public_id ?? null),
    );
  if (collection.value?.key === 'exceptions') {
    for (const [field, source] of [
      ['key', 'key'],
      ['exception_version', 'version'],
    ] as const)
      props.controller.set(
        props.controller.fieldPath(props.section.key, field),
        row[source] ?? null,
      );
  }
}
</script>
<template>
  <Card>
    <template #header
      ><h2>{{ tr(section.title) }}</h2></template
    >
    <template v-if="collection">
      <FormNotifications
        :locale="adminLocale"
        :error="resource.error.value ? tr(resource.error.value.message) : ''"
      />
      <p v-if="record && !rows.length" class="mb-4 text-sm text-muted">
        {{ tr('No entries in this bounded result.') }}
      </p>
      <ul
        v-else-if="rows.length"
        class="mb-4 space-y-3"
        :aria-label="tr(collection.label)"
      >
        <li
          v-for="(row, index) in rows"
          :key="String(row.public_id ?? index)"
          class="rounded border border-line p-3"
        >
          <dl class="grid gap-2 text-sm sm:grid-cols-2">
            <div v-for="(value, key) in row" :key="key" class="min-w-0">
              <dt class="font-semibold">{{ tr(String(key)) }}</dt>
              <dd class="mt-1 min-w-0">
                <ValuePreview
                  :text="display(value, String(key))"
                  :format="
                    value !== null && typeof value === 'object'
                      ? 'json'
                      : 'text'
                  "
                  :locale="adminLocale"
                />
              </dd>
            </div>
          </dl>
          <Button
            v-if="!section.readonly"
            size="sm"
            class="mt-2"
            :disabled="controller.busy.value"
            @click="select(row)"
            >{{ tr('Use this entry') }}</Button
          >
        </li>
      </ul>
      <div v-if="paged" class="mb-4 flex gap-2">
        <Button
          size="sm"
          :disabled="resource.busy.value || !next"
          @click="older"
          >{{ tr('Older entries') }}</Button
        >
        <Button
          size="sm"
          :disabled="resource.busy.value"
          @click="resource.clear()"
          >{{ tr('Latest entries') }}</Button
        >
      </div>
    </template>
    <AdminSection :section="fields" :controller="controller" />
  </Card>
</template>
