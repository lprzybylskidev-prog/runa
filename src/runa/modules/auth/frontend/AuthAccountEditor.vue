<script setup lang="ts">
import { computed, ref } from 'vue';
import {
  Alert,
  Button,
  Card,
  Checkbox,
  FileInput,
  Form,
  RadioGroup,
  Select,
  TextInput,
} from '@/runa/ui';
import type { AccountSection, useAuthAccount } from './useAuthAccount';
import AuthAccountProof from './AuthAccountProof.vue';
import AuthAvatarCropper from './AuthAvatarCropper.vue';
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
  naming,
  target,
  secret,
  codes,
  files,
  has,
  canAvatar,
  overviewAction,
  removeAvatar,
  sectionFields,
  sectionErrors,
  saveSection,
  sendVerification,
  failure,
  notice,
} = props.account;
const detailForm = ref<{ focusInvalid: () => void }>();
const avatarForm = ref<{ focusInvalid: () => void }>();
const passwordForm = ref<{ focusInvalid: () => void }>();
const securityForm = ref<{ focusInvalid: () => void }>();
const originals = ref<readonly File[]>([]);
const cropper = ref<InstanceType<typeof AuthAvatarCropper>>();
const cropReady = ref(false),
  cropError = ref(''),
  cropping = ref(false);
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

function setAction(value: string) {
  if (!actions.value.some((action) => action.value === value)) return;
  overviewAction.value = value as typeof overviewAction.value;
  target.value = security.value?.enrolled[0] ?? 'totp';
  if (value !== 'totpConfirm') secret.value = '';
  sectionFields.security.code = '';
  sectionFields.security.enrollment_code = '';
}
async function save(section: AccountSection) {
  if (busy.value || cropping.value) return;
  props.account.form.value = {
    details: detailForm,
    avatar: avatarForm,
    password: passwordForm,
    security: securityForm,
  }[section].value;
  if (section === 'avatar' && originals.value[0]) {
    cropError.value = '';
    cropping.value = true;
    try {
      if (!cropper.value || !cropReady.value) return;
      files.value = [await cropper.value.crop()];
    } catch {
      cropError.value = text(
        'Nie udało się przygotować zdjęcia. Wybierz inny plik lub szerszy kadr.',
        'Could not prepare the image. Choose another file or a wider crop.',
      );
      return;
    } finally {
      cropping.value = false;
    }
  }
  await saveSection(section);
  if (
    section === 'avatar' &&
    !failure.value &&
    !Object.keys(sectionErrors.avatar).length &&
    notice.value
  )
    originals.value = [];
}
</script>
<template>
  <div
    v-if="profile"
    class="grid min-w-0 items-start gap-6 xl:grid-cols-2"
    :inert="codes.length > 0 || undefined"
  >
    <Card>
      <template #header
        ><h3>{{ text('Dane konta', 'Account details') }}</h3></template
      >
      <Form
        ref="detailForm"
        data-account-section="details"
        :label="text('Dane konta', 'Account details')"
        :busy="busy"
        @submit="save('details')"
      >
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
            :error="sectionErrors.details[name] ?? ''"
            autocomplete="off"
            @update:model-value="
              fields[name] = $event ?? '';
              delete sectionErrors.details[name];
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
          :error="sectionErrors.details.email ?? ''"
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
            delete sectionErrors.details.email;
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
        <AuthAccountProof
          v-if="has('own_email_editable') && fields.email !== profile.email"
          :account="account"
          section="details"
        />
        <template #actions
          ><Button
            type="submit"
            variant="outline"
            :busy="busy"
            :disabled="
              !has('own_name_editable') &&
              !has('own_login_editable') &&
              !has('own_email_editable')
            "
            >{{ text('Zapisz dane', 'Save details') }}</Button
          ></template
        >
      </Form>
    </Card>
    <Card v-if="has('avatar_enabled')">
      <template #header
        ><h3>{{ text('Zdjęcie profilowe', 'Profile image') }}</h3></template
      >
      <Form
        ref="avatarForm"
        data-account-section="avatar"
        :label="text('Zdjęcie profilowe', 'Profile image')"
        :busy="busy || cropping"
        @submit="save('avatar')"
      >
        <div class="flex items-center gap-4">
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
          <p class="text-xs leading-relaxed text-muted">
            {{
              text(
                'Wybierz zdjęcie do 20 MB, ustaw kadr i zapisz.',
                'Choose an image up to 20 MB, adjust the crop and save.',
              )
            }}
          </p>
        </div>
        <template v-if="canAvatar">
          <FileInput
            v-model="originals"
            :locale="locale"
            :label="text('Obraz JPEG, PNG lub WebP', 'JPEG, PNG or WebP image')"
            accept="image/jpeg,image/png,image/webp"
            :max-bytes="20 * 1024 * 1024"
            :choose-label="text('Wybierz plik', 'Choose file')"
            :empty-label="text('Nie wybrano pliku', 'No file selected')"
            :remove-label="text('Usuń', 'Remove')"
            :count-message="text('Wybierz jeden plik.', 'Choose one file.')"
            :size-message="
              text('Plik przekracza 20 MB.', 'File exceeds 20 MB.')
            "
            :type-message="
              text(
                'Wybierz obraz JPEG, PNG lub WebP.',
                'Choose a JPEG, PNG or WebP image.',
              )
            "
            :error="sectionErrors.avatar.avatar ?? ''"
            @update:model-value="
              cropReady = false;
              cropError = '';
              delete sectionErrors.avatar.avatar;
            "
          />
          <AuthAvatarCropper
            v-if="originals[0]"
            ref="cropper"
            :file="originals[0]"
            :locale="locale"
            :max-bytes="Number(profile.capabilities.avatar_max_kib) * 1024"
            @ready="cropReady = $event"
          />
          <Alert v-if="cropError" tone="danger">{{ cropError }}</Alert>
          <Checkbox
            v-if="profile.has_avatar"
            v-model="removeAvatar"
            :disabled="originals.length > 0"
            :label="text('Usuń obecne zdjęcie', 'Remove current image')"
          />
        </template>
        <template #actions
          ><Button
            type="submit"
            variant="outline"
            :busy="busy || cropping"
            :disabled="
              !canAvatar ||
              (originals.length > 0 && !cropReady) ||
              (!originals.length && !removeAvatar)
            "
            >{{ text('Zapisz zdjęcie', 'Save image') }}</Button
          ></template
        >
      </Form>
    </Card>
    <Card>
      <template #header
        ><h3>{{ text('Hasło', 'Password') }}</h3></template
      >
      <Form
        ref="passwordForm"
        data-account-section="password"
        :label="text('Zmiana hasła', 'Password change')"
        :busy="busy"
        @submit="save('password')"
      >
        <div class="grid min-w-0 gap-5 md:grid-cols-2">
          <TextInput
            v-for="name in ['password', 'password_confirmation']"
            :key="name"
            :model-value="sectionFields.password[name] ?? ''"
            :name="name"
            type="password"
            autocomplete="new-password"
            :label="labels[name] ?? name"
            required
            :error="sectionErrors.password[name] ?? ''"
            :helper="name === 'password' ? passwordHint : ''"
            @update:model-value="
              sectionFields.password[name] = $event ?? '';
              delete sectionErrors.password[name];
            "
          />
        </div>
        <AuthAccountProof :account="account" section="password" />
        <template #actions
          ><Button type="submit" variant="outline" :busy="busy">{{
            text('Zapisz hasło', 'Save password')
          }}</Button></template
        >
      </Form>
    </Card>
    <Card>
      <template #header
        ><h3>
          {{ text('Uwierzytelnianie dwuetapowe', 'Two-factor authentication') }}
        </h3></template
      >
      <Form
        v-if="!codes.length"
        ref="securityForm"
        data-account-section="security"
        :label="
          text('Uwierzytelnianie dwuetapowe', 'Two-factor authentication')
        "
        :busy="busy"
        @submit="save('security')"
      >
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
            :model-value="sectionFields.security.enrollment_code ?? ''"
            name="enrollment_code"
            autocomplete="one-time-code"
            inputmode="numeric"
            :label="text('Kod z aplikacji', 'Authenticator code')"
            required
            :error="sectionErrors.security.enrollment_code ?? ''"
            @update:model-value="
              sectionFields.security.enrollment_code = $event ?? '';
              delete sectionErrors.security.enrollment_code;
            "
          />
        </div>
        <AuthAccountProof
          v-if="overviewAction !== 'none'"
          :account="account"
          section="security"
        />
        <template #actions
          ><Button
            type="submit"
            variant="outline"
            :busy="busy"
            :disabled="overviewAction === 'none'"
            >{{
              text('Zapisz uwierzytelnianie', 'Save authentication')
            }}</Button
          ></template
        >
      </Form>
      <Alert v-else tone="warning">{{
        text(
          'Zapisz i potwierdź kody odzyskiwania poniżej.',
          'Save and acknowledge the recovery codes below.',
        )
      }}</Alert>
    </Card>
  </div>
</template>
<style scoped>
@media print {
  [data-enrollment-secret] {
    visibility: hidden;
  }
}
</style>
