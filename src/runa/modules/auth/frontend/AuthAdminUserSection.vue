<script setup lang="ts">
import { identityDrafts } from './identityDrafts';
import { trustedDeviceColumns } from './trustedDeviceColumns';
import { computed, reactive, ref, watch } from 'vue';
import {
  AdminField,
  adminBooleanTone,
  AdminSection,
  useAdminLocale,
  useAdminResource,
  useAdminPresentation,
} from '@/runa/admin';
import type {
  AdminJson,
  AdminSectionPresentationProps,
  AdminComponentRegistry,
} from '@/runa/admin';
import {
  Alert,
  BooleanBadge,
  Button,
  Card,
  ContentLoading,
  Form,
  FormConflict,
  FormNotifications,
  RadioGroup,
  TextInput,
  Table,
  formValuesEqual,
  formatApplicationInstant,
  instantTimestamp,
  withTableFilters,
} from '@/runa/ui';
import {
  AuthIdentityFields,
  AuthDisplayPreferenceFields,
  AuthEmailPreferenceFields,
} from '@/runa/auth';
import type { AuthIdentityField } from '@/runa/auth';
import type { TableColumn, TableQuery } from '@/runa/ui';
import { PermissionsAssignments } from '@/runa/permissions';
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
const presentation = useAdminPresentation();
const resource = useAdminResource(props.page, props.section.key);
const exports = useAdminResource(props.page, props.section.key);
const currentCursor = ref<string>();
watch(
  () => props.controller.records.value[props.section.key],
  () => {
    currentCursor.value = undefined;
    resource.clear();
  },
);
const settled = ref(false);
async function retry() {
  settled.value = false;
  try {
    await props.controller.refresh();
  } finally {
    settled.value = true;
  }
}
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
    })[kind.value as 'secondary' | 'sessions' | 'trust'],
);
const rows = computed(() =>
  collectionKey.value ? array(collectionKey.value) : [],
);
const fields = computed(() =>
  props.section.fields.filter((field) => {
    if (
      [
        'identifier_mode',
        'has_avatar',
        'avatar_initials',
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
        'recipient_selection_enabled',
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
    if (['preferences', 'profile'].includes(kind.value)) return false;
    if (
      kind.value === 'primary_email' &&
      ['email', 'replacement_email'].includes(field.key)
    )
      return (
        field.key ===
        (emailCommand.value === 'replace' ? 'replacement_email' : 'email')
      );
    return true;
  }),
);
const customIdentityFields = computed(() =>
  props.section.fields.filter(
    (field) =>
      identityKeys.includes(field.key as AuthIdentityField) &&
      (field.component || presentation.value.field),
  ),
);
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const naming = ref('full');
const nameDrafts = identityDrafts(get, set);
const attempted = ref(false);
const touched = reactive(new Set<string>());
const identityKeys: AuthIdentityField[] = [
  'name',
  'first_name',
  'surname',
  'login',
];
const identityValues = computed(
  () =>
    Object.fromEntries(
      identityKeys.map((key) => [
        key,
        typeof get(key) === 'string' ? get(key) : null,
      ]),
    ) as Record<AuthIdentityField, string | null>,
);
watch(
  record,
  (value, before) => {
    if (kind.value !== 'profile' || !value) return;
    if (
      value.public_id !== before?.public_id ||
      identityKeys.every((key) =>
        formValuesEqual(get(key), value.values[key] ?? null),
      )
    ) {
      nameDrafts.clear();
      naming.value = value.values.name ? 'single' : 'full';
      attempted.value = false;
      touched.clear();
    }
  },
  { immediate: true },
);
function changeNaming(value: string) {
  nameDrafts.change(naming.value, value);
  naming.value = value;
}
const identityIssues = computed<Record<string, string>>(() => {
  if (kind.value !== 'profile') return {};
  const issues: Record<string, string> = {};
  for (const key of naming.value === 'full'
    ? ['first_name', 'surname']
    : ['name']) {
    const value = String(get(key) ?? '');
    if (!value.trim())
      issues[key] = text('Wypełnij to pole.', 'Complete this field.');
    else if (
      value.trim() !== value ||
      [...value].length > 160 ||
      /\p{C}/u.test(value)
    )
      issues[key] = text('Popraw dane.', 'Correct this value.');
  }
  const login = String(get('login') ?? '');
  if (get('identifier_mode') === 'login' && !login)
    issues.login = text('Wypełnij to pole.', 'Complete this field.');
  else if (login && !/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}$/.test(login))
    issues.login = text('Niepoprawny login.', 'Invalid login.');
  return issues;
});
const emailIssues = computed<Record<string, string>>(() => {
  if (!['primary_email', 'secondary'].includes(kind.value)) return {};
  const issues: Record<string, string> = {};
  for (const field of fields.value.filter(
    (field) =>
      ['email', 'replacement_email'].includes(field.key) && !field.readonly,
  )) {
    const value = String(get(field.key) ?? '');
    if (!value.trim())
      issues[field.key] = text('Wypełnij to pole.', 'Complete this field.');
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || value.length > 254)
      issues[field.key] = text(
        'Podaj poprawny adres e-mail.',
        'Enter a valid email address.',
      );
  }
  return issues;
});
function emailErrors(key: string) {
  return [
    ...(props.controller.errors.value[path(key)] ?? []).map(t),
    ...((attempted.value || touched.has(key)) && emailIssues.value[key]
      ? [emailIssues.value[key]!]
      : []),
  ];
}
const identityErrors = computed(() =>
  Object.fromEntries(
    identityKeys.map((key) => [
      key,
      [
        ...(props.controller.errors.value[path(key)] ?? []).map(t),
        ...((attempted.value || touched.has(key)) && identityIssues.value[key]
          ? [identityIssues.value[key]!]
          : []),
      ],
    ]),
  ),
);
const dirty = computed(
  () =>
    !!record.value &&
    props.section.fields.some(
      (field) =>
        !field.readonly &&
        !formValuesEqual(
          get(field.key),
          record.value?.values[field.key] ?? null,
        ),
    ),
);
function revert() {
  nameDrafts.clear();
  for (const field of props.section.fields)
    if (!field.readonly)
      set(field.key, record.value?.values[field.key] ?? null);
  for (const conflict of conflicts.value)
    props.controller.resolve(conflict.path, 'remote');
  attempted.value = false;
  touched.clear();
  if (kind.value === 'profile')
    naming.value = record.value?.values.name ? 'single' : 'full';
}
const emailOperation = ref('change');
const emailCommands = computed(() =>
  commands.value.filter((command) =>
    ['change', 'replace'].includes(command.key),
  ),
);
const emailCommand = computed(() =>
  emailCommands.value.some((command) => command.key === emailOperation.value)
    ? emailOperation.value
    : emailCommands.value[0]?.key,
);
const submitCommand = computed(() =>
  kind.value === 'primary_email'
    ? emailCommand.value
    : kind.value === 'secondary'
      ? get('address_id')
        ? 'change'
        : 'add'
      : canSave.value
        ? 'save'
        : undefined,
);
function submit() {
  attempted.value = true;
  if (
    Object.keys(identityIssues.value).length ||
    Object.keys(emailIssues.value).length ||
    !submitCommand.value
  )
    return;
  emit('submit', props.section.key, submitCommand.value, []);
}
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
const labels = computed<Record<string, string>>(() => ({
  profile: text('Dane użytkownika', 'User details'),
  avatar: text('Zdjęcie profilowe', 'Profile image'),
  secondary: text('Adresy dodatkowe', 'Secondary emails'),
  lifecycle: text('Stan konta', 'Account status'),
  eligibility: text('Warunki dostępu', 'Access conditions'),
  assignments: text('Role i uprawnienia', 'Roles and permissions'),
  trust: text('Zaufane urządzenia', 'Trusted devices'),
}));
function fieldValue(key: string): AdminJson {
  if (kind.value !== 'eligibility') return get(key);
  const labels: Record<string, { pl: string; en: string }> = {
    operator_recovery_required: {
      pl: 'Wymagane odzyskanie przez operatora',
      en: 'Operator recovery required',
    },
    deleted: { pl: 'Konto usunięte', en: 'Account deleted' },
    before_activation: {
      pl: 'Oczekuje na termin aktywacji',
      en: 'Awaiting activation date',
    },
    inactive: { pl: 'Konto nieaktywne', en: 'Account inactive' },
    deactivated: {
      pl: 'Upłynął termin dezaktywacji',
      en: 'Deactivation date reached',
    },
    security_locked: { pl: 'Blokada bezpieczeństwa', en: 'Security lock' },
    attempt_locked: {
      pl: 'Blokada prób logowania',
      en: 'Sign-in attempt lock',
    },
    email_verification_required: {
      pl: 'Wymagane potwierdzenie adresu e-mail',
      en: 'Email verification required',
    },
    password_change_required: {
      pl: 'Wymagana zmiana hasła',
      en: 'Password change required',
    },
  };
  const codes = String(get(key) ?? '')
    .split(',')
    .map((code) => code.trim())
    .filter(Boolean);
  return codes.length
    ? codes.map((code) => t(labels[code] ?? code)).join(' · ')
    : text('Brak', 'None');
}
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
  }
}
function rowAction(row: Record<string, AdminJson>, command: string) {
  const values: Record<string, AdminJson> = {};
  if (kind.value === 'secondary') values.address_id = row.public_id ?? null;
  if (kind.value === 'sessions') values.session_id = row.public_id ?? null;
  if (kind.value === 'trust') values.device_id = row.public_id ?? null;
  emit('operation', props.section.key, command, values);
}
async function older() {
  const id = props.controller.records.value[props.section.key]?.public_id;
  if (id && typeof next.value === 'string') {
    const cursor = next.value;
    await resource.read({ kind: 'record', public_id: id, cursor });
    if (!resource.error.value) currentCursor.value = cursor;
  }
}
async function exportRows(signal: AbortSignal) {
  const id = record.value?.public_id;
  if (!id || !props.section.export_allowed)
    throw new Error('permission_denied');
  await exports.read(
    {
      kind: 'record',
      public_id: id,
      export: true,
      ...(currentCursor.value ? { cursor: currentCursor.value } : {}),
    },
    { signal },
  );
  signal.throwIfAborted();
  const result = exports.result.value;
  if (
    exports.error.value ||
    !result ||
    !('values' in result) ||
    result.public_id !== id
  )
    throw new Error('permission_denied');
  const raw = result.values[collectionKey.value!];
  if (typeof raw !== 'string') throw new Error('permission_denied');
  const parsed: unknown = JSON.parse(raw);
  if (
    !Array.isArray(parsed) ||
    parsed.some((row) => !row || typeof row !== 'object' || Array.isArray(row))
  )
    throw new Error('permission_denied');
  return parsed as Record<string, AdminJson>[];
}
const next = computed(() =>
  rows.value.length === 100 ? rows.value.at(-1)?.public_id : undefined,
);
const cursor = computed(() => ['sessions', 'trust'].includes(kind.value));
const canSave = computed(() =>
  commands.value.some((command) => command.key === 'save'),
);
const rowKeys = computed(() =>
  kind.value === 'secondary'
    ? ['email', 'verified_at', 'created_at']
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
const tableQuery = ref<TableQuery>({
  page: 1,
  perPage: 10,
  sort: null,
  filters: {},
});
watch(rows, () => {
  tableQuery.value = { ...tableQuery.value, page: 1 };
});
const tableColumns = computed<TableColumn<Record<string, AdminJson>>[]>(() =>
  withTableFilters(
    kind.value === 'trust'
      ? trustedDeviceColumns<Record<string, AdminJson>>(
          locale.value,
          (row, key) => row[key],
        )
      : ['authenticated_at', 'activity_at', 'expires_at', 'remembered'].map(
          (key) => ({
            key,
            label: fieldTitle(key),
            value: (row) => display(row[key] ?? null, key),
            ...(key === 'remembered'
              ? {}
              : {
                  compare: (
                    a: Record<string, AdminJson>,
                    b: Record<string, AdminJson>,
                  ) =>
                    (typeof a[key] === 'string'
                      ? instantTimestamp(a[key])
                      : 0) -
                    (typeof b[key] === 'string' ? instantTimestamp(b[key]) : 0),
                }),
          }),
        ),
  ),
);

const rowCommands = computed(() =>
  props.section.readonly
    ? []
    : commands.value.filter(
        (command) =>
          command.key ===
          (kind.value === 'sessions' ? 'revoke' : 'revoke_device'),
      ),
);
const tableRefreshing = ref(false);
async function refreshTable() {
  if (tableRefreshing.value || resource.busy.value) return;
  tableRefreshing.value = true;
  try {
    await props.controller.refresh();
  } finally {
    tableRefreshing.value = false;
  }
}
</script>
<template>
  <PermissionsAssignments
    v-if="
      section.key === 'runa.permissions.user_access.assignments' &&
      !presentation.field &&
      !section.fields.some((field) => field.component)
    "
    :page="page"
    :section="section"
    :controller="controller"
    @submit="
      (section, command, fields) => emit('submit', section, command, fields)
    "
  />
  <Card v-else :data-user-section="kind">
    <template #header
      ><h2>{{ labels[kind] ?? t(section.title) }}</h2></template
    >
    <Form
      :label="labels[kind] ?? t(section.title)"
      :busy="controller.busy.value"
      :valid="
        !conflicts.length &&
        !preferenceErrors &&
        !Object.keys(identityIssues).length &&
        !Object.keys(emailIssues).length
      "
      @invalid="attempted = true"
      @submit="submit"
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
            <Button :busy="controller.busy.value" size="sm" @click="retry">{{
              t('Retry loading')
            }}</Button></Alert
          >
          <div v-else class="min-w-0 space-y-5 md:col-span-2">
            <FormNotifications
              :locale="locale"
              :error="
                resource.error.value ? t(resource.error.value.message) : ''
              "
            />
            <Table
              v-if="cursor"
              v-model:query="tableQuery"
              mode="client"
              :rows="rows"
              :columns="tableColumns"
              :row-key="(row) => String(row.public_id)"
              :caption="labels[kind] ?? t(section.title)"
              :english="locale === 'en'"
              :export-allowed="section.export_allowed === true"
              :export-rows="exportRows"
              :retain-empty-frame="true"
              :refresh="refreshTable"
              :refreshing="resource.busy.value || tableRefreshing"
              :refresh-disabled="controller.busy.value"
            >
              <template v-if="kind === 'sessions'" #cell-remembered="{ row }">
                <BooleanBadge
                  :value="row.remembered === true"
                  :locale="locale"
                  :tone="
                    adminBooleanTone('remembered', row.remembered === true)
                  "
                />
              </template>
              <template v-if="rowCommands.length" #actions="{ row }">
                <Button
                  v-for="command in rowCommands"
                  :key="command.key"
                  size="sm"
                  variant="outline"
                  tone="danger"
                  :disabled="controller.busy.value || resource.busy.value"
                  @click="rowAction(row, command.key)"
                  >{{ t(command.title) }}</Button
                >
              </template>
            </Table>
            <div v-else-if="rows.length" class="space-y-3">
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
                      <BooleanBadge
                        v-if="typeof row[key] === 'boolean'"
                        :value="row[key] as boolean"
                        :locale="locale"
                        :tone="adminBooleanTone(key, row[key] as boolean)"
                      />
                      <template v-else>{{
                        key === 'key' && typeof row[key] === 'string'
                          ? t(row[key])
                          : display(row[key] ?? null, key)
                      }}</template>
                    </dd>
                  </div>
                </dl>
                <div v-if="!section.readonly" class="mt-3 flex flex-wrap gap-2">
                  <Button
                    v-if="kind === 'secondary'"
                    size="sm"
                    variant="outline"
                    :disabled="controller.busy.value"
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
                        }) as Record<string, string[]>
                      )[kind]?.includes(command.key),
                    )"
                    :key="command.key"
                    size="sm"
                    variant="outline"
                    tone="danger"
                    :disabled="controller.busy.value"
                    @click="rowAction(row, command.key)"
                    >{{ t(command.title) }}</Button
                  >
                </div>
              </div>
            </div>
            <p
              v-else-if="collectionKey && controller.records.value[section.key]"
              class="text-sm text-muted"
            >
              {{
                text(
                  {
                    secondary: 'Brak dodatkowych adresów.',
                    sessions: 'Brak aktywnych sesji.',
                    trust: 'Brak zaufanych urządzeń.',
                  }[kind as 'secondary' | 'sessions' | 'trust'],
                  {
                    secondary: 'No secondary email addresses.',
                    sessions: 'No active sessions.',
                    trust: 'No trusted devices.',
                  }[kind as 'secondary' | 'sessions' | 'trust'],
                )
              }}
            </p>
            <div
              v-if="cursor && (next || resource.result.value)"
              class="flex gap-2"
            >
              <Button
                size="sm"
                :disabled="
                  resource.busy.value ||
                  controller.busy.value ||
                  tableRefreshing
                "
                @click="refreshTable"
                >{{ t('Latest entries') }}</Button
              >
              <Button
                size="sm"
                :disabled="
                  resource.busy.value ||
                  controller.busy.value ||
                  tableRefreshing ||
                  !next
                "
                @click="older"
                >{{ t('Older entries') }}</Button
              >
            </div>
            <AuthIdentityFields
              v-if="kind === 'profile'"
              :locale="locale"
              :naming="naming"
              :columns="
                naming === 'single' && get('identifier_mode') === 'email'
                  ? 1
                  : 2
              "
              :values="identityValues"
              :errors="identityErrors"
              :show-login="get('identifier_mode') !== 'email'"
              :login-required="get('identifier_mode') === 'login'"
              :readonly="section.readonly"
              :readonly-fields="
                section.fields
                  .filter((field) => field.readonly)
                  .map((field) => field.key)
              "
              :disabled="controller.busy.value"
              @update:naming="changeNaming"
              @field="set"
              @blur="touched.add($event)"
            >
              <template
                v-for="field in customIdentityFields"
                :key="field.key"
                #[field.key]
              >
                <AdminField
                  :field="field"
                  :model-value="fieldValue(field.key)"
                  :errors="controller.errors.value[path(field.key)] ?? []"
                  :readonly="section.readonly || field.readonly"
                  :busy="controller.busy.value"
                  :components="components"
                  @update:model-value="set(field.key, $event)"
                  @availability="
                    controller.setAvailable(path(field.key), $event)
                  "
                />
              </template>
            </AuthIdentityFields>
            <RadioGroup
              v-if="
                kind === 'primary_email' &&
                emailCommands.length > 1 &&
                !section.readonly
              "
              :label="text('Sposób zmiany adresu', 'Email change method')"
              :model-value="emailCommand ?? null"
              :disabled="controller.busy.value"
              :options="
                emailCommands.map((command) => ({
                  value: command.key,
                  label: t(command.title),
                }))
              "
              @update:model-value="emailOperation = $event"
            />
            <div
              class="grid min-w-0 gap-4 sm:grid-cols-2"
              :class="kind === 'lifecycle' ? 'lg:grid-cols-3' : ''"
            >
              <template v-for="field in fields" :key="field.key">
                <TextInput
                  v-if="
                    ['primary_email', 'secondary'].includes(kind) &&
                    ['email', 'replacement_email'].includes(field.key) &&
                    !field.component &&
                    !presentation.field
                  "
                  :model-value="String(get(field.key) ?? '')"
                  :name="field.key"
                  type="email"
                  autocomplete="email"
                  :label="text('Nowy adres e-mail', 'New email address')"
                  :maxlength="254"
                  required
                  :readonly="section.readonly || field.readonly"
                  :disabled="controller.busy.value"
                  :error="emailErrors(field.key)"
                  @blur="touched.add(field.key)"
                  @update:model-value="set(field.key, $event)"
                />
                <AdminField
                  v-else
                  :field="
                    ['email', 'replacement_email'].includes(field.key)
                      ? {
                          ...field,
                          label: {
                            pl: 'Nowy adres e-mail',
                            en: 'New email address',
                          },
                        }
                      : field
                  "
                  :model-value="fieldValue(field.key)"
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
                  @availability="
                    controller.setAvailable(path(field.key), $event)
                  "
                />
              </template>
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
              <section class="space-y-3">
                <h3 class="text-sm font-semibold">
                  {{ text('Wygląd i język', 'Appearance and language') }}
                </h3>
                <AuthDisplayPreferenceFields
                  :locale="locale"
                  :language="String(get('locale') ?? 'pl')"
                  :theme="String(get('theme') ?? 'system')"
                  :disabled="preferenceReadonly || controller.busy.value"
                  @update:language="set('locale', $event)"
                  @update:theme="set('theme', $event)"
                />
              </section>
              <section
                v-if="categories.length"
                class="space-y-3 border-t border-line pt-4"
              >
                <h3 class="text-sm font-semibold">
                  {{ text('Powiadomienia e-mail', 'Email notifications') }}
                </h3>
                <div v-if="categories.length" class="divide-y divide-line">
                  <AuthEmailPreferenceFields
                    v-for="category in categories"
                    :key="String(category.module) + ':' + String(category.key)"
                    class="py-3 first:pt-0 last:pb-0"
                    :locale="locale"
                    :label="
                      category.names &&
                      typeof category.names === 'object' &&
                      !Array.isArray(category.names)
                        ? String(category.names[locale] ?? category.key)
                        : String(category.key)
                    "
                    :enabled="deliveryValue(category)?.enabled === true"
                    :recipients="recipientIds(category)"
                    :recipient-selection="
                      get('recipient_selection_enabled') === true
                    "
                    :options="recipients"
                    :disabled="preferenceReadonly || controller.busy.value"
                    :error="
                      deliveryValue(category)?.enabled &&
                      !recipientIds(category).length
                        ? text(
                            'Wybierz co najmniej jeden adres.',
                            'Select at least one address.',
                          )
                        : ''
                    "
                    @update:enabled="
                      updateDelivery(category, { enabled: $event })
                    "
                    @update:recipients="
                      updateDelivery(category, { recipients: $event })
                    "
                  />
                </div>
              </section>
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
      <template
        v-if="
          record &&
          !section.readonly &&
          ((submitCommand && (dirty || kind === 'secondary')) ||
            (kind === 'secondary' && get('address_id')))
        "
        #actions
      >
        <Button
          v-if="
            record &&
            submitCommand &&
            !section.readonly &&
            (dirty || kind === 'secondary')
          "
          type="submit"
          variant="outline"
          :busy="controller.busy.value"
          :disabled="!!conflicts.length"
          >{{
            kind === 'profile'
              ? text('Zapisz dane', 'Save details')
              : kind === 'preferences'
                ? text('Zapisz preferencje', 'Save preferences')
                : t(
                    commands.find((command) => command.key === submitCommand)
                      ?.title ?? 'Save',
                  )
          }}</Button
        >
        <Button
          v-if="dirty && !section.readonly"
          :disabled="controller.busy.value"
          @click="revert"
          >{{ text('Cofnij zmiany', 'Revert changes') }}</Button
        >
        <Button
          v-if="kind === 'secondary' && get('address_id')"
          :disabled="controller.busy.value"
          @click="
            set('address_id', null);
            set('email', '');
          "
          >{{ text('Anuluj edycję adresu', 'Cancel editing address') }}</Button
        >
      </template>
    </Form>
  </Card>
</template>
