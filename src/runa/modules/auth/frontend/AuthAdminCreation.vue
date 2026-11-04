<script setup lang="ts">
import { computed, nextTick, onScopeDispose, reactive, ref, watch } from 'vue';
import { useAdminLocale } from '@/runa/admin';
import type {
  AdminJson,
  AdminSectionPresentationProps,
  AdminComponentRegistry,
} from '@/runa/admin';
import { AuthOperationProof } from '@/runa/auth';
import {
  Badge,
  Button,
  Checkbox,
  ContentLoading,
  DateTimeInput,
  Form,
  FormNotifications,
  FormSection,
  RadioGroup,
  TextInput,
  ViewBoundary,
  formatApplicationInstant,
  useViewReadiness,
} from '@/runa/ui';
import type { EntryOptions } from './entry';

const props = defineProps<
  AdminSectionPresentationProps & {
    components?: AdminComponentRegistry | undefined;
  }
>();
const { locale, t } = useAdminLocale();
const text = (pl: string, en: string) => t({ pl, en });
const form = ref<InstanceType<typeof Form>>();
const attempt = ref(0);
const naming = ref('full');
const activation = ref('inactive');
const deactivation = ref(false);
const advanced = ref(true);
const touched = reactive(new Set<string>());
const temporalValid = reactive<Record<string, boolean>>({});
const record = computed(
  () => props.controller.records.value[props.section.key],
);
const initialSettled = ref(false);
async function read(retry = false) {
  initialSettled.value = false;
  try {
    if (retry) await props.controller.refresh();
    else await props.controller.activateSection(props.section.key);
  } finally {
    initialSettled.value = true;
  }
}
void read();
const readiness = useViewReadiness({
  identity: () =>
    props.page.state.value === 'ready'
      ? (props.page.page.value?.revision ?? null)
      : null,
  required: () => !!record.value,
  failed: () => initialSettled.value && !record.value,
});
const command = computed(() =>
  props.section.commands.find((c) => c.key === 'create'),
);
const creationProof = props.controller.proof(props.section.key, 'create');
onScopeDispose(() => creationProof.reset());
const busy = computed(() => props.controller.busy.value);
const readonly = computed(
  () => props.section.readonly || !command.value?.available,
);
const path = (key: string) =>
  props.controller.fieldPath(props.section.key, key);
const get = (key: string): AdminJson =>
  props.controller.values.value[path(key)] ?? null;
const string = (key: string) =>
  typeof get(key) === 'string' ? (get(key) as string) : '';
function set(key: string, value: AdminJson) {
  props.controller.set(path(key), value);
}
const credentials = computed(() => string('credential_mode'));
const canInvite = computed(() => get('invitation_allowed') === true);
const canVerify = computed(() => get('email_verification_allowed') === true);
const policy = computed<EntryOptions['password'] | undefined>(() => {
  try {
    const value: unknown = JSON.parse(string('password_policy'));
    if (
      !value ||
      typeof value !== 'object' ||
      !('min_length' in value) ||
      !('max_bytes' in value) ||
      typeof value.min_length !== 'number' ||
      value.max_bytes !== 72
    )
      return;
    return value as EntryOptions['password'];
  } catch {
    return;
  }
});
const passwordHint = computed(() => {
  const p = policy.value;
  if (!p) return '';
  return [
    text(
      `Co najmniej ${p.min_length} znaków, maksymalnie 72 bajty.`,
      `At least ${p.min_length} characters, at most 72 bytes.`,
    ),
    ...(p.mixed_case
      ? [text('Wielkie i małe litery.', 'Uppercase and lowercase letters.')]
      : []),
    ...(p.numbers
      ? [text('Co najmniej jedna cyfra.', 'At least one number.')]
      : []),
    ...(p.symbols
      ? [text('Co najmniej jeden symbol.', 'At least one symbol.')]
      : []),
  ].join(' ');
});
watch(record, () => {
  if (!props.controller.dirty.value) {
    activation.value =
      get('active') === true
        ? 'active'
        : string('activate_at')
          ? 'scheduled'
          : 'inactive';
    deactivation.value = !!string('deactivate_at');
    touched.clear();
  }
});
function changeNaming(value: string) {
  naming.value = value;
  for (const key of value === 'full' ? ['name'] : ['first_name', 'surname'])
    set(key, null);
}
function changeActivation(value: string) {
  activation.value = value;
  set('active', value === 'active');
  if (value !== 'scheduled') {
    set('activate_at', null);
    temporalValid.activate_at = true;
  }
}
function changeCredentials(value: string) {
  set('credential_mode', value);
  set('password', { set: false });
  set('password_confirmation', { set: false });
  if (value !== 'password') set('password_change_required', false);
}
const issues = computed<Record<string, string>>(() => {
  const errors: Record<string, string> = {};
  const required = [
    'email',
    ...(naming.value === 'full' ? ['first_name', 'surname'] : ['name']),
    ...(string('identifier_mode') === 'login' ? ['login'] : []),
    ...(activation.value === 'scheduled' ? ['activate_at'] : []),
    ...(deactivation.value ? ['deactivate_at'] : []),
  ];
  for (const key of required)
    if (!string(key).trim())
      errors[key] = text('Wypełnij to pole.', 'Complete this field.');
  if (
    string('email') &&
    (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(string('email')) ||
      string('email').length > 254)
  )
    errors.email = text(
      'Podaj poprawny adres e-mail.',
      'Enter a valid email address.',
    );
  for (const key of naming.value === 'full'
    ? ['first_name', 'surname']
    : ['name']) {
    const value = string(key);
    if (
      value &&
      (value.trim() !== value ||
        [...value].length > 160 ||
        /\p{C}/u.test(value))
    )
      errors[key] = text('Popraw dane.', 'Correct this value.');
  }
  if (
    string('login') &&
    !/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,63}$/.test(string('login'))
  )
    errors.login = text('Niepoprawny login.', 'Invalid login.');
  for (const key of ['activate_at', 'deactivate_at', 'attempt_locked_until']) {
    if (
      temporalValid[key] === false ||
      (string(key) &&
        (!Number.isFinite(Date.parse(string(key))) ||
          Date.parse(string(key)) <= Date.now()))
    )
      errors[key] = text(
        'Wybierz poprawny termin w przyszłości.',
        'Choose a valid future date.',
      );
  }
  if (
    string('activate_at') &&
    string('deactivate_at') &&
    Date.parse(string('activate_at')) >= Date.parse(string('deactivate_at'))
  )
    errors.deactivate_at = text(
      'Dezaktywacja musi nastąpić po aktywacji.',
      'Deactivation must follow activation.',
    );
  if (
    credentials.value === 'invitation' &&
    (!canInvite.value ||
      get('security_locked') === true ||
      !!string('attempt_locked_until'))
  )
    errors.credential_mode = text(
      'Zaproszenie wymaga konta bez blokady bezpieczeństwa i blokady logowania.',
      'Invitations require an account without security or login locks.',
    );
  if (get('email_verified') === true && !canVerify.value)
    errors.email_verified = text(
      'Brak uprawnienia lub zgody polityki Auth.',
      'The Auth policy or your permission does not allow this.',
    );
  if (credentials.value === 'password') {
    const p = policy.value,
      value = string('password');
    if (
      !p ||
      [...value].length < p.min_length ||
      new TextEncoder().encode(value).length > p.max_bytes ||
      value.includes('\0') ||
      (p.mixed_case && (!/\p{Ll}/u.test(value) || !/\p{Lu}/u.test(value))) ||
      (p.numbers && !/\p{N}/u.test(value)) ||
      (p.symbols && !/[\p{S}\p{P}]/u.test(value))
    )
      errors.password = text(
        'Hasło nie spełnia podanych wymagań.',
        'The password does not meet the stated requirements.',
      );
    if (value !== string('password_confirmation'))
      errors.password_confirmation = text(
        'Hasła muszą być jednakowe.',
        'Passwords must match.',
      );
  }
  return errors;
});
function errors(key: string): string[] {
  return [
    ...(props.controller.errors.value[path(key)] ?? []).map(t),
    ...((attempt.value || touched.has(key)) && issues.value[key]
      ? [issues.value[key]!]
      : []),
  ];
}
const summary = computed(() =>
  [
    activation.value === 'active'
      ? text('Konto aktywne od razu.', 'The account is active immediately.')
      : activation.value === 'scheduled'
        ? string('activate_at')
          ? text('Aktywacja: ', 'Activation: ') +
            formatApplicationInstant(string('activate_at'), locale.value)
          : text('Wybierz termin aktywacji.', 'Choose an activation date.')
        : text(
            'Konto nieaktywne do ręcznej aktywacji.',
            'The account stays inactive until manually activated.',
          ),
    ...(deactivation.value
      ? [
          string('deactivate_at')
            ? text('Dezaktywacja: ', 'Deactivation: ') +
              formatApplicationInstant(string('deactivate_at'), locale.value)
            : text(
                'Wybierz termin dezaktywacji.',
                'Choose a deactivation date.',
              ),
        ]
      : []),
    credentials.value === 'invitation'
      ? text(
          'Zaproszenie zostanie zlecone do wysyłki od razu. Użytkownik ustawi hasło i potwierdzi e-mail, nawet przed aktywacją.',
          'An invitation will be queued immediately. The user can set a password and verify their email before activation.',
        )
      : credentials.value === 'password'
        ? text(
            'Hasło ustawia administrator. Zaproszenie nie zostanie wysłane.',
            'The administrator sets the password. No invitation will be sent.',
          )
        : text(
            'Konto powstanie bez hasła i bez wiadomości. Zaproszenie możesz wysłać później.',
            'The account will have no password and no email will be sent. You can invite the user later.',
          ),
    get('email_verified') === true
      ? text(
          'Adres e-mail zostanie potwierdzony przez administratora.',
          'The administrator will verify the email address.',
        )
      : credentials.value !== 'invitation'
        ? text(
            'Adres pozostanie niezweryfikowany. Dostęp będzie zależał od polityki weryfikacji Auth.',
            'The email remains unverified. Access follows the Auth verification policy.',
          )
        : '',
    ...(get('password_change_required') === true
      ? [
          text(
            'Przy pierwszym logowaniu wymagana będzie zmiana hasła.',
            'The first sign-in requires a password change.',
          ),
        ]
      : []),
    ...(get('security_locked') === true || !!string('attempt_locked_until')
      ? [
          text(
            'Dodatkowa blokada uniemożliwi logowanie do czasu jej zdjęcia lub upływu terminu.',
            'An additional lock prevents sign-in until it is removed or expires.',
          ),
        ]
      : []),
  ].filter(Boolean),
);
async function submit() {
  attempt.value++;
  if (Object.keys(issues.value).length) {
    if (issues.value.attempt_locked_until || issues.value.credential_mode)
      advanced.value = false;
  } else if (command.value && !readonly.value) {
    const review = await props.controller.reviewCommand(
      props.section.key,
      command.value.key,
    );
    if (review) {
      if (!creationProof.confirmed.value) await creationProof.begin();
      else {
        const result = await props.controller.execute(
          props.section.key,
          command.value.key,
        );
        if (result?.status === 'committed') {
          attempt.value = 0;
          touched.clear();
        }
      }
    }
  }
  await nextTick();
  form.value?.focusInvalid();
}
</script>

<template>
  <Form
    ref="form"
    :label="text('Tworzenie użytkownika', 'Create user')"
    :busy="busy"
    :dirty="controller.dirty.value"
    @submit="submit"
  >
    <ViewBoundary :readiness="readiness">
      <template #loading
        ><ContentLoading
          shape="form"
          :label="text('Wczytywanie opcji konta', 'Loading account options')"
      /></template>
      <template #error>
        <FormNotifications
          :locale="locale"
          :error="
            text(
              'Nie udało się wczytać opcji konta.',
              'Unable to load account options.',
            )
          "
        />
        <Button @click="read(true)">{{
          text('Spróbuj ponownie', 'Retry loading')
        }}</Button>
      </template>
      <div class="min-w-0 space-y-6" data-auth-creation>
        <div class="flex flex-wrap items-center justify-between gap-3">
          <p class="max-w-2xl text-sm text-muted">
            {{
              text(
                'Ustal dane użytkownika, sposób ustawienia hasła i moment uzyskania dostępu.',
                'Choose the user details, initial password setup and when access begins.',
              )
            }}
          </p>
          <Badge :tone="activation === 'active' ? 'success' : 'neutral'">{{
            activation === 'active'
              ? text('Aktywne od razu', 'Active immediately')
              : activation === 'scheduled'
                ? text('Zaplanowana aktywacja', 'Scheduled activation')
                : text('Nieaktywne', 'Inactive')
          }}</Badge>
        </div>
        <FormSection :title="text('Dane użytkownika', 'User details')">
          <RadioGroup
            :model-value="naming"
            :label="text('Sposób prezentacji nazwy', 'Name format')"
            :disabled="readonly"
            :options="[
              {
                value: 'full',
                label: text('Imię i nazwisko', 'First name and surname'),
              },
              {
                value: 'single',
                label: text('Nazwa wyświetlana', 'Display name'),
              },
            ]"
            @update:model-value="changeNaming"
          />
          <div class="grid min-w-0 gap-5 md:grid-cols-2">
            <TextInput
              v-for="key in naming === 'full'
                ? ['first_name', 'surname']
                : ['name']"
              :key="key"
              :model-value="string(key)"
              :name="key"
              :autocomplete="
                key === 'first_name'
                  ? 'given-name'
                  : key === 'surname'
                    ? 'family-name'
                    : 'name'
              "
              :label="
                key === 'first_name'
                  ? text('Imię', 'First name')
                  : key === 'surname'
                    ? text('Nazwisko', 'Surname')
                    : text('Nazwa wyświetlana', 'Display name')
              "
              required
              :maxlength="160"
              :readonly="readonly"
              :error="errors(key)"
              @blur="touched.add(key)"
              @update:model-value="set(key, $event)"
            />
            <TextInput
              :model-value="string('email')"
              name="email"
              type="email"
              autocomplete="email"
              :label="text('E-mail', 'Email')"
              required
              :maxlength="254"
              :readonly="readonly"
              :error="errors('email')"
              @blur="touched.add('email')"
              @update:model-value="set('email', $event)"
            />
            <TextInput
              v-if="string('identifier_mode') !== 'email'"
              :model-value="string('login')"
              name="login"
              autocomplete="username"
              :label="text('Login', 'Login')"
              :required="string('identifier_mode') === 'login'"
              :maxlength="64"
              :readonly="readonly"
              :error="errors('login')"
              @blur="touched.add('login')"
              @update:model-value="set('login', $event)"
            />
          </div>
        </FormSection>
        <FormSection :title="text('Aktywność konta', 'Account activity')">
          <RadioGroup
            :model-value="activation"
            :label="
              text(
                'Kiedy konto będzie aktywne?',
                'When will this account be active?',
              )
            "
            :disabled="readonly"
            :options="[
              { value: 'active', label: text('Od razu', 'Immediately') },
              {
                value: 'inactive',
                label: text('Po ręcznej aktywacji', 'After manual activation'),
              },
              {
                value: 'scheduled',
                label: text('W wybranym terminie', 'On a scheduled date'),
              },
            ]"
            @update:model-value="changeActivation"
          />
          <DateTimeInput
            v-if="activation === 'scheduled'"
            :model-value="string('activate_at') || null"
            :label="text('Termin aktywacji', 'Activation date')"
            :locale="locale"
            required
            :disabled="readonly"
            :error="errors('activate_at')"
            @validity="temporalValid.activate_at = $event"
            @update:model-value="set('activate_at', $event)"
          />
          <Checkbox
            :model-value="deactivation"
            :label="
              text(
                'Zaplanuj dezaktywację konta',
                'Schedule account deactivation',
              )
            "
            :disabled="readonly"
            @update:model-value="
              deactivation = $event === true;
              if (!$event) {
                set('deactivate_at', null);
                temporalValid.deactivate_at = true;
              }
            "
          />
          <DateTimeInput
            v-if="deactivation"
            :model-value="string('deactivate_at') || null"
            :label="text('Termin dezaktywacji', 'Deactivation date')"
            :locale="locale"
            required
            :disabled="readonly"
            :error="errors('deactivate_at')"
            @validity="temporalValid.deactivate_at = $event"
            @update:model-value="set('deactivate_at', $event)"
          />
        </FormSection>
        <FormSection
          :title="text('Hasło i zaproszenie', 'Password and invitation')"
        >
          <RadioGroup
            :model-value="credentials"
            :label="
              text(
                'Jak użytkownik otrzyma pierwsze hasło?',
                'How will the user set their first password?',
              )
            "
            :disabled="readonly"
            :error="errors('credential_mode')"
            :options="[
              {
                value: 'invitation',
                label: text('Wyślij zaproszenie', 'Send invitation'),
                disabled: !canInvite,
              },
              {
                value: 'password',
                label: text('Ustaw hasło teraz', 'Set password now'),
              },
              {
                value: 'none',
                label: text(
                  'Utwórz bez hasła i wysyłki',
                  'Create without password or email',
                ),
              },
            ]"
            @update:model-value="changeCredentials"
          />
          <p v-if="credentials === 'invitation'" class="text-sm text-muted">
            {{
              text(
                'Użytkownik od razu otrzyma link do ustawienia hasła. Przyjęcie zaproszenia potwierdza adres e-mail, ale nie aktywuje konta.',
                'The user receives a password setup link immediately. Accepting it verifies the email but does not activate the account.',
              )
            }}
          </p>
          <template v-if="credentials === 'password'">
            <div class="grid min-w-0 gap-5 md:grid-cols-2">
              <TextInput
                v-for="key in ['password', 'password_confirmation']"
                :key="key"
                :model-value="string(key)"
                :name="key"
                type="password"
                autocomplete="new-password"
                :label="
                  key === 'password'
                    ? text('Hasło', 'Password')
                    : text('Powtórz hasło', 'Confirm password')
                "
                required
                :maxlength="72"
                :readonly="readonly"
                :helper="key === 'password' ? passwordHint : ''"
                :error="errors(key)"
                @blur="touched.add(key)"
                @update:model-value="set(key, $event)"
              />
            </div>
            <Checkbox
              :model-value="get('password_change_required') === true"
              :label="
                text(
                  'Wymagaj zmiany hasła przy pierwszym logowaniu',
                  'Require a password change at first sign-in',
                )
              "
              :disabled="readonly"
              @update:model-value="
                set('password_change_required', $event === true)
              "
            />
          </template>
        </FormSection>
        <FormSection
          :title="text('Weryfikacja adresu e-mail', 'Email verification')"
        >
          <Checkbox
            :model-value="get('email_verified') === true"
            :label="
              text(
                'Potwierdzam adres e-mail jako administrator',
                'I verify this email address as an administrator',
              )
            "
            :helper="
              text(
                'Użytkownik nie będzie musiał osobno potwierdzać tego adresu. Decyzja zostanie zapisana w historii Audit.',
                'The user will not need to verify this address separately. Audit history will record this decision.',
              )
            "
            :disabled="readonly || !canVerify"
            :error="errors('email_verified')"
            @update:model-value="set('email_verified', $event === true)"
          />
          <p v-if="!canVerify" class="text-xs text-muted">
            {{
              text(
                'Opcja wymaga uprawnienia do administracyjnego potwierdzania adresów i włączenia jej w polityce Auth.',
                'This option requires email verification bypass permission and an enabled Auth policy setting.',
              )
            }}
          </p>
        </FormSection>
        <FormSection
          :title="text('Dodatkowe zabezpieczenia', 'Additional security')"
          collapsible
          v-model:collapsed="advanced"
        >
          <Checkbox
            :model-value="get('security_locked') === true"
            :label="
              text(
                'Utwórz konto z blokadą bezpieczeństwa',
                'Create with a security lock',
              )
            "
            :disabled="readonly"
            @update:model-value="set('security_locked', $event === true)"
          />
          <DateTimeInput
            :model-value="string('attempt_locked_until') || null"
            :label="text('Zablokuj logowanie do', 'Block sign-in until')"
            :locale="locale"
            :helper="
              text(
                'Opcjonalnie. Blokada działa niezależnie od aktywacji konta.',
                'Optional. This lock applies independently of account activation.',
              )
            "
            :disabled="readonly"
            :error="errors('attempt_locked_until')"
            @validity="temporalValid.attempt_locked_until = $event"
            @update:model-value="set('attempt_locked_until', $event)"
          />
        </FormSection>
        <div
          class="rounded border border-line bg-surface p-5"
          data-creation-summary
        >
          <h3 class="mb-3 text-sm font-semibold">
            {{ text('Po utworzeniu konta', 'After account creation') }}
          </h3>
          <ul class="list-disc space-y-2 pl-5 text-sm text-muted">
            <li v-for="line in summary" :key="line">{{ line }}</li>
          </ul>
        </div>
        <AuthOperationProof
          v-if="command && creationProof.open.value"
          :controller="creationProof"
        />
      </div>
    </ViewBoundary>
    <template #feedback
      ><FormNotifications
        :locale="locale"
        :attempt="attempt"
        :error="
          attempt && Object.keys(issues).length
            ? text(
                'Sprawdź zaznaczone pola przed utworzeniem konta.',
                'Check the highlighted fields before creating this account.',
              )
            : t(controller.error.value?.message ?? '')
        "
        :success="t(controller.notice.value)"
    /></template>
    <template #actions
      ><Button
        type="submit"
        :disabled="!record || readonly || controller.blocked.value"
        >{{ text('Utwórz konto', 'Create account') }}</Button
      ></template
    >
  </Form>
</template>
