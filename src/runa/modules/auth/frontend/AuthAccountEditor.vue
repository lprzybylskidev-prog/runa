<script setup lang="ts">
import { computed, ref, watch, onScopeDispose } from 'vue';
import {
  Avatar,
  Button,
  Card,
  Dialog,
  ConfirmDialog,
  FileInput,
  Form,
  FormNotifications,
  RadioGroup,
  TextInput,
} from '@/runa/ui';
import type { AccountSection, useAuthAccount } from './useAuthAccount';
import AuthAccountProof from './AuthAccountProof.vue';
import AuthAvatarCropper from './AuthAvatarCropper.vue';
import AuthAccountSecurity from './AuthAccountSecurity.vue';
import AuthSecondaryEmails from './AuthSecondaryEmails.vue';
const props = defineProps<{
  account: ReturnType<typeof useAuthAccount>;
  passwordHint: string;
}>();
const emit = defineEmits<{ 'feedback-modal': [active: boolean] }>();
const {
  text,
  locale,
  profile,
  options,
  busy,
  fields,
  naming,
  codes,
  files,
  has,
  canAvatar,
  removeAvatar,
  sectionFields,
  sectionErrors,
  saveSection,
  sendVerification,
  failure,
  failureText,
  notice,
} = props.account;
const detailForm = ref<{ focusInvalid: () => void }>();
const avatarForm = ref<{ focusInvalid: () => void }>();
const passwordForm = ref<{ focusInvalid: () => void }>();
const originals = ref<readonly File[]>([]);
const cropOpen = ref(false);
const removeOpen = ref(false);
const secondaryModalOpen = ref(false);
const securityModalOpen = ref(false);
const removalAttempt = ref(0);
watch(removeOpen, (value) => {
  if (value) removalAttempt.value = 0;
});
watch(
  () => removeOpen.value || secondaryModalOpen.value || securityModalOpen.value,
  (value) => emit('feedback-modal', value),
);
onScopeDispose(() => emit('feedback-modal', false));
const previewUrl = ref('');
function clearPreview() {
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value);
  previewUrl.value = '';
}
watch(originals, (value) => {
  cropError.value = '';
  cropReady.value = false;
  if (value[0]) cropOpen.value = true;
  else {
    cropOpen.value = false;
    files.value = [];
    clearPreview();
  }
});
onScopeDispose(clearPreview);
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
async function confirmRemoval() {
  if (busy.value || cropping.value || originals.value.length) return;
  removalAttempt.value++;
  removeAvatar.value = true;
  await save('avatar');
  removeAvatar.value = false;
  if (
    !failure.value &&
    !Object.keys(sectionErrors.avatar).length &&
    notice.value
  )
    removeOpen.value = false;
}
async function save(section: Exclude<AccountSection, 'security'>) {
  if (busy.value || cropping.value) return;
  props.account.form.value = {
    details: detailForm,
    avatar: avatarForm,
    password: passwordForm,
  }[section].value;
  await saveSection(section);
  if (
    section === 'avatar' &&
    !failure.value &&
    !Object.keys(sectionErrors.avatar).length &&
    notice.value
  )
    originals.value = [];
}
async function applyCrop() {
  if (!cropper.value || !cropReady.value || cropping.value) return;
  cropError.value = '';
  cropping.value = true;
  try {
    const cropped = await cropper.value.crop();
    files.value = [cropped];
    clearPreview();
    previewUrl.value = URL.createObjectURL(cropped);
    removeAvatar.value = false;
    cropOpen.value = false;
  } catch {
    cropError.value = text(
      'Nie udało się przygotować zdjęcia. Wybierz inny plik.',
      'Could not prepare the image. Choose another file.',
    );
  } finally {
    cropping.value = false;
  }
}
function closeCrop(value: boolean) {
  if (cropping.value) return;
  cropOpen.value = value;
  if (!value && !files.value.length) originals.value = [];
}
</script>
<template>
  <div
    v-if="profile"
    class="grid min-w-0 items-start gap-6 xl:grid-cols-2"
    :inert="codes.length > 0 || undefined"
  >
    <div class="min-w-0 space-y-6">
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
              :required="
                name !== 'login' || options?.identifier_mode === 'login'
              "
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
        <AuthSecondaryEmails
          :account="account"
          @feedback-modal="secondaryModalOpen = $event"
        />
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
    </div>
    <div class="min-w-0 space-y-6">
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
            <Avatar
              :src="
                previewUrl ||
                (profile.has_avatar
                  ? `/api/runa/auth/profile/avatar?v=${profile.version}`
                  : '')
              "
              :initials="profile.initials"
              :size="80"
              class="text-xl"
            />
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
              :label="
                text('Obraz JPEG, PNG lub WebP', 'JPEG, PNG or WebP image')
              "
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
            <Button
              v-if="originals[0] && files.length"
              @click="cropOpen = true"
              >{{ text('Popraw kadr', 'Adjust crop') }}</Button
            >
          </template>
          <template #actions
            ><Button
              type="submit"
              variant="outline"
              :busy="busy || cropping"
              :disabled="!canAvatar || (!files.length && !removeAvatar)"
              >{{ text('Zapisz zdjęcie', 'Save image') }}</Button
            >
            <Button
              v-if="canAvatar && profile.has_avatar"
              variant="outline"
              tone="danger"
              :disabled="busy || cropping || originals.length > 0"
              @click="removeOpen = true"
              >{{ text('Usuń avatar', 'Remove avatar') }}</Button
            ></template
          >
        </Form>
      </Card>
      <AuthAccountSecurity
        :account="account"
        @feedback-modal="securityModalOpen = $event"
      />
    </div>
  </div>
  <ConfirmDialog
    v-model="removeOpen"
    :title="text('Usunąć avatar?', 'Remove avatar?')"
    :confirm-label="text('Usuń avatar', 'Remove avatar')"
    confirm-tone="danger"
    :locale="locale"
    :busy="busy"
    @confirm="confirmRemoval"
  >
    <p class="text-sm leading-7">
      {{
        text(
          'Czy na pewno chcesz usunąć swoje zdjęcie profilowe?',
          'Are you sure you want to remove your profile image?',
        )
      }}
    </p>
    <FormNotifications
      v-if="removeOpen && removalAttempt"
      :attempt="removalAttempt"
      :error="failure ? failureText : ''"
      :invalid="Object.keys(sectionErrors.avatar).length > 0"
      :locale="locale"
    >
      <template #actions>
        <Button v-if="failure === 'csrf'" @click="account.refreshCsrf">{{
          text('Odśwież zabezpieczenie', 'Renew form security')
        }}</Button>
        <Button v-if="failure === 'conflict'" @click="account.initialize">{{
          text('Wczytaj aktualne dane', 'Reload current values')
        }}</Button>
      </template>
    </FormNotifications>
  </ConfirmDialog>
  <Dialog
    :model-value="cropOpen"
    :title="text('Kadrowanie zdjęcia', 'Crop image')"
    :locale="locale"
    size="lg"
    :busy="cropping"
    initial-focus="canvas"
    @update:model-value="closeCrop"
  >
    <FormNotifications :error="cropError" :locale="locale" />
    <AuthAvatarCropper
      v-if="cropOpen && originals[0] && profile"
      ref="cropper"
      :file="originals[0]"
      :locale="locale"
      :max-bytes="Number(profile.capabilities.avatar_max_kib) * 1024"
      @ready="cropReady = $event"
    />
    <template #footer>
      <Button :disabled="cropping" @click="closeCrop(false)">{{
        text('Anuluj', 'Cancel')
      }}</Button>
      <Button
        variant="outline"
        :busy="cropping"
        :disabled="!cropReady"
        @click="applyCrop"
        >{{ text('Zastosuj kadr', 'Apply crop') }}</Button
      >
    </template>
  </Dialog>
</template>
<style scoped>
@media print {
  [data-enrollment-secret] {
    visibility: hidden;
  }
}
</style>
