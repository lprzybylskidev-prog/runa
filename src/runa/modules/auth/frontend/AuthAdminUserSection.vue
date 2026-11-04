<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import {
  AdminField,
  AdminSection,
  useAdminLocale,
  useAdminResource,
} from '@/runa/admin';
import type {
  AdminJson,
  AdminSectionPresentationProps,
  AdminComponentRegistry,
} from '@/runa/admin';
import {
  Alert,
  Button,
  Card,
  ContentLoading,
  Form,
  FormConflict,
  FormNotifications,
  Select,
  Switch,
  formatApplicationInstant,
} from '@/runa/ui';
import AuthAdminAvatar from './AuthAdminAvatar.vue';
const props = defineProps<
  AdminSectionPresentationProps & {
    components?: AdminComponentRegistry | undefined;
  }
>();
const emit = defineEmits<{
  submit: [section: string, command: string, fields?: string[]];
  operation: [
    section: string,
    command: string,
    values: Record<string, AdminJson>,
  ];
}>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const resource = useAdminResource(props.page, props.section.key);
watch(
  () => props.controller.records.value[props.section.key],
  () => resource.clear(),
);
const settled = ref(false);
void props.controller.activateSection(props.section.key).finally(() => {
  settled.value = true;
});
const path = (key: string) =>
  props.controller.fieldPath(props.section.key, key);
const get = (key: string): AdminJson =>
  props.controller.values.value[path(key)] ?? null;
const set = (key: string, value: AdminJson) =>
  props.controller.set(path(key), value);
function array(key: string): Record<string, AdminJson>[] {
  const result = resource.result.value;
  const value = result && 'values' in result ? result.values[key] : get(key);
  if (typeof value !== 'string') return [];
  try {
    const parsed: unknown = JSON.parse(value);
    return Array.isArray(parsed)
      ? parsed.filter(
          (row): row is Record<string, AdminJson> =>
            !!row && typeof row === 'object' && !Array.isArray(row),
        )
      : [];
  } catch {
    return [];
  }
}
const kind = computed(() =>
  props.section.key.endsWith('.assignments')
    ? 'assignments'
    : props.section.key,
);
const collectionKey = computed(
  () =>
    ({
      secondary: 'addresses',
      sessions: 'sessions',
      trust: 'devices',
      exceptions: 'exceptions',
    })[kind.value as 'secondary' | 'sessions' | 'trust' | 'exceptions'],
);
const rows = computed(() =>
  collectionKey.value ? array(collectionKey.value) : [],
);
const fields = computed(() =>
  props.section.fields.filter((field) => {
    if (
      [
        'preference_revision',
        'exception_versions',
        'exception_version',
        'policy_revision',
        'address_id',
        'session_id',
        'device_id',
        'avatar_upload_pending',
        'avatar_max_kib',
        'operation',
        'at',
        'enabled',
        'categories',
        'recipients',
        'delivery',
      ].includes(field.key)
    )
      return false;
    if (field.key === collectionKey.value) return false;
    if (['sessions', 'trust'].includes(kind.value)) return false;
    if (kind.value === 'lifecycle')
      return [
        'active',
        'deleted',
        'security_locked',
        'activate_at',
        'deactivate_at',
        'attempt_locked_until',
        'created_at',
        'updated_at',
        'deleted_at',
        'operator_recovery_pending',
      ].includes(field.key);
    if (kind.value === 'preferences') return false;
    return true;
  }),
);
const emailFact = computed(() =>
  props.page.page.value?.sections
    .find((section) => section.key === 'lifecycle')
    ?.fields.find((field) => field.key === 'email_verified_at'),
);
const commands = computed(() =>
  props.section.commands.filter((command) => command.available),
);
const conflicts = computed(() =>
  props.controller.conflicts.value.filter((conflict) =>
    props.section.fields.some((field) => path(field.key) === conflict.path),
  ),
);
const categories = computed(() =>
  array('categories').filter((row) => !row.mandatory && !row.sensitive),
);
const delivery = computed(() => array('delivery'));
const recipients = computed(() =>
  array('recipients').flatMap((row) =>
    typeof row.value === 'string' && typeof row.label === 'string'
      ? [{ value: row.value, label: row.label }]
      : [],
  ),
);
const preferenceReadonly = computed(
  () =>
    props.section.readonly ||
    !commands.value.some((command) => command.key === 'save'),
);
function deliveryValue(row: Record<string, AdminJson>) {
  return delivery.value.find(
    (item) => item.module === row.module && item.key === row.key,
  );
}
function updateDelivery(
  row: Record<string, AdminJson>,
  patch: Record<string, AdminJson>,
) {
  set(
    'delivery',
    JSON.stringify(
      delivery.value.map((item) =>
        item.module === row.module && item.key === row.key
          ? { ...item, ...patch }
          : item,
      ),
    ),
  );
}
function recipientIds(row: Record<string, AdminJson>): string[] {
  const value = deliveryValue(row)?.recipients;
  return Array.isArray(value)
    ? value.filter((id): id is string => typeof id === 'string')
    : [];
}
const preferenceErrors = computed(() =>
  categories.value.some(
    (row) => deliveryValue(row)?.enabled === true && !recipientIds(row).length,
  ),
);
const labels: Record<string, string> = {
  secondary: text('Adresy dodatkowe', 'Secondary emails'),
  lifecycle: text('Stan konta', 'Account status'),
  eligibility: text('Warunki dostępu', 'Access conditions'),
  assignments: text('Role i uprawnienia', 'Roles and permissions'),
};
function display(value: AdminJson, key: string): string {
  if (value === null) return '—';
  if (typeof value === 'boolean') return t(value ? 'Yes' : 'No');
  if (
    typeof value === 'string' &&
    (key.endsWith('_at') || key.endsWith('_until'))
  )
    return formatApplicationInstant(value, locale.value);
  if (typeof value === 'object') return JSON.stringify(value);
  return String(value);
}
function edit(row: Record<string, AdminJson>) {
  if (kind.value === 'secondary') {
    set('address_id', row.public_id ?? null);
    set('email', row.email ?? '');
  } else if (kind.value === 'exceptions') {
    for (const key of ['key', 'value', 'reason', 'expires_at'])
      set(key, row[key] ?? null);
    set('exception_version', row.version ?? 0);
  }
}
function rowAction(row: Record<string, AdminJson>, command: string) {
  const values: Record<string, AdminJson> = {};
  if (kind.value === 'secondary') values.address_id = row.public_id ?? null;
  if (kind.value === 'sessions') values.session_id = row.public_id ?? null;
  if (kind.value === 'trust') values.device_id = row.public_id ?? null;
  if (kind.value === 'exceptions') {
    Object.assign(values, {
      key: row.key ?? null,
      value: null,
      reason: row.reason ?? '',
      expires_at: new Date(Date.now() + 3600000).toISOString(),
      policy_revision: get('policy_revision'),
    });
    try {
      const versions: unknown = JSON.parse(
        String(get('exception_versions') ?? '{}'),
      );
      values.exception_version =
        versions && typeof versions === 'object' && !Array.isArray(versions)
          ? ((versions as Record<string, number>)[String(row.key)] ?? 0)
          : 0;
    } catch {
      values.exception_version = 0;
    }
  }
  emit('operation', props.section.key, command, values);
}
watch(
  () => get('key'),
  (key) => {
    if (kind.value !== 'exceptions') return;
    try {
      const versions: unknown = JSON.parse(
        String(get('exception_versions') ?? '{}'),
      );
      set(
        'exception_version',
        versions &&
          typeof versions === 'object' &&
          !Array.isArray(versions) &&
          typeof key === 'string'
          ? ((versions as Record<string, number>)[key] ?? 0)
          : 0,
      );
    } catch {
      set('exception_version', 0);
    }
  },
);
async function older() {
  const id = props.controller.records.value[props.section.key]?.public_id;
  if (id && typeof next.value === 'string')
    await resource.read({ kind: 'record', public_id: id, cursor: next.value });
}
const next = computed(() => rows.value.at(-1)?.public_id);
const cursor = computed(() => ['sessions', 'trust'].includes(kind.value));
const canSave = computed(() =>
  commands.value.some((command) => command.key === 'save'),
);
const rowKeys = computed(() =>
  kind.value === 'secondary'
    ? ['email', 'verified_at', 'created_at']
    : kind.value === 'exceptions'
      ? ['key', 'value', 'reason', 'expires_at', 'revoked_at']
      : kind.value === 'sessions'
        ? [
            'created_at',
            'authenticated_at',
            'activity_at',
            'expires_at',
            'current',
            'remembered',
          ]
        : ['created_at', 'expires_at'],
);
const fieldTitle = (key: string) =>
  t(
    (
      {
        key: { pl: 'Zasada', en: 'Rule' },
        value: { pl: 'Wartość', en: 'Value' },
        reason: { pl: 'Powód', en: 'Reason' },
        email: { pl: 'Adres e-mail', en: 'Email' },
        verified_at: { pl: 'Data weryfikacji', en: 'Verification date' },
        created_at: { pl: 'Utworzono', en: 'Created at' },
        expires_at: { pl: 'Wygasa', en: 'Expires at' },
        revoked_at: { pl: 'Odwołano', en: 'Revoked at' },
        authenticated_at: { pl: 'Zalogowano', en: 'Signed in' },
        activity_at: { pl: 'Ostatnia aktywność', en: 'Last activity' },
        current: { pl: 'Bieżąca sesja', en: 'Current session' },
        remembered: { pl: 'Zapamiętane logowanie', en: 'Remembered sign-in' },
      } as Record<string, { pl: string; en: string }>
    )[key] ?? key,
  );
</script>
<template>
  <Card :data-user-section="kind">
    <template #header
      ><h2>{{ labels[kind] ?? t(section.title) }}</h2></template
    >
    <Form
      :label="labels[kind] ?? t(section.title)"
      :busy="controller.busy.value"
      :valid="!conflicts.length && !preferenceErrors"
      @submit="emit('submit', section.key, 'save', [])"
    >
      <AdminSection :section="section" :controller="controller" embedded>
        <template #default>
          <ContentLoading
            v-if="!controller.records.value[section.key] && !settled"
            shape="form"
            :label="t('Loading record details')"
            class="md:col-span-2"
          />
          <Alert
            v-else-if="!controller.records.value[section.key]"
            tone="warning"
            class="md:col-span-2"
            >{{ t('Unable to load the current record.') }}
            <Button size="sm" @click="controller.refresh">{{
              t('Retry loading')
            }}</Button></Alert
          >
          <div v-else class="space-y-5 md:col-span-2">
            <FormNotifications
              :locale="locale"
              :error="
                resource.error.value ? t(resource.error.value.message) : ''
              "
            />
            <div v-if="rows.length" class="space-y-3">
              <div
                v-for="(row, index) in rows"
                :key="String(row.public_id ?? index)"
                class="rounded-lg border border-line p-4"
              >
                <dl
                  class="grid min-w-0 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-3"
                >
                  <div
                    v-for="key in rowKeys.filter((key) => key in row)"
                    :key="key"
                    class="min-w-0"
                  >
                    <dt class="text-xs text-muted">{{ fieldTitle(key) }}</dt>
                    <dd class="mt-1 break-words">
                      {{
                        key === 'key' && typeof row[key] === 'string'
                          ? t(row[key])
                          : display(row[key] ?? null, key)
                      }}
                    </dd>
                  </div>
                </dl>
                <div v-if="!section.readonly" class="mt-3 flex flex-wrap gap-2">
                  <Button
                    v-if="['secondary', 'exceptions'].includes(kind)"
                    size="sm"
                    @click="edit(row)"
                    >{{ text('Edytuj', 'Edit') }}</Button
                  >
                  <Button
                    v-for="command in commands.filter((command) =>
                      (
                        ({
                          secondary: ['remove'],
                          sessions: ['revoke'],
                          trust: ['revoke_device'],
                          exceptions: ['set'],
                        }) as Record<string, string[]>
                      )[kind]?.includes(command.key),
                    )"
                    :key="command.key"
                    size="sm"
                    variant="outline"
                    @click="rowAction(row, command.key)"
                    >{{
                      kind === 'exceptions'
                        ? text('Odwołaj wyjątek', 'Revoke exception')
                        : t(command.title)
                    }}</Button
                  >
                </div>
              </div>
            </div>
            <p
              v-else-if="collectionKey && controller.records.value[section.key]"
              class="text-sm text-muted"
            >
              {{ t('No entries in this bounded result.') }}
            </p>
            <div
              v-if="cursor && (next || resource.result.value)"
              class="flex gap-2"
            >
              <Button size="sm" @click="resource.clear()">{{
                t('Latest entries')
              }}</Button>
              <Button
                size="sm"
                :disabled="resource.busy.value || !next"
                @click="older"
                >{{ t('Older entries') }}</Button
              >
            </div>
            <div
              class="grid min-w-0 gap-4 sm:grid-cols-2"
              :class="kind === 'lifecycle' ? 'lg:grid-cols-3' : ''"
            >
              <AdminField
                v-for="field in fields"
                :key="field.key"
                :field="field"
                :model-value="get(field.key)"
                :errors="controller.errors.value[path(field.key)] ?? []"
                :readonly="section.readonly || field.readonly"
                :busy="controller.busy.value"
                :lookup="
                  field.lookup
                    ? controller.lookup(section.key, field.key)
                    : undefined
                "
                :components="components"
                @update:model-value="set(field.key, $event)"
                @availability="controller.setAvailable(path(field.key), $event)"
              />
            </div>
            <AdminField
              v-if="kind === 'primary_email' && emailFact"
              :field="emailFact"
              :model-value="
                controller.values.value[
                  controller.fieldPath('lifecycle', 'email_verified_at')
                ] ?? null
              "
              :errors="[]"
              readonly
              :busy="controller.busy.value"
            />
            <template v-if="kind === 'preferences'">
              <div class="grid gap-4 sm:grid-cols-2">
                <Select
                  :label="text('Język', 'Language')"
                  :model-value="String(get('locale') ?? 'pl')"
                  :options="[
                    { value: 'pl', label: 'Polski' },
                    { value: 'en', label: 'English' },
                  ]"
                  :locale="locale"
                  :disabled="preferenceReadonly"
                  @update:model-value="set('locale', $event)"
                />
                <Select
                  :label="text('Motyw', 'Theme')"
                  :model-value="String(get('theme') ?? 'system')"
                  :options="[
                    { value: 'light', label: text('Jasny', 'Light') },
                    { value: 'dark', label: text('Ciemny', 'Dark') },
                    { value: 'system', label: text('Systemowy', 'System') },
                  ]"
                  :locale="locale"
                  :disabled="preferenceReadonly"
                  @update:model-value="set('theme', $event)"
                />
              </div>
              <div
                v-for="category in categories"
                :key="String(category.module) + ':' + String(category.key)"
                class="space-y-3 rounded-lg border border-line p-4"
              >
                <Switch
                  :label="
                    category.names &&
                    typeof category.names === 'object' &&
                    !Array.isArray(category.names)
                      ? String(category.names[locale] ?? category.key)
                      : String(category.key)
                  "
                  :model-value="deliveryValue(category)?.enabled === true"
                  :disabled="preferenceReadonly"
                  @update:model-value="
                    updateDelivery(category, { enabled: $event })
                  "
                />
                <Select
                  v-if="deliveryValue(category)?.enabled"
                  multiple
                  :label="text('Odbiorcy', 'Recipients')"
                  :model-value="recipientIds(category)"
                  :options="recipients"
                  :locale="locale"
                  :disabled="preferenceReadonly"
                  :error="
                    !recipientIds(category).length
                      ? text(
                          'Wybierz co najmniej jeden adres.',
                          'Select at least one address.',
                        )
                      : ''
                  "
                  @update:model-value="
                    updateDelivery(category, { recipients: $event })
                  "
                />
              </div>
              <p class="text-sm text-muted">
                {{
                  text(
                    'Wiadomości wymagane ze względów bezpieczeństwa są wysyłane na główny adres.',
                    'Required security messages are delivered to the primary email.',
                  )
                }}
              </p>
            </template>
            <AuthAdminAvatar
              v-if="kind === 'avatar'"
              :page="page"
              :section="section"
              :controller="controller"
            />
            <FormConflict
              v-for="conflict in conflicts"
              :key="conflict.path"
              :locale="locale"
              :current="JSON.stringify(conflict.remote)"
              :busy="controller.busy.value"
              @accept="controller.resolve(conflict.path, 'remote')"
              @keep="controller.resolve(conflict.path, 'local')"
            />
          </div>
        </template>
      </AdminSection>
      <template #actions>
        <Button
          v-if="canSave"
          type="submit"
          variant="outline"
          :disabled="section.readonly || !!conflicts.length"
          >{{ t('Save') }}</Button
        >
        <template
          v-if="['primary_email', 'secondary', 'exceptions'].includes(kind)"
        >
          <Button
            v-for="command in commands.filter(
              (command) =>
                !['remove', 'revoke_all', 'require_verification'].includes(
                  command.key,
                ) &&
                (kind !== 'secondary' ||
                  command.key === (get('address_id') ? 'change' : 'add')),
            )"
            :key="command.key"
            variant="outline"
            :disabled="
              section.readonly ||
              !!conflicts.length ||
              (kind === 'secondary' &&
                command.key === 'change' &&
                !get('address_id'))
            "
            @click="emit('submit', section.key, command.key, [])"
            >{{
              kind === 'exceptions'
                ? text('Zapisz wyjątek', 'Save exception')
                : t(command.title)
            }}</Button
          >
          <Button
            v-if="kind === 'secondary' && get('address_id')"
            @click="
              set('address_id', null);
              set('email', '');
            "
            >{{ text('Nowy adres', 'New address') }}</Button
          >
        </template>
      </template>
    </Form>
  </Card>
</template>
