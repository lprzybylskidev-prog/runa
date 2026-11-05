<script setup lang="ts">
import { computed, nextTick, onScopeDispose, ref, watch } from 'vue';
import {
  AdminComponentBoundary,
  AdminField,
  AdminSectionContent,
  useAdminLocale,
  useAdminPresentation,
  validateAdminField,
} from '@/runa/admin';
import type {
  AdminJson,
  AdminFieldData,
  AdminPageProps,
  AdminSectionData,
  AdminText,
} from '@/runa/admin';
import {
  Button,
  Card,
  ConfirmDialog,
  Dialog,
  Form,
  FormNotifications,
  Select,
  Tabs,
} from '@/runa/ui';
import AuthAdminActionGroups from './AuthAdminActionGroups.vue';
import {
  groupAuthActions,
  authActionIntent,
  authActionGroupIntent,
} from './bulkGroups';
import AuthAdminUserStatus from './AuthAdminUserStatus.vue';
import AuthAdminPolicyExceptions from './AuthAdminPolicyExceptions.vue';
import { policyRuleNames, usePolicyExceptions } from './policyExceptions';
import AuthAdminEmails from './AuthAdminEmails.vue';
import AuthAdminUserSection from './AuthAdminUserSection.vue';
const props = defineProps<AdminPageProps>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const presentation = useAdminPresentation();
const region = ref<HTMLElement>();
const tab = ref('user');
const visited = ref(new Set(['user']));
watch(tab, (value) => {
  visited.value = new Set([...visited.value, value]);
});
const sections = computed(() =>
  (props.controller.page.value?.sections ?? []).filter(
    (section) =>
      ![
        'directory',
        'creation',
        'bulk_sessions',
        'bulk_users',
        'runa.permissions.user_authority_bulk.bulk_authority',
      ].includes(section.key),
  ),
);
const statusSources = computed(() =>
  sections.value
    .filter(
      (section) =>
        ['lifecycle', 'primary_email', 'security', 'invitations'].includes(
          section.key,
        ) && supplied(section),
    )
    .sort(
      (a, b) =>
        ['lifecycle', 'primary_email', 'security', 'invitations'].indexOf(
          a.key,
        ) -
        ['lifecycle', 'primary_email', 'security', 'invitations'].indexOf(
          b.key,
        ),
    ),
);
const emailSources = computed(() =>
  sections.value.filter(
    (section) =>
      ['primary_email', 'secondary'].includes(section.key) && supplied(section),
  ),
);
const presented = computed(() => {
  const list = sections.value.filter(
    (section) =>
      section.key !== 'runa.permissions.user_authority.authority' &&
      (![
        'lifecycle',
        'eligibility',
        'invitations',
        'security',
        'primary_email',
        'secondary',
      ].includes(section.key) ||
        !supplied(section)),
  );
  const source = statusSources.value[0];
  if (source)
    list.push({
      ...source,
      key: 'status',
      fields: [],
      commands: [],
      component: null,
    });
  const email = emailSources.value[0];
  if (email)
    list.push({
      ...email,
      key: 'emails',
      fields: [],
      commands: [],
      component: null,
    });
  return list;
});
const groups = computed(() =>
  [
    {
      id: 'user',
      label: text('Użytkownik', 'User'),
      sections: presented.value
        .filter(
          (section) => !['security', 'sessions', 'trust'].includes(section.key),
        )
        .sort((a, b) => order(a) - order(b)),
    },
    {
      id: 'security',
      label: text('Bezpieczeństwo', 'Security'),
      sections: presented.value.filter((section) =>
        ['security', 'sessions', 'trust'].includes(section.key),
      ),
    },
  ].filter((group) => group.sections.length),
);
function order(section: AdminSectionData) {
  if (section.key.endsWith('.assignments')) return 8;
  const index = [
    'profile',
    'avatar',
    'status',
    'emails',
    'preferences',
    'exceptions',
  ].indexOf(section.key);
  return index < 0 ? 10 : section.key === 'preferences' ? 9 : index;
}
watch(
  groups,
  (value) => {
    if (!value.some((group) => group.id === tab.value))
      tab.value = value[0]?.id ?? '';
  },
  { immediate: true },
);
type Action = {
  section: string;
  command: string;
  title: AdminText;
  values?: Record<string, AdminJson>;
  fields?: string[];
};
function actionKey(action: Action): string {
  if (action.section === 'runa.permissions.user_authority.authority')
    return action.values?.grant ? 'grant_superadmin' : 'revoke_superadmin';
  if (typeof action.values?.operation === 'string')
    return action.values.operation;
  return (
    (
      {
        'invitations.invalidate': 'invalidate_invitation',
        'primary_email.require_verification': 'require_verification',
        'security.reset': 'factor_reset',
        'security.reset_password': 'password_reset',
        'sessions.revoke_all': 'revoke',
        'trust.revoke_all': 'revoke_trust',
        'preferences.reset': 'reset_preferences',
        'exceptions.revoke_all': 'revoke_exceptions',
      } as Record<string, string>
    )[`${action.section}.${action.command}`] ?? action.command
  );
}
function actionGroupIntent(actions: readonly Action[]) {
  return authActionGroupIntent(
    actions.map((action) =>
      authActionIntent(
        actionKey(action),
        typeof action.values?.enabled === 'boolean'
          ? action.values.enabled
          : null,
        action.values?.at === null && !action.fields?.includes('at'),
      ),
    ),
  );
}
const definition = (action: Action) =>
  sections.value
    .find((section) => section.key === action.section)
    ?.commands.find(
      (command) => command.key === action.command && command.available,
    );
function life(
  operation: string,
  pl: string,
  en: string,
  values: Record<string, AdminJson> = {},
  fields: string[] = [],
): Action {
  return {
    section: 'lifecycle',
    command: 'change',
    title: { pl, en },
    values: { operation, at: null, enabled: true, ...values },
    fields,
  };
}
const toolbar = computed(() => {
  const items: { title: AdminText; actions: Action[] }[] = [
    {
      title: { pl: 'Superadministrator', en: 'Super administrator' },
      actions: [
        {
          section: 'runa.permissions.user_authority.authority',
          command: 'change',
          title: {
            pl: 'Nadaj superadministratora',
            en: 'Grant super administrator',
          },
          values: { grant: true },
        },
        {
          section: 'runa.permissions.user_authority.authority',
          command: 'change',
          title: {
            pl: 'Odbierz superadministratora',
            en: 'Revoke super administrator',
          },
          values: { grant: false },
        },
      ],
    },
    {
      title: { pl: 'Aktywność konta', en: 'Account activity' },
      actions: [
        life('activate', 'Aktywuj konto', 'Activate account'),
        life('deactivate', 'Dezaktywuj konto', 'Deactivate account'),
      ],
    },
    {
      title: { pl: 'Terminy aktywności', en: 'Activity schedules' },
      actions: [
        life(
          'schedule_activation',
          'Zaplanuj aktywację',
          'Schedule activation',
          {},
          ['at'],
        ),
        life(
          'schedule_deactivation',
          'Zaplanuj dezaktywację',
          'Schedule deactivation',
          {},
          ['at'],
        ),
      ],
    },
    {
      title: { pl: 'Usuwanie i przywracanie', en: 'Deletion and restoration' },
      actions: [
        life('delete', 'Usuń konto', 'Delete account'),
        life('restore', 'Przywróć konto', 'Restore account'),
      ],
    },
    {
      title: { pl: 'Zaproszenia', en: 'Invitations' },
      actions: [
        {
          section: 'invitations',
          command: 'invite',
          title: {
            pl: 'Wyślij lub ponów zaproszenie',
            en: 'Send or resend invitation',
          },
        },
        {
          section: 'invitations',
          command: 'invalidate',
          title: { pl: 'Unieważnij zaproszenie', en: 'Invalidate invitation' },
        },
      ],
    },
    {
      title: { pl: 'Blokada prób', en: 'Attempt lock' },
      actions: [
        life(
          'set_attempt_lock',
          'Ustaw blokadę do terminu',
          'Lock until a deadline',
          {},
          ['at'],
        ),
        life('set_attempt_lock', 'Usuń blokadę prób', 'Clear attempt lock'),
      ],
    },
    {
      title: { pl: 'Blokada bezpieczeństwa', en: 'Security lock' },
      actions: [
        life(
          'set_security_lock',
          'Włącz blokadę bezpieczeństwa',
          'Enable security lock',
        ),
        life(
          'set_security_lock',
          'Wyłącz blokadę bezpieczeństwa',
          'Disable security lock',
          { enabled: false },
        ),
      ],
    },
    {
      title: { pl: 'Zmiana hasła', en: 'Password change' },
      actions: [
        life(
          'require_password_change',
          'Wymuś zmianę hasła',
          'Force password change',
        ),
        life(
          'require_password_change',
          'Cofnij wymuszenie zmiany hasła',
          'Clear forced password change',
          { enabled: false },
        ),
      ],
    },
    ...(
      [
        [
          'primary_email',
          'require_verification',
          'Wymagaj weryfikacji adresu',
          'Require email verification',
        ],
        [
          'security',
          'password_reset',
          'Zleć reset hasła',
          'Request password reset',
        ],
        ['security', 'factor_reset', 'Zresetuj 2FA', 'Reset second factors'],
        [
          'sessions',
          'revoke_all',
          'Wyloguj ze wszystkich sesji',
          'Revoke all sessions',
        ],
        [
          'trust',
          'revoke_all',
          'Cofnij zaufanie urządzeniom',
          'Revoke all trusted devices',
        ],
        [
          'exceptions',
          'revoke_all',
          'Odwołaj wszystkie wyjątki',
          'Revoke all exceptions',
        ],
        [
          'preferences',
          'reset',
          'Przywróć preferencje widoku',
          'Reset view preferences',
        ],
      ] as const
    ).map(([section, command, pl, en]) => ({
      title: { pl, en },
      actions: [{ section, command, title: { pl, en }, fields: [] }],
    })),
  ];
  const operations =
    sections.value
      .find((section) => section.key === 'lifecycle')
      ?.fields.find((field) => field.key === 'operation')?.choices ?? [];
  return items
    .map((item) => ({
      ...item,
      actions: item.actions.filter(
        (action) =>
          !!definition(action) &&
          (!action.values?.operation ||
            operations.includes(String(action.values.operation))),
      ),
    }))
    .filter((item) => item.actions.length);
});
const actionGroups = computed(() =>
  groupAuthActions(toolbar.value, (item) => actionKey(item.actions[0]!)),
);
const actions = ref<Action[]>([]);
const previous = new Map<string, AdminJson>();
let restoreParameters = false;
const selected = ref('0');
const pending = computed(() => actions.value[Number(selected.value)]);
const section = computed(() =>
  sections.value.find((section) => section.key === pending.value?.section),
);
const command = computed(() =>
  pending.value ? definition(pending.value) : undefined,
);
const proof = computed(() =>
  pending.value
    ? props.form.proof(pending.value.section, pending.value.command)
    : undefined,
);
const stage = ref<'parameters' | 'review'>('parameters');
const pinned = ref('');
const attempt = ref(0);
const path = (key: string) =>
  props.form.fieldPath(pending.value?.section ?? '', key);
const signature = () =>
  JSON.stringify([
    pending.value?.section,
    pending.value?.command,
    props.form.records.value[pending.value?.section ?? '']?.revision,
    command.value?.inputs.map((key) => props.form.values.value[path(key)]),
  ]);
const fields = computed(
  () =>
    section.value?.fields
      .filter((field) => pending.value?.fields?.includes(field.key))
      .sort((a, b) =>
        pending.value?.section === 'exceptions'
          ? pending.value.fields!.indexOf(a.key) -
            pending.value.fields!.indexOf(b.key)
          : 0,
      ) ?? [],
);
const review = computed(() =>
  pending.value
    ? props.form.commandReviews.value[
        `${pending.value.section}:${pending.value.command}`
      ]
    : undefined,
);
function close(restore = true) {
  if (props.form.busy.value) return;
  proof.value?.reset();
  if (restore)
    for (const [path, value] of previous) props.form.set(path, value);
  previous.clear();
  actions.value = [];
  stage.value = 'parameters';
  pinned.value = '';
}
async function select(value: string) {
  proof.value?.reset();
  selected.value = value;
  stage.value = 'parameters';
  const action = pending.value;
  if (!action) return;
  await props.form.activateSection(action.section);
  if (restoreParameters)
    for (const key of definition(action)?.inputs ?? []) {
      const fieldPath = props.form.fieldPath(action.section, key);
      if (!previous.has(fieldPath))
        previous.set(fieldPath, props.form.values.value[fieldPath] ?? null);
    }
  for (const [key, value] of Object.entries(action.values ?? {}))
    props.form.set(props.form.fieldPath(action.section, key), value);
}
async function open(items: Action[], restore = false) {
  restoreParameters = restore;
  previous.clear();
  actions.value = items;
  selected.value = '0';
  stage.value = 'parameters';
  await select('0');
}
function operationField(field: AdminFieldData): AdminFieldData {
  if (pending.value?.section === 'exceptions') {
    const key = String(pending.value.values?.key);
    if (field.key === 'value')
      return {
        ...field,
        required: pending.value.fields?.includes('value') === true,
        label:
          key === 'password_expiry_days'
            ? {
                pl: 'Ważność hasła (dni, 1–3650)',
                en: 'Password lifetime (days, 1–3650)',
              }
            : {
                pl: 'Limit bezczynności (min, 1–43200)',
                en: 'Idle limit (min, 1–43200)',
              },
      };
    if (field.key === 'expires_at')
      return {
        ...field,
        required: false,
        label: {
          pl: 'Obowiązuje do (opcjonalnie)',
          en: 'Applies until (optional)',
        },
      };
    if (field.key === 'reason') return { ...field, required: true };
  }
  if (pending.value?.section !== 'lifecycle' || field.key !== 'at')
    return field;
  const operation = pending.value?.values?.operation;
  return {
    ...field,
    required:
      operation === 'set_attempt_lock' &&
      !!pending.value?.fields?.includes('at'),
    label:
      operation === 'schedule_activation'
        ? { pl: 'Termin aktywacji', en: 'Activation date' }
        : operation === 'schedule_deactivation'
          ? { pl: 'Termin dezaktywacji', en: 'Deactivation date' }
          : { pl: 'Blokada prób do', en: 'Sign-in attempt lock until' },
  };
}
function valid() {
  if (!command.value || !section.value) return false;
  let valid = true;
  for (const key of command.value.inputs) {
    const field = section.value.fields.find((field) => field.key === key);
    if (!field || field.readonly) return false;
    const errors = [
      ...validateAdminField(
        operationField(field),
        props.form.values.value[path(key)] ?? null,
      ),
    ];
    if (pending.value?.section === 'exceptions') {
      const value = props.form.values.value[path(key)];
      if (
        key === 'value' &&
        pending.value.fields?.includes('value') &&
        typeof value === 'number'
      ) {
        const max =
          pending.value.values?.key === 'password_expiry_days' ? 3650 : 43200;
        if (value < 1 || value > max)
          errors.push(
            text(
              `Podaj wartość od 1 do ${max}.`,
              `Enter a value between 1 and ${max}.`,
            ),
          );
      }
      if (key === 'expires_at' && typeof value === 'string' && value !== '') {
        const at = Date.parse(value);
        if (!Number.isFinite(at) || at <= Date.now())
          errors.push(
            text(
              'Wybierz przyszły termin albo pozostaw pole puste.',
              'Choose a future date or leave the field empty.',
            ),
          );
      }
      if (
        key === 'reason' &&
        typeof value === 'string' &&
        (!value.trim() ||
          new TextEncoder().encode(value).length > 1000 ||
          /[\x00-\x1f]/.test(value))
      )
        errors.push(
          text(
            'Podaj powód w jednym wierszu, do 1000 bajtów.',
            'Enter a single-line reason, up to 1000 bytes.',
          ),
        );
    }
    props.form.errors.value[path(key)] = errors;
    if (errors.length) valid = false;
  }
  return valid;
}
async function execute() {
  if (!pending.value || pinned.value !== signature()) {
    stage.value = 'parameters';
    proof.value?.reset();
    return;
  }
  const result = await props.form.execute(
    pending.value.section,
    pending.value.command,
    { confirmReviewed: true },
  );
  if (result?.status === 'committed') {
    const emailDraft =
      restoreParameters &&
      pending.value?.section === 'secondary' &&
      pending.value.command === 'change'
        ? new Map(previous)
        : undefined;
    close(false);
    await props.form.refresh();
    if (emailDraft && props.controller.state.value === 'ready')
      for (const [key, value] of emailDraft) props.form.set(key, value);
  } else {
    stage.value = 'parameters';
    proof.value?.reset();
  }
}
async function authorize() {
  pinned.value = signature();
  await execute();
}
async function submit() {
  attempt.value++;
  if (!valid()) return;
  if (command.value?.preview_required && !review.value) {
    if (
      !(await props.form.reviewCommand(
        pending.value!.section,
        pending.value!.command,
      ))
    )
      return;
    stage.value = 'review';
    return;
  }
  await authorize();
}
const exceptionState = usePolicyExceptions(props.form, 'exceptions');
async function rowOperation(
  key: string,
  name: string,
  values: Record<string, AdminJson>,
) {
  const target = sections.value
    .find((section) => section.key === key)
    ?.commands.find((command) => command.key === name && command.available);
  const exception = key === 'exceptions' && name === 'set';
  const revoke = exception && values.revoke === true;
  if (exception) {
    values = { ...values };
    delete values.revoke;
  }
  if (exception) {
    values = {
      ...values,
      exception_version: exceptionState.version(String(values.key)),
      policy_revision: exceptionState.get('policy_revision'),
    };
  }
  const rule = policyRuleNames[String(values.key)] ?? String(values.key);
  const ruleLabel = (language: 'pl' | 'en') =>
    typeof rule === 'string' ? rule : rule[language];
  if (target)
    await open(
      [
        {
          section: key,
          command: name,
          title: exception
            ? {
                pl: `${revoke ? 'Przywróć zasadę ogólną' : 'Ustaw własną zasadę'}: ${ruleLabel('pl')}`,
                en: `${revoke ? 'Restore global rule' : 'Set custom rule'}: ${ruleLabel('en')}`,
              }
            : target.title,
          values,
          fields: exception
            ? revoke
              ? ['reason']
              : ['value', 'expires_at', 'reason']
            : [],
        },
      ],
      true,
    );
}
async function submitForm(key: string, name: string, fields: string[] = []) {
  const target = sections.value
    .find((section) => section.key === key)
    ?.commands.find((command) => command.key === name && command.available);
  if (!target) return;
  await open([{ section: key, command: name, title: target.title, fields }]);
  if (!valid()) {
    close();
    props.form.error.value = {
      reason: 'validation_failed',
      message: 'Check the highlighted fields.',
    };
    await nextTick();
    region.value?.querySelector<HTMLElement>('[aria-invalid=true]')?.focus();
    return;
  }
  await submit();
}
watch(
  () => props.controller.state.value,
  (value) => {
    if (value !== 'ready') {
      proof.value?.reset();
      actions.value = [];
    }
  },
);
watch(command, (value) => {
  if (!value) {
    proof.value?.reset();
    actions.value = [];
  }
});
onScopeDispose(() => proof.value?.reset());
void props.form.activateSection('lifecycle');

function supplied(section: AdminSectionData) {
  const known = [
    'profile',
    'avatar',
    'lifecycle',
    'eligibility',
    'primary_email',
    'secondary',
    'invitations',
    'status',
    'emails',
    'preferences',
    'security',
    'sessions',
    'trust',
    'exceptions',
  ];
  return (
    !presentation.value.section &&
    (!['primary_email', 'secondary', 'exceptions'].includes(section.key) ||
      (!presentation.value.field &&
        !section.fields.some((field) => field.component))) &&
    !section.diagnostic &&
    (section.component === null ||
      section.component?.key === 'runa.auth.admin_section') &&
    (known.includes(section.key) || section.key.endsWith('.assignments'))
  );
}
</script>
<template>
  <div ref="region" class="min-w-0 space-y-5" data-auth-user-details>
    <FormNotifications
      v-if="!pending"
      :locale="locale"
      :error="t(form.error.value?.message ?? '')"
      :success="t(form.notice.value)"
    />
    <Tabs
      v-model="tab"
      :items="groups.map(({ id, label }) => ({ id, label }))"
      :label="text('Szczegóły użytkownika', 'User details')"
    >
      <template v-for="group in groups" :key="group.id" #[group.id]>
        <div
          v-if="visited.has(group.id)"
          class="flex min-w-0 flex-col gap-5 pt-4"
        >
          <Card v-if="group.id === 'user' && toolbar.length">
            <template #header
              ><h2>{{ text('Akcje', 'Actions') }}</h2></template
            >
            <AuthAdminActionGroups :groups="actionGroups">
              <template #default="{ item }">
                <Button
                  :intent="actionGroupIntent(item.actions)"
                  size="sm"
                  variant="outline"
                  :disabled="form.busy.value"
                  @click="open(item.actions, true)"
                  >{{ t(item.title) }}</Button
                >
              </template>
            </AuthAdminActionGroups>
          </Card>

          <div
            :class="
              group.id === 'user'
                ? 'grid min-w-0 gap-5 lg:grid-cols-2'
                : 'flex min-w-0 flex-col gap-5'
            "
          >
            <div
              v-for="item in group.sections"
              :key="item.key"
              class="min-w-0"
              :class="
                group.id === 'user' && !['profile', 'avatar'].includes(item.key)
                  ? 'lg:col-span-2'
                  : ''
              "
            >
              <AuthAdminUserStatus
                v-if="item.key === 'status'"
                :page="controller"
                :controller="form"
                :section="item"
                :sources="statusSources"
                :components="components"
                :exceptions-source="
                  sections.find(
                    (source) => source.key === 'exceptions' && supplied(source),
                  )
                "
              />
              <AuthAdminEmails
                v-else-if="item.key === 'emails'"
                :page="controller"
                :controller="form"
                :section="item"
                :sources="emailSources"
                @submit="submitForm"
                @operation="rowOperation"
              />
              <AdminComponentBoundary
                v-else
                :identity="controller.page.value!.revision + ':' + item.key"
                :on-failure="(failed) => form.setAvailable(item.key, !failed)"
              >
                <AuthAdminPolicyExceptions
                  v-if="item.key === 'exceptions' && supplied(item)"
                  :page="controller"
                  :controller="form"
                  :section="item"
                  @operation="rowOperation"
                />
                <AuthAdminUserSection
                  v-else-if="supplied(item)"
                  :page="controller"
                  :controller="form"
                  :section="item"
                  :components="components"
                  @submit="submitForm"
                  @operation="rowOperation"
                />
                <AdminSectionContent
                  v-else
                  :page="controller"
                  :controller="form"
                  :section="item"
                  :components="components"
                />
              </AdminComponentBoundary>
            </div>
          </div>
        </div>
      </template>
    </Tabs>
    <Dialog
      :model-value="!!pending"
      :title="pending ? t(pending.title) : ''"
      :locale="locale"
      size="md"
      :busy="form.busy.value || proof?.busy.value === true"
      @update:model-value="!$event && close()"
    >
      <Form
        v-if="pending"
        :label="t(pending.title)"
        :busy="form.busy.value || proof?.busy.value === true"
        @submit="submit"
      >
        <div class="flex min-w-0 flex-col gap-4">
          <p
            v-if="
              pending?.section === 'primary_email' &&
              pending.command === 'verify'
            "
            class="text-sm text-muted"
          >
            {{
              text(
                'Potwierdzenie głównego adresu wyloguje użytkownika ze wszystkich sesji i cofnie zaufanie urządzeniom.',
                'Verifying the primary address signs the user out of all sessions and revokes trusted devices.',
              )
            }}
          </p>
          <Select
            v-if="actions.length > 1"
            :label="text('Operacja', 'Operation')"
            :model-value="selected"
            :options="
              actions.map((action, index) => ({
                value: String(index),
                label: t(action.title),
              }))
            "
            :locale="locale"
            @update:model-value="select"
          />
          <p
            v-if="
              pending?.section === 'preferences' && pending.command === 'reset'
            "
            class="text-sm text-muted"
          >
            {{
              text(
                'Przywraca domyślny wygląd, język oraz ustawienia menu i tabel. Zachowuje ustawienia powiadomień e-mail.',
                'Restores default appearance, language, menu and table preferences. Keeps email notification settings.',
              )
            }}
          </p>
          <div
            v-for="field in fields"
            :key="field.key"
            class="min-w-0 space-y-2"
            :data-operation-field="field.key"
          >
            <AdminField
              :field="operationField(field)"
              :model-value="form.values.value[path(field.key)] ?? null"
              :errors="form.errors.value[path(field.key)] ?? []"
              :busy="form.busy.value"
              :readonly="field.readonly"
              @update:model-value="form.set(path(field.key), $event)"
            />
            <p
              v-if="
                pending.section === 'exceptions' && field.key === 'expires_at'
              "
              class="text-xs text-muted"
            >
              {{
                text(
                  'Pozostaw puste, aby zasada obowiązywała bezterminowo.',
                  'Leave empty for an indefinite rule.',
                )
              }}
            </p>
          </div>
          <p
            v-if="
              fields.some((field) => field.key === 'at') &&
              ['schedule_activation', 'schedule_deactivation'].includes(
                String(pending?.values?.operation),
              )
            "
            class="text-sm text-muted"
          >
            {{
              text(
                'Pozostaw termin pusty, aby usunąć zaplanowaną datę.',
                'Leave the date empty to remove the schedule.',
              )
            }}
          </p>
          <div
            v-if="stage === 'review' && review"
            class="rounded-lg border border-line p-4 text-sm"
          >
            <p v-for="(change, key) in review.changes" :key="key" class="mt-2">
              {{ t(String(key)) }}:
              {{
                change &&
                typeof change === 'object' &&
                !Array.isArray(change) &&
                Array.isArray(change.new)
                  ? change.new.length
                  : '✓'
              }}
            </p>
          </div>
        </div>
        <template #feedback
          ><FormNotifications
            :locale="locale"
            :attempt="attempt"
            :error="t(form.error.value?.message ?? '')"
        /></template>
        <template #actions>
          <Button
            :intent="
              command?.preview_required && !review
                ? 'inspect'
                : pending
                  ? authActionIntent(
                      actionKey(pending),
                      typeof form.values.value[path('enabled')] === 'boolean'
                        ? (form.values.value[path('enabled')] as boolean)
                        : null,
                      form.values.value[path('at')] === null,
                    )
                  : 'commit'
            "
            type="submit"
            variant="outline"
            >{{
              command?.preview_required && !review
                ? text('Sprawdź zmiany', 'Review changes')
                : text('Zatwierdź', 'Confirm')
            }}</Button
          ><Button intent="cancel" @click="close()">{{ t('Cancel') }}</Button>
        </template>
      </Form>
    </Dialog>
    <ConfirmDialog
      :locale="locale"
      :model-value="!!form.confirmation.value"
      :title="t(form.confirmation.value?.title ?? 'Confirm')"
      :description="t(form.confirmation.value?.description ?? '')"
      :confirm-intent="form.confirmation.value?.intent"
      @confirm="form.confirm(true)"
      @cancel="form.confirm(false)"
      @update:model-value="!$event && form.confirm(false)"
    />
  </div>
</template>
