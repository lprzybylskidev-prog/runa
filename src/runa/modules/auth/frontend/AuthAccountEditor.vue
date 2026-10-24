<script setup lang="ts">
import { computed } from 'vue';
import {
  Button,
  Checkbox,
  FileInput,
  Form,
  FormSection,
  RadioGroup,
  Select,
  TextInput,
} from '@/runa/ui';
import type { useAuthAccount } from './useAuthAccount';
const props = defineProps<{
  account: ReturnType<typeof useAuthAccount>;
  passwordHint: string;
}>();
const {
  text,
  locale,
  profile,
  security,
  options,
  busy,
  fields,
  errors,
  naming,
  method,
  target,
  secret,
  codes,
  files,
  has,
  canAvatar,
  proofMethods,
  form,
  overviewAction,
  overviewNeedsProof,
  removeAvatar,
  saveOverview,
  sendCode,
  sendVerification,
} = props.account;
const identityFields = computed(() => [
  ...(has('own_name_editable')
    ? naming.value === 'single'
      ? ['name']
      : ['first_name', 'surname']
    : []),
  ...(has('own_login_editable') ? ['login'] : []),
]);
const labels = computed<Record<string, string>>(() => ({
  name: text('Nazwa wyświetlana', 'Display name'),
  first_name: text('Imię', 'First name'),
  surname: text('Nazwisko', 'Surname'),
  login: 'Login',
  password: text('Nowe hasło', 'New password'),
  password_confirmation: text('Powtórz nowe hasło', 'Repeat new password'),
}));
const actions = computed(() => [
  { value: 'none', label: text('Bez zmian', 'No change') },
  ...(secret.value
    ? [
        {
          value: 'totpConfirm',
          label: text('Potwierdź aplikację', 'Confirm authenticator'),
        },
      ]
    : []),
  ...(security.value?.totp_enabled &&
  !security.value.enrolled.includes('totp') &&
  !secret.value
    ? [
        {
          value: 'totpBegin',
          label: text(
            'Włącz aplikację uwierzytelniającą',
            'Enable authenticator app',
          ),
        },
      ]
    : []),
  ...(security.value?.email_otp_enabled &&
  security.value.email_verified &&
  !security.value.enrolled.includes('email')
    ? [
        {
          value: 'emailEnable',
          label: text('Włącz kody e-mail', 'Enable email codes'),
        },
      ]
    : []),
  ...(security.value?.enrolled.length
    ? [{ value: 'disable', label: text('Wyłącz metodę', 'Disable method') }]
    : []),
  ...(security.value?.recovery_codes_enabled &&
  security.value.enrolled.includes('totp')
    ? [
        {
          value: 'recoveryRotate',
          label: text(
            'Wygeneruj nowe kody odzyskiwania',
            'Replace recovery codes',
          ),
        },
      ]
    : []),
]);
const currentPasswordRequired = computed(() =>
  Boolean(
    overviewAction.value !== 'none' ||
    fields.password ||
    fields.password_confirmation ||
    (has('own_email_editable') && fields.email !== profile.value?.email),
  ),
);
function setAction(value: string) {
  if (!actions.value.some((action) => action.value === value)) return;
  overviewAction.value = value as typeof overviewAction.value;
  target.value = security.value?.enrolled[0] ?? 'totp';
  if (value !== 'totpConfirm') secret.value = '';
  fields.code = '';
  fields.enrollment_code = '';
}
</script>
<template>
  <Form
    v-if="profile && !codes.length"
    ref="form"
    :label="text('Dane i zabezpieczenia konta', 'Account details and security')"
    :busy="busy"
    @submit="saveOverview"
  >
    <div class="grid min-w-0 gap-6 xl:grid-cols-2">
      <FormSection :title="text('Dane konta', 'Account details')">
        <div
          v-if="has('avatar_enabled')"
          class="flex min-w-0 flex-col items-start gap-5 border-b border-line pb-5 sm:flex-row"
        >
          <img
            v-if="profile.has_avatar"
            :key="profile.version"
            :src="`/api/runa/auth/profile/avatar?v=${profile.version}`"
            :alt="text('Twoje zdjęcie profilowe', 'Your profile image')"
            class="h-20 w-20 shrink-0 rounded-full object-cover"
          />
          <span
            v-else
            class="grid h-20 w-20 shrink-0 place-items-center rounded-full bg-[var(--runa-primary-subtle)] text-xl font-semibold"
            aria-hidden="true"
            >{{ profile.initials }}</span
          >
          <div class="w-full min-w-0 flex-1 space-y-3 sm:w-auto">
            <p class="text-sm font-semibold">
              {{ text('Zdjęcie profilowe', 'Profile image') }}
            </p>
            <FileInput
              v-if="canAvatar"
              v-model="files"
              :locale="locale"
              :label="
                text('Obraz JPEG, PNG lub WebP', 'JPEG, PNG or WebP image')
              "
              accept="image/jpeg,image/png,image/webp"
              :error="errors.avatar ?? ''"
              @update:model-value="delete errors.avatar"
              :max-bytes="Number(profile.capabilities.avatar_max_kib) * 1024"
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
            <Checkbox
              v-if="canAvatar && profile.has_avatar"
              v-model="removeAvatar"
              :disabled="files.length > 0"
              :label="text('Usuń obecne zdjęcie', 'Remove current image')"
            />
          </div>
        </div>
        <RadioGroup
          v-if="has('own_name_editable')"
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
        <div class="grid min-w-0 gap-5 md:grid-cols-2">
          <TextInput
            v-for="name in identityFields"
            :key="name"
            :model-value="fields[name] ?? ''"
            :name="name"
            :label="labels[name] ?? name"
            :required="name !== 'login' || options?.identifier_mode === 'login'"
            :error="errors[name] ?? ''"
            autocomplete="off"
            @update:model-value="
              fields[name] = $event ?? '';
              delete errors[name];
            "
          />
        </div>
        <p
          v-if="!has('own_name_editable')"
          class="text-sm [overflow-wrap:anywhere]"
        >
          {{ profile.display_name }}
        </p>
        <TextInput
          :model-value="fields.email ?? ''"
          name="email"
          type="email"
          autocomplete="email"
          :label="text('Adres e-mail', 'Email address')"
          :readonly="!has('own_email_editable')"
          required
          :error="errors.email ?? ''"
          :helper="
            profile.email_verified
              ? text(
                  'Adres potwierdzony. Zmiana wymaga potwierdzenia nowego adresu.',
                  'Address verified. Changing it requires verification of the new address.',
                )
              : text('Adres niepotwierdzony.', 'Email not verified.')
          "
          @update:model-value="
            fields.email = $event ?? '';
            delete errors.email;
          "
        />
        <Button
          v-if="!profile.email_verified"
          :busy="busy"
          @click="sendVerification"
          >{{
            text('Wyślij wiadomość weryfikacyjną', 'Send verification email')
          }}</Button
        >
      </FormSection>
      <FormSection
        :title="text('Hasło i weryfikacja', 'Password and verification')"
      >
        <p class="text-xs leading-relaxed text-muted">
          {{
            text(
              'Pozostaw nowe hasło puste, jeśli nie chcesz go zmieniać.',
              'Leave the new password empty to keep your current password.',
            )
          }}
        </p>
        <div class="grid min-w-0 gap-5 md:grid-cols-2">
          <TextInput
            v-for="name in ['password', 'password_confirmation']"
            :key="name"
            :model-value="fields[name] ?? ''"
            :name="name"
            type="password"
            autocomplete="new-password"
            :label="labels[name] ?? name"
            :error="errors[name] ?? ''"
            :helper="name === 'password' ? passwordHint : ''"
            @update:model-value="
              fields[name] = $event ?? '';
              delete errors[name];
            "
          />
        </div>
        <div class="space-y-4 border-t border-line pt-5">
          <p class="text-xs text-muted">
            {{ text('Włączone metody:', 'Enrolled methods:') }}
            {{
              security?.enrolled
                .map((value) =>
                  value === 'totp'
                    ? text('aplikacja', 'authenticator')
                    : 'e-mail',
                )
                .join(', ') || text('brak', 'none')
            }}
          </p>
          <Select
            :model-value="overviewAction"
            :options="actions"
            :locale="locale"
            :label="text('Weryfikacja dwuetapowa', 'Two-factor authentication')"
            @update:model-value="setAction"
          />
          <RadioGroup
            v-if="overviewAction === 'disable'"
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
          <div
            v-if="secret && overviewAction === 'totpConfirm'"
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
            <TextInput
              :model-value="fields.enrollment_code ?? ''"
              name="enrollment_code"
              autocomplete="one-time-code"
              inputmode="numeric"
              :label="text('Kod z aplikacji', 'Authenticator code')"
              required
              :error="errors.enrollment_code ?? ''"
              @update:model-value="
                fields.enrollment_code = $event ?? '';
                delete errors.enrollment_code;
              "
            />
          </div>
        </div>
        <TextInput
          :model-value="fields.current_password ?? ''"
          name="current_password"
          type="password"
          autocomplete="current-password"
          :label="text('Aktualne hasło', 'Current password')"
          :required="currentPasswordRequired"
          :error="errors.current_password ?? ''"
          :helper="
            text(
              'Wymagane przy zmianie hasła, adresu e-mail lub weryfikacji.',
              'Required when changing your password, email address or verification.',
            )
          "
          @update:model-value="
            fields.current_password = $event ?? '';
            delete errors.current_password;
          "
        />
        <template v-if="overviewNeedsProof">
          <RadioGroup
            v-model="method"
            :label="
              text('Potwierdź obecną metodą', 'Confirm with an existing method')
            "
            :options="
              proofMethods.map((value) => ({
                value,
                label:
                  value === 'totp'
                    ? text('Aplikacja', 'Authenticator')
                    : value === 'email'
                      ? 'E-mail'
                      : text('Kod odzyskiwania', 'Recovery code'),
              }))
            "
          />
          <TextInput
            :model-value="fields.code ?? ''"
            name="code"
            autocomplete="one-time-code"
            :label="text('Kod weryfikacyjny', 'Verification code')"
            required
            :error="errors.code ?? ''"
            @update:model-value="
              fields.code = $event ?? '';
              delete errors.code;
            "
          />
          <Button v-if="method === 'email'" @click="sendCode">{{
            text('Wyślij kod', 'Send code')
          }}</Button>
        </template>
      </FormSection>
    </div>
    <template #actions
      ><Button type="submit" variant="outline" :busy="busy">{{
        text('Zapisz zmiany', 'Save changes')
      }}</Button></template
    >
  </Form>
</template>
<style scoped>
@media print {
  [data-enrollment-secret] {
    visibility: hidden;
  }
}
</style>
