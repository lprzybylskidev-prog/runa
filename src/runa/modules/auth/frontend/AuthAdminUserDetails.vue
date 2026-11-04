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
import AuthOperationProofFields from './AuthOperationProofFields.vue';
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
      !['directory', 'creation', 'bulk_sessions', 'bulk_users'].includes(
        section.key,
      ),
  ),
);
const groups = computed(() =>
  [
    {
      id: 'user',
      label: text('Użytkownik', 'User'),
      sections: sections.value
        .filter(
          (section) =>
            !['security', 'sessions', 'trust', 'exceptions'].includes(
              section.key,
            ),
        )
        .sort((a, b) => order(a) - order(b)),
    },
    {
      id: 'security',
      label: text('Bezpieczeństwo', 'Security'),
      sections: sections.value.filter((section) =>
        ['security', 'sessions', 'trust'].includes(section.key),
      ),
    },
    {
      id: 'rules',
      label: text('Zasady i wyjątki', 'Rules and exceptions'),
      sections: sections.value.filter(
        (section) => section.key === 'exceptions',
      ),
    },
  ].filter((group) => group.sections.length),
);
function order(section: AdminSectionData) {
  if (section.key.endsWith('.assignments')) return 8;
  const index = [
    'profile',
    'avatar',
    'lifecycle',
    'eligibility',
    'primary_email',
    'secondary',
    'invitations',
    'preferences',
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
        ['preferences', 'reset', 'Zresetuj preferencje', 'Reset preferences'],
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
const stage = ref<'parameters' | 'review' | 'proof'>('parameters');
const proofRegion = ref<HTMLElement>();
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
    section.value?.fields.filter((field) =>
      pending.value?.fields?.includes(field.key),
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
function valid() {
  if (!command.value || !section.value) return false;
  let valid = true;
  for (const key of command.value.inputs) {
    const field = section.value.fields.find((field) => field.key === key);
    if (!field || field.readonly) return false;
    const errors = validateAdminField(
      field,
      props.form.values.value[path(key)] ?? null,
    );
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
    close(false);
    await props.form.refresh();
  } else {
    stage.value = 'parameters';
    proof.value?.reset();
  }
}
async function authorize() {
  pinned.value = signature();
  if (proof.value && proof.value.requirement.value !== 'none') {
    stage.value = 'proof';
    await proof.value.begin();
    await nextTick();
    proofRegion.value?.querySelector('input')?.focus();
  } else await execute();
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
async function confirmProof() {
  if (pinned.value !== signature()) {
    stage.value = 'parameters';
    proof.value?.reset();
    return;
  }
  await proof.value?.confirm();
  if (proof.value?.confirmed.value) await execute();
}
async function rowOperation(
  key: string,
  name: string,
  values: Record<string, AdminJson>,
) {
  const target = sections.value
    .find((section) => section.key === key)
    ?.commands.find((command) => command.key === name && command.available);
  if (target)
    await open(
      [
        {
          section: key,
          command: name,
          title: target.title,
          values,
          fields: [],
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
  if (
    !target.destructive &&
    !target.preview_required &&
    target.requirement === 'none'
  )
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
const securityFacts = computed(
  () =>
    sections.value
      .find((section) => section.key === 'lifecycle')
      ?.fields.filter((field) =>
        [
          'logged_in',
          'session_count',
          'authenticated_at',
          'activity_at',
          'last_login_at',
          'last_activity_at',
          'trusted_device_count',
        ].includes(field.key),
      ) ?? [],
);
function supplied(section: AdminSectionData) {
  const known = [
    'profile',
    'avatar',
    'lifecycle',
    'eligibility',
    'primary_email',
    'secondary',
    'invitations',
    'preferences',
    'security',
    'sessions',
    'trust',
    'exceptions',
  ];
  return (
    !presentation.value.section &&
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
            <div
              class="flex flex-wrap gap-2"
              role="group"
              :aria-label="text('Akcje użytkownika', 'User actions')"
            >
              <Button
                v-for="item in toolbar"
                :key="t(item.title)"
                size="sm"
                variant="outline"
                :disabled="form.busy.value"
                @click="open(item.actions, true)"
                >{{ t(item.title) }}</Button
              >
            </div>
          </Card>
          <Card v-if="group.id === 'security' && securityFacts.length">
            <template #header
              ><h2>
                {{ text('Aktywność i logowanie', 'Activity and sign-in') }}
              </h2></template
            >
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
              <AdminField
                v-for="field in securityFacts"
                :key="field.key"
                :field="field"
                :model-value="
                  form.values.value[form.fieldPath('lifecycle', field.key)] ??
                  null
                "
                readonly
                :errors="[]"
                :busy="form.busy.value"
              />
            </div>
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
              <AdminComponentBoundary
                :identity="controller.page.value!.revision + ':' + item.key"
                :on-failure="(failed) => form.setAvailable(item.key, !failed)"
              >
                <AuthAdminUserSection
                  v-if="supplied(item)"
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
      :busy="form.busy.value"
      @update:model-value="!$event && close()"
    >
      <Form
        v-if="pending"
        :label="t(pending.title)"
        :busy="form.busy.value"
        @submit="stage === 'proof' ? confirmProof() : submit()"
      >
        <div
          v-if="stage === 'proof' && proof"
          ref="proofRegion"
          class="space-y-4"
        >
          <h2 class="font-semibold">
            {{ text('Potwierdź tożsamość', 'Confirm your identity') }}
          </h2>
          <AuthOperationProofFields
            :controller="proof"
            :locale="locale"
            submit
            @cancel="
              proof.reset();
              stage = 'parameters';
            "
          />
        </div>
        <div v-else class="space-y-4">
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
          <p class="text-sm text-muted">
            {{
              text(
                'Operacja dotyczy wyłącznie tego użytkownika.',
                'This operation applies only to this user.',
              )
            }}
          </p>
          <AdminField
            v-for="field in fields"
            :key="field.key"
            :field="
              field.key === 'at'
                ? {
                    ...field,
                    label: {
                      pl: 'Termin (puste usuwa termin)',
                      en: 'Deadline (empty clears)',
                    },
                  }
                : field
            "
            :model-value="form.values.value[path(field.key)] ?? null"
            :errors="form.errors.value[path(field.key)] ?? []"
            :busy="form.busy.value"
            :readonly="field.readonly"
            @update:model-value="form.set(path(field.key), $event)"
          />
          <div
            v-if="stage === 'review' && review"
            class="rounded-lg border border-line p-4 text-sm"
          >
            <p class="font-semibold">
              {{
                text(
                  'Sprawdź zmiany przed zatwierdzeniem.',
                  'Review the changes before confirming.',
                )
              }}
            </p>
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
            :error="
              stage === 'proof'
                ? (proof?.error.value ?? '')
                : t(form.error.value?.message ?? '')
            "
        /></template>
        <template #actions>
          <template v-if="stage !== 'proof'"
            ><Button type="submit" variant="outline">{{
              command?.preview_required && !review
                ? text('Sprawdź zmiany', 'Review changes')
                : text('Zatwierdź', 'Confirm')
            }}</Button
            ><Button tone="danger" variant="outline" @click="close()">{{
              t('Cancel')
            }}</Button></template
          >
        </template>
      </Form>
    </Dialog>
    <ConfirmDialog
      :locale="locale"
      :model-value="!!form.confirmation.value"
      :title="t(form.confirmation.value?.title ?? 'Confirm')"
      :description="t(form.confirmation.value?.description ?? '')"
      @confirm="form.confirm(true)"
      @cancel="form.confirm(false)"
      @update:model-value="!$event && form.confirm(false)"
    />
  </div>
</template>
