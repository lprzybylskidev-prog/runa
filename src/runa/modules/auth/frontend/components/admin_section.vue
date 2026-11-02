<script setup lang="ts">
import { useAdminLocale } from '@/runa/admin';
const { locale: adminLocale, t: tr } = useAdminLocale();
import AdminAvatar from '../AuthAdminAvatar.vue';
import { computed, watch } from 'vue';
import {
  formatApplicationInstant,
  FormNotifications,
  Button,
  Card,
  ValuePreview,
} from '@/runa/ui';
import { AdminSection, useAdminResource } from '@/runa/admin';
import type {
  AdminCommandReview,
  AdminJson,
  AdminSectionPresentationProps,
} from '@/runa/admin';
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
  fields: props.section.fields.filter((field) => {
    if (props.section.key === 'bulk_sessions' && field.key === 'summary')
      return false;
    if (
      collectionKeys.includes(field.key) ||
      field.key === 'avatar_upload_pending'
    )
      return false;
    if (props.section.key !== 'lifecycle') return true;
    const operation =
      props.controller.values.value[
        props.controller.fieldPath('lifecycle', 'operation')
      ];
    if (field.key === 'at')
      return (
        typeof operation === 'string' &&
        [
          'schedule_activation',
          'schedule_deactivation',
          'set_expiry',
          'set_attempt_lock',
        ].includes(operation)
      );
    if (field.key === 'enabled')
      return (
        typeof operation === 'string' &&
        ['set_security_lock', 'require_password_change'].includes(operation)
      );
    return true;
  }),
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
function reviewCount(review: AdminCommandReview, key: string): string {
  const summary = review.changes.summary;
  if (!summary || typeof summary !== 'object' || Array.isArray(summary))
    return '—';
  return typeof summary[key] === 'number' ? String(summary[key]) : '—';
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
    <AdminSection
      :section="fields"
      :controller="controller"
      :embedded="page.page.value?.key === 'users'"
    >
      <template
        v-if="section.key === 'bulk_sessions'"
        #commandReview="{ review }"
      >
        <p class="text-sm font-semibold">{{ tr('Review summary') }}</p>
        <dl class="grid gap-4 sm:grid-cols-3">
          <div
            v-for="item in [
              { key: 'accounts', label: { pl: 'Konta', en: 'Accounts' } },
              { key: 'sessions', label: { pl: 'Sesje', en: 'Sessions' } },
              {
                key: 'trusted_devices',
                label: { pl: 'Zaufane urządzenia', en: 'Trusted devices' },
              },
            ]"
            :key="item.key"
          >
            <dt class="text-xs text-muted">{{ tr(item.label) }}</dt>
            <dd class="mt-1 text-lg font-semibold">
              {{ reviewCount(review, item.key) }}
            </dd>
          </div>
        </dl>
      </template>
    </AdminSection>
    <AdminAvatar
      v-if="section.key === 'avatar'"
      :page="page"
      :section="section"
      :controller="controller"
    />
  </Card>
</template>
