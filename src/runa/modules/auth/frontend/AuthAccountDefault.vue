<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Alert,
  Button,
  Checkbox,
  FileInput,
  Form,
  FormSection,
  RadioGroup,
  Spinner,
  Table,
  TextInput,
  FormNotifications,
} from '@/runa/ui';
import AppLoadingOverlay from '../../../resources/js/components/AppLoadingOverlay.vue';
import AppPreferenceControls from '../../../resources/js/components/AppPreferenceControls.vue';
import { useDisplayPreferences } from '../../../resources/js/composables/useDisplayPreferences';
import type { AccountRow } from './account';
import type { TableColumn, TableQuery } from '@/runa/ui';
import type { AccountTab, AccountTask } from './useAuthAccount';
import { useAuthAccount } from './useAuthAccount';
import AuthAccountEditor from './AuthAccountEditor.vue';
import AuthRecoveryCodesDialog from './AuthRecoveryCodesDialog.vue';
const props = defineProps<{ accountId: string }>();
const account = useAuthAccount(props.accountId);
const modalFeedback = ref(false);
const {
  text,
  locale,
  profile,
  security,
  options,
  tab,
  task,
  busy,
  ready,
  failure,
  failureText,
  notice,
  confirmed,
  fields,
  errors,
  naming,
  method,
  target,
  secret,
  codes,
  files,
  rows,
  cursor,
  delivery,
  draft,
  caps,
  has,
  canAvatar,
  proofMethods,
  tabs,
  fieldNames,
  needsProof,
  form,
  heading,
  choose,
  initialize,
  selectTab,
  submit,
  revoke,
  saveDelivery,
  resetPreferences,
  restoreDraft,
  readPage,
  perform,
  refreshCsrf,
  sendCode,
  sendVerification,
} = account;
const display = useDisplayPreferences();
const feedbackAttempt = ref(0);
const titles = computed<Record<AccountTab, string>>(() => ({
  details: text('Konto i zabezpieczenia', 'Account and security'),
  security: text(
    'Hasło i weryfikacja dwuetapowa',
    'Password and two-factor authentication',
  ),
  addresses: text('Dodatkowe adresy e-mail', 'Secondary email addresses'),
  avatar: text('Zdjęcie profilowe', 'Profile image'),
  sessions: text('Aktywne sesje', 'Active sessions'),
  trust: text('Zaufane urządzenia', 'Trusted devices'),
  preferences: text('Preferencje', 'Preferences'),
  loginHistory: text('Historia logowania', 'Login history'),
  accountHistory: text('Historia konta', 'Account history'),
}));
const labels = computed<Record<string, string>>(() => ({
  name: text('Nazwa wyświetlana', 'Display name'),
  first_name: text('Imię', 'First name'),
  surname: text('Nazwisko', 'Surname'),
  login: 'Login',
  email: text('Nowy adres e-mail', 'New email address'),
  current_password: text('Aktualne hasło', 'Current password'),
  password: text('Nowe hasło', 'New password'),
  password_confirmation: text('Powtórz nowe hasło', 'Repeat new password'),
  code: text('Kod weryfikacyjny', 'Verification code'),
  enrollment_code: text('Kod z aplikacji', 'Authenticator code'),
}));
const destructive = computed(() =>
  ['secondaryRemove', 'avatarRemove', 'deactivate', 'delete'].includes(
    task.value,
  ),
);
const taskTitle = computed(
  () =>
    ({
      details: titles.value.details,
      password: text('Zmień hasło', 'Change password'),
      email: text('Zmień główny adres e-mail', 'Change primary email'),
      secondaryAdd: text('Dodaj adres e-mail', 'Add email address'),
      secondaryChange: text(
        'Zmień lub ponów weryfikację adresu',
        'Change or verify email again',
      ),
      secondaryRemove: text('Usuń dodatkowy adres', 'Remove secondary address'),
      avatar: text('Zmień zdjęcie', 'Change image'),
      avatarRemove: text('Usuń zdjęcie', 'Remove image'),
      totpBegin: text(
        'Dodaj aplikację uwierzytelniającą',
        'Add authenticator app',
      ),
      totpConfirm: text('Potwierdź aplikację', 'Confirm authenticator'),
      emailEnable: text('Włącz kody e-mail', 'Enable email codes'),
      disable: text('Wyłącz drugi składnik', 'Disable second factor'),
      recoveryRotate: text('Nowe kody odzyskiwania', 'Replace recovery codes'),
      trustIssue: text('Zaufaj tej przeglądarce', 'Trust this browser'),
      deactivate: text('Dezaktywuj konto', 'Deactivate account'),
      delete: text('Usuń konto', 'Delete account'),
    })[task.value],
);
const methodOptions = computed(() =>
  proofMethods.value.map((value) => ({
    value,
    label:
      value === 'totp'
        ? text('Aplikacja', 'Authenticator')
        : value === 'email'
          ? 'E-mail'
          : text('Kod odzyskiwania', 'Recovery code'),
  })),
);
const formVisible = computed(
  () =>
    ['details', 'security', 'addresses'].includes(tab.value) ||
    (tab.value === 'avatar' && canAvatar.value) ||
    (tab.value === 'trust' && proofMethods.value.length > 0),
);
const visibleTabs = computed(() =>
  tabs.value.filter(
    (value) => !['security', 'avatar', 'addresses'].includes(value),
  ),
);
const history = computed(() => tab.value.endsWith('History'));
const query = ref<TableQuery>({
  page: 1,
  perPage: 10,
  sort: null,
  filters: {},
});
function date(value: string | undefined) {
  if (!value) return '—';
  const d = new Date(/[zZ]|[+-]\d\d:?\d\d$/.test(value) ? value : value + 'Z');
  return Number.isNaN(d.getTime())
    ? '—'
    : d.toLocaleString(locale.value === 'pl' ? 'pl-PL' : 'en-GB');
}
const columns = computed<TableColumn<AccountRow>[]>(() =>
  history.value
    ? [
        {
          key: 'date',
          label: text('Data', 'Date'),
          value: (r) => date(r.occurred_at),
        },
        {
          key: 'action',
          label: text('Zdarzenie', 'Event'),
          value: (r) => eventLabel(r.action),
        },
        {
          key: 'outcome',
          label: text('Wynik', 'Outcome'),
          value: (r) =>
            r.outcome === 'success'
              ? text('Powodzenie', 'Success')
              : r.outcome === 'refused'
                ? text('Odmowa', 'Refused')
                : text('Zarejestrowano', 'Recorded'),
        },
      ]
    : [
        {
          key: 'created',
          label: text('Utworzono', 'Created'),
          value: (r) => date(r.authenticated_at ?? r.created_at),
        },
        {
          key: 'expires',
          label: text('Wygasa', 'Expires'),
          value: (r) => date(r.expires_at),
        },
        ...(tab.value === 'sessions'
          ? [
              {
                key: 'current',
                label: text('Sesja', 'Session'),
                value: (r: AccountRow) =>
                  r.current
                    ? text('Bieżąca', 'Current')
                    : text('Inna', 'Other'),
              },
            ]
          : []),
      ],
);
function eventLabel(action?: string) {
  const prefix = action?.split('.')[0];
  return (
    (
      {
        login: text('Logowanie', 'Sign-in'),
        logout: text('Wylogowanie', 'Sign-out'),
        password: text('Hasło', 'Password'),
        two_factor: text('Drugi składnik', 'Second factor'),
        profile: text('Profil', 'Profile'),
        email: text('Adres e-mail', 'Email address'),
        secondary: text('Dodatkowy adres', 'Secondary address'),
        avatar: text('Zdjęcie', 'Image'),
        session: text('Sesja', 'Session'),
        sessions: text('Sesje', 'Sessions'),
        verification: text('Weryfikacja adresu', 'Email verification'),
        identity: text('Stan konta', 'Account status'),
      } as Record<string, string>
    )[prefix ?? ''] ?? text('Zmiana konta', 'Account change')
  );
}
function selectTask(value: AccountTask) {
  choose(value);
}
function recipients(
  choice: { recipients: string[] },
  id: string,
  enabled: boolean,
) {
  choice.recipients = enabled
    ? [...new Set([...choice.recipients, id])]
    : choice.recipients.filter((v) => v !== id);
}
const passwordHint = computed(() =>
  options.value
    ? [
        text(
          `Co najmniej ${options.value.password.min_length} znaków, maksymalnie 72 bajty.`,
          `At least ${options.value.password.min_length} characters, at most 72 bytes.`,
        ),
        options.value.password.mixed_case
          ? text('Wielkie i małe litery.', 'Upper and lower case letters.')
          : '',
        options.value.password.numbers ? text('Cyfra.', 'A number.') : '',
        options.value.password.symbols ? text('Symbol.', 'A symbol.') : '',
      ]
        .filter(Boolean)
        .join(' ')
    : '',
);
</script>
<template>
  <div
    class="space-y-6"
    @submit.capture="feedbackAttempt++"
    :aria-busy="busy"
    :inert="display.changingPreference.value || undefined"
  >
    <nav
      class="flex flex-wrap gap-2"
      :aria-label="text('Ustawienia konta', 'Account settings')"
    >
      <Button
        v-for="item in visibleTabs"
        :key="item"
        :variant="tab === item ? 'solid' : 'outline'"
        :aria-current="tab === item ? 'page' : undefined"
        :disabled="busy"
        @click="
          query.page = 1;
          selectTab(item);
        "
        >{{ titles[item] }}</Button
      >
    </nav>
    <h2 ref="heading" tabindex="-1" class="auth-heading text-xl font-semibold">
      {{ titles[tab] }}
    </h2>
    <Spinner v-if="!ready && busy" :label="text('Wczytywanie', 'Loading')" />
    <template v-if="ready && profile">
      <Alert v-if="draft && tab === 'details'" tone="info">
        {{
          text(
            'Zachowano niezapisane dane tego konta w pamięci.',
            'Unsaved values for this account were retained in memory.',
          )
        }}
        <Button @click="restoreDraft">{{
          text('Przywróć do przejrzenia', 'Restore for review')
        }}</Button>
        <Button @click="draft = null">{{ text('Odrzuć', 'Discard') }}</Button>
      </Alert>
      <AuthAccountEditor
        @feedback-modal="modalFeedback = $event"
        v-if="tab === 'details' && !['deactivate', 'delete'].includes(task)"
        :account="account"
        :password-hint="passwordHint"
      />
      <div
        v-if="
          tab === 'details' && (has('self_deactivate') || has('self_delete'))
        "
        class="flex flex-wrap gap-3 border-t border-line pt-5"
      >
        <Button
          v-if="has('self_deactivate')"
          tone="danger"
          @click="selectTask('deactivate')"
          >{{ text('Dezaktywuj konto', 'Deactivate account') }}</Button
        >
        <Button
          v-if="has('self_delete')"
          tone="danger"
          @click="selectTask('delete')"
          >{{ text('Usuń konto', 'Delete account') }}</Button
        >
        <Button v-if="task !== 'details'" @click="selectTask('details')">{{
          text('Wróć do danych konta', 'Back to account details')
        }}</Button>
      </div>

      <Form
        v-if="
          formVisible &&
          (tab !== 'details' || ['deactivate', 'delete'].includes(task)) &&
          !codes.length &&
          (tab !== 'addresses' ||
            task !== 'secondaryAdd' ||
            (has('secondary_self_add') &&
              profile.secondary_emails.length <
                Number(caps.secondary_email_limit)))
        "
        ref="form"
        :label="taskTitle"
        :busy="busy"
        @submit="submit"
      >
        <FormSection :title="taskTitle">
          <RadioGroup
            v-if="task === 'details' && has('own_name_editable')"
            v-model="naming"
            :label="text('Sposób prezentacji nazwy', 'Name format')"
            :options="[
              { value: 'single', label: text('Jedna nazwa', 'Single name') },
              {
                value: 'full',
                label: text('Imię i nazwisko', 'First name and surname'),
              },
            ]"
          />
          <RadioGroup
            v-if="needsProof"
            v-model="method"
            :label="text('Metoda weryfikacji', 'Verification method')"
            :options="methodOptions"
          />
          <RadioGroup
            v-if="task === 'disable'"
            v-model="target"
            :label="text('Metoda do wyłączenia', 'Method to disable')"
            :options="
              (security?.enrolled ?? []).map((value) => ({
                value,
                label:
                  value === 'totp'
                    ? text('Aplikacja', 'Authenticator')
                    : 'E-mail',
              }))
            "
          />
          <TextInput
            v-for="name in fieldNames"
            :key="name"
            :model-value="fields[name] ?? ''"
            :label="labels[name] ?? name"
            :name="name"
            :type="
              name.includes('password')
                ? 'password'
                : name === 'email'
                  ? 'email'
                  : 'text'
            "
            :autocomplete="
              name === 'current_password'
                ? 'current-password'
                : name.includes('password')
                  ? 'new-password'
                  : name.includes('code')
                    ? 'one-time-code'
                    : name === 'email'
                      ? 'email'
                      : 'off'
            "
            :error="errors[name] ?? ''"
            :required="name !== 'login' || options?.identifier_mode === 'login'"
            :helper="name === 'password' ? passwordHint : ''"
            @update:model-value="
              fields[name] = $event ?? '';
              delete errors[name];
            "
          />
          <Button v-if="needsProof && method === 'email'" @click="sendCode">{{
            text('Wyślij kod', 'Send code')
          }}</Button>
          <div
            v-if="secret"
            class="space-y-3 border-l-2 border-[var(--runa-primary-solid)] pl-4"
          >
            <p class="text-sm">
              {{
                text(
                  'Dodaj ten klucz w aplikacji uwierzytelniającej, a następnie potwierdź kodem.',
                  'Add this key to your authenticator, then confirm with its code.',
                )
              }}
            </p>
            <code
              class="select-all text-sm [overflow-wrap:anywhere]"
              data-enrollment-secret
              >{{ secret }}</code
            >
          </div>
          <FileInput
            v-if="task === 'avatar'"
            v-model="files"
            :locale="locale"
            :label="text('Obraz JPEG, PNG lub WebP', 'JPEG, PNG or WebP image')"
            accept="image/jpeg,image/png,image/webp"
            :max-bytes="Number(caps.avatar_max_kib) * 1024"
            :choose-label="text('Wybierz plik', 'Choose file')"
            :empty-label="text('Nie wybrano pliku', 'No file selected')"
            :remove-label="text('Usuń', 'Remove')"
            :count-message="text('Wybierz jeden plik.', 'Choose one file.')"
            :size-message="text('Plik jest za duży.', 'File is too large.')"
            :type-message="
              text(
                'Wybierz obraz JPEG, PNG lub WebP.',
                'Choose a JPEG, PNG or WebP image.',
              )
            "
          />
          <template v-if="destructive">
            <p class="text-sm">
              {{
                ['deactivate', 'delete'].includes(task)
                  ? text(
                      'Utracisz dostęp do konta. Przywrócenie wymaga administratora.',
                      'You will lose access. An administrator is required to restore it.',
                    )
                  : task === 'secondaryRemove'
                    ? fields.email
                    : text(
                        'Zdjęcie zostanie usunięte.',
                        'The profile image will be removed.',
                      )
              }}
            </p>
            <Checkbox
              v-model="confirmed"
              :label="text('Potwierdzam operację', 'I confirm this operation')"
            />
          </template>
        </FormSection>
        <template #actions
          ><Button
            type="submit"
            variant="outline"
            :busy="busy"
            :tone="destructive ? 'danger' : 'primary'"
            :disabled="task === 'details' && !fieldNames.length"
            >{{ text('Zatwierdź', 'Submit') }}</Button
          ></template
        >
      </Form>
      <template v-if="tab === 'sessions' || tab === 'trust' || history">
        <Table
          v-model:query="query"
          mode="client"
          :rows="rows"
          :columns="columns"
          :row-key="(row) => row.public_id"
          :caption="titles[tab]"
          :english="locale === 'en'"
          :searchable="false"
        >
          <template v-if="!history" #actions="{ row }"
            ><Button
              tone="danger"
              :disabled="busy"
              @click="revoke(row.public_id)"
              >{{
                row.current
                  ? text('Zakończ bieżącą', 'End current')
                  : text('Unieważnij', 'Revoke')
              }}</Button
            ></template
          >
        </Table>
        <Button
          v-if="cursor"
          :busy="busy"
          @click="perform(() => readPage(true))"
          >{{ text('Wczytaj starsze', 'Load older') }}</Button
        >
      </template>
      <FormSection
        v-if="tab === 'preferences'"
        :title="text('Wygląd i język', 'Appearance and language')"
      >
        <AppPreferenceControls
          :locale="display.locale.value"
          :theme="display.theme.value"
          :theme-label="display.themeLabel.value"
          :language-label="display.languageLabel.value"
          :t="display.t"
          @change="display.changePreference"
        />
        <p class="text-sm text-muted">
          {{
            text(
              'Zmiany zapisują się na koncie od razu.',
              'Changes are saved to your account immediately.',
            )
          }}
        </p>
        <Checkbox
          v-model="confirmed"
          :label="
            text(
              'Potwierdzam przywrócenie domyślnych preferencji konta',
              'I confirm resetting account preferences',
            )
          "
        />
        <Button :busy="busy" @click="resetPreferences">{{
          text('Przywróć domyślne', 'Restore defaults')
        }}</Button>
      </FormSection>
      <FormSection
        v-if="tab === 'preferences'"
        :title="text('Powiadomienia e-mail', 'Email notifications')"
      >
        <Form
          v-for="choice in delivery"
          :key="choice.module + ':' + choice.key"
          :label="choice.names[locale]"
          :busy="busy"
          @submit="saveDelivery(choice)"
        >
          <Checkbox
            v-model="choice.enabled"
            :disabled="choice.mandatory || choice.sensitive"
            :label="choice.names[locale]"
          />
          <p
            v-if="choice.mandatory || choice.sensitive"
            class="text-xs text-muted"
          >
            {{
              text(
                'Wymagana wiadomość bezpieczeństwa, tylko na główny adres.',
                'Required security email, sent only to the primary address.',
              )
            }}
          </p>
          <template v-else>
            <Checkbox
              :model-value="choice.recipients.includes('primary')"
              :label="profile.email"
              @update:model-value="recipients(choice, 'primary', $event)"
            />
            <template
              v-for="address in profile.secondary_emails"
              :key="address.public_id"
              ><Checkbox
                v-if="address.verified_at"
                :model-value="choice.recipients.includes(address.public_id)"
                :label="address.email"
                @update:model-value="
                  recipients(choice, address.public_id, $event)
                "
            /></template>
          </template>
          <template v-if="!choice.mandatory && !choice.sensitive" #actions
            ><Button
              type="submit"
              variant="outline"
              :disabled="!choice.recipients.length"
              >{{ text('Zapisz wybór', 'Save selection') }}</Button
            ></template
          >
        </Form>
      </FormSection>
    </template>
  </div>
  <AuthRecoveryCodesDialog
    :codes="codes"
    :locale="locale"
    :busy="busy"
    @acknowledge="codes = []"
  />
  <FormNotifications
    v-if="!modalFeedback && !codes.length"
    :attempt="feedbackAttempt"
    :error="failure ? failureText : ''"
    :success="notice"
    :invalid="Object.keys(errors).length > 0"
    :locale="locale"
  >
    <template #actions>
      <Button v-if="failure === 'csrf'" @click="refreshCsrf">{{
        text('Odśwież zabezpieczenie', 'Renew form security')
      }}</Button>
      <Button
        v-if="failure && (!ready || failure === 'conflict')"
        @click="initialize"
        >{{ text('Wczytaj aktualne dane', 'Reload current values') }}</Button
      >
    </template>
  </FormNotifications>
  <AppLoadingOverlay
    :active="display.coverVisible.value"
    :label="display.t('applyingPreferences')"
    @covered="display.applyPreference"
    @revealed="display.finishPreferenceChange"
  />
</template>
<style scoped>
/* A programmatic reading target, not an interactive/tab-stop control. */
.auth-heading:focus {
  outline: none;
}

/* Account steps change immediately; shared controls own feedback and reduced-motion transitions. */
@media print {
  [data-enrollment-secret],
  [data-recovery-code] {
    visibility: hidden;
  }
}
</style>
