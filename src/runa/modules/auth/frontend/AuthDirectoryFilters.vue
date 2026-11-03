<script setup lang="ts">
import { computed } from 'vue';
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
// Keep filter order independent of the table's fact/column declaration order.
const filterOrder = [
  'display_name',
  'first_name',
  'surname',
  'login',
  'email',
  'has_avatar',
  'active',
  'state',
  'verified',
  'verification_required',
  'invitation_pending',
  'password_set',
  'password_change_required',
  'password_expired',
  'security_locked',
  'operator_recovery_pending',
  'two_factor_enabled',
  'two_factor_required',
  'totp_enrolled',
  'email_factor_enrolled',
  'totp_setup_pending',
  'recovery_codes_remaining',
  'logged_in',
  'session_count',
  'trusted_device_count',
  'created_at',
  'updated_at',
  'activate_at',
  'deactivate_at',
  'deleted_at',
  'email_verified_at',
  'invitation_sent_at',
  'invitation_expires_at',
  'password_changed_at',
  'password_expires_at',
  'attempt_locked_until',
  'totp_pending_until',
  'last_login_at',
  'last_activity_at',
  'authenticated_at',
  'activity_at',
];
const orderedFields = computed(() => {
  const rank = (field: AdminFieldData) => {
    const index = filterOrder.indexOf(field.key);
    return index < 0 ? filterOrder.length : index;
  };
  return props.fields
    .filter((field) => field.filterable)
    .sort(
      (a, b) =>
        Number(a.type === 'datetime') - Number(b.type === 'datetime') ||
        rank(a) - rank(b),
    );
});
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
  if (field.key === 'state')
    return [
      { value: 'current', label: tr({ pl: 'Nieusunięte', en: 'Not deleted' }) },
      { value: 'deleted', label: tr({ pl: 'Usunięte', en: 'Deleted' }) },
      { value: 'all', label: tr('All') },
    ];
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
    <template v-for="field in orderedFields" :key="field.key">
      <DateTimeRangeInput
        size="sm"
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
        size="sm"
        v-else-if="field.type === 'boolean' || field.type === 'dictionary'"
        :label="
          field.key === 'state'
            ? tr({ pl: 'Usunięte', en: 'Deleted' })
            : tr(field.label)
        "
        :model-value="
          String(
            query.filters?.[field.key] ??
              (field.key === 'state' ? 'current' : ''),
          )
        "
        :options="options(field)"
        :locale="locale"
        @update:model-value="set(field.key, $event)"
      />
      <NumberInput
        size="sm"
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
        size="sm"
        v-else
        :label="tr(field.label)"
        :model-value="String(query.filters?.[field.key] ?? '')"
        @update:model-value="set(field.key, $event)"
      />
    </template>
  </div>
</template>
