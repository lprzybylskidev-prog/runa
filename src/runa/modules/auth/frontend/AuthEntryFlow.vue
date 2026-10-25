<script setup lang="ts">
import { computed, ref } from 'vue';
import { useAuthPresentation } from './presentation';
import {
  Alert,
  Button,
  Checkbox,
  Form,
  FormNotifications,
  MinimalLayout,
  RadioGroup,
  Spinner,
  TextInput,
  TextLink,
} from '@/runa/ui';
import type { EntrySession, EntryPage } from './entry';
import type { EntryCopyKey } from './messages';
import { useLocale } from '../../../resources/js/composables/useLocale';
const { locale } = useLocale();
import { useAuthEntry } from './useAuthEntry';
import AuthTotpSetup from './AuthTotpSetup.vue';
import AuthRecoveryCodesDialog from './AuthRecoveryCodesDialog.vue';
const presentation = useAuthPresentation();
const feedbackAttempt = ref(0);
const props = defineProps<{
  page: EntryPage;
  authenticated?: ((session: EntrySession) => void) | undefined;
}>();
const {
  t,
  options,
  session,
  busy,
  ready,
  failure,
  notice,
  screen,
  title,
  description,
  values,
  errors,
  naming,
  method,
  remember,
  fieldNames,
  passwordHint,
  methods,
  enrollmentRequired,
  needsProof,
  newPassword,
  enrollmentSecret,
  enrollmentQr,
  recoveryCodes,
  isTokenPage,
  returnPath,
  hasSubmit,
  errorMessage,
  form,
  heading,
  label,
  submit,
  initialize,
  refresh,
  refreshCsrf,
  sendEmail,
  logout,
  codesSaved,
  changeMethod,
} = useAuthEntry(props.page, props.authenticated);
const methodOptions = computed(() =>
  methods.value.map((value) => ({
    value,
    label: t(value === 'email' ? 'emailMethod' : value),
  })),
);
function fieldType(name: string) {
  return name.includes('password')
    ? 'password'
    : name === 'email' ||
        (name === 'identifier' && options.value?.identifier_mode === 'email')
      ? 'email'
      : 'text';
}
function autocomplete(name: string) {
  return (
    {
      identifier: 'username',
      login: 'username',
      email: 'email',
      name: 'name',
      first_name: 'given-name',
      surname: 'family-name',
      current_password: 'current-password',
      password: newPassword.value ? 'new-password' : 'current-password',
      password_confirmation: 'new-password',
      code: 'one-time-code',
      enrollment_code: 'one-time-code',
    } as Record<string, string>
  )[name];
}
const submitLabel = computed(() => {
  if (screen.value === 'enrollment')
    return t(
      method.value === 'email'
        ? 'enableEmail'
        : enrollmentSecret.value
          ? 'confirm'
          : 'beginTotp',
    );
  return t(
    ([
      'login',
      'register',
      'forgot',
      'reset',
      'invitation',
      'passwordChange',
    ].includes(screen.value)
      ? screen.value
      : 'confirm') as EntryCopyKey,
  );
});
</script>

<template>
  <component :is="presentation.minimalLayout ?? MinimalLayout">
    <section
      class="mx-auto w-full max-w-md space-y-6"
      :aria-busy="busy"
      aria-labelledby="auth-title"
    >
      <div class="space-y-3">
        <h1
          id="auth-title"
          :ref="
            (value) =>
              (heading = (value as HTMLHeadingElement | null) ?? undefined)
          "
          tabindex="-1"
          class="text-2xl font-semibold leading-tight tracking-tight auth-heading sm:text-3xl"
        >
          {{ title }}
        </h1>
        <p v-if="description" class="text-sm leading-7 text-muted">
          {{ description }}
        </p>
      </div>
      <div v-if="!ready" class="space-y-5">
        <Spinner v-if="busy" :label="t('loading')" />
      </div>
      <template v-else>
        <Form
          :ref="
            (value) =>
              (form = (value as InstanceType<typeof Form> | null) ?? undefined)
          "
          :label="title"
          :busy="busy"
          @submit="
            feedbackAttempt++;
            submit();
          "
        >
          <Alert v-if="screen === 'disabled'" tone="info">{{
            t('disabled')
          }}</Alert>
          <Alert v-else-if="screen === 'missingToken'" tone="warning">{{
            t('missingToken')
          }}</Alert>
          <AuthRecoveryCodesDialog
            v-else-if="screen === 'recoveryCodes'"
            :codes="recoveryCodes"
            :locale="locale"
            :busy="busy"
            @acknowledge="codesSaved"
          />
          <template v-else-if="screen === 'signedIn'">
            <p class="break-words text-sm text-muted">
              {{ session?.display_name }}
            </p>
            <TextLink :href="returnPath">{{ t('continue') }}</TextLink>
          </template>
          <template v-else-if="screen !== 'complete'">
            <RadioGroup
              v-if="screen === 'register'"
              v-model="naming"
              :label="t('naming')"
              :options="[
                { value: 'single', label: t('singleName') },
                { value: 'full', label: t('fullName') },
              ]"
            />
            <RadioGroup
              v-if="
                screen === 'enrollment' ||
                screen === 'challenge' ||
                needsProof ||
                (screen === 'verify' && enrollmentRequired)
              "
              :model-value="method"
              :label="t('method')"
              :options="methodOptions"
              @update:model-value="changeMethod"
            />
            <template v-for="name in fieldNames" :key="name">
              <TextInput
                :model-value="values[name] ?? ''"
                :name="name"
                :label="label(name)"
                :type="fieldType(name)"
                :autocomplete="autocomplete(name)"
                :inputmode="
                  ['code', 'enrollment_code'].includes(name) &&
                  method !== 'recovery'
                    ? 'numeric'
                    : undefined
                "
                :autocapitalize="
                  [
                    'identifier',
                    'email',
                    'login',
                    'code',
                    'enrollment_code',
                  ].includes(name)
                    ? 'none'
                    : undefined
                "
                :spellcheck="
                  [
                    'identifier',
                    'email',
                    'login',
                    'code',
                    'enrollment_code',
                  ].includes(name)
                    ? false
                    : undefined
                "
                :required="
                  !(name === 'login' && options?.identifier_mode !== 'login')
                "
                :error="errors[name] ? t(errors[name]) : ''"
                :helper="name === 'password' && newPassword ? passwordHint : ''"
                @update:model-value="
                  values[name] = $event ?? '';
                  delete errors[name];
                "
              />
            </template>
            <div
              v-if="enrollmentSecret"
              class="space-y-3 border-l-2 border-[var(--runa-primary-solid)] pl-4"
            >
              <AuthTotpSetup
                :secret="enrollmentSecret"
                :qr-code="enrollmentQr"
                :locale="locale"
              />
            </div>
            <Checkbox
              v-if="screen === 'login' && options?.remember_enabled"
              v-model="remember"
              :label="t('remember')"
            />
            <Button
              v-if="method === 'email' && needsProof"
              variant="outline"
              @click="sendEmail"
              >{{ t('sendCode') }}</Button
            >
            <template v-if="screen === 'verify' && !isTokenPage">
              <Button variant="outline" @click="sendEmail">{{
                t('resend')
              }}</Button>
              <Button @click="refresh">{{ t('refresh') }}</Button>
            </template>
          </template>
          <template v-if="hasSubmit || failure" #actions>
            <Button
              v-if="hasSubmit"
              type="submit"
              variant="outline"
              :busy="busy"
              >{{ submitLabel }}</Button
            >
          </template>
        </Form>
        <nav
          class="flex flex-wrap items-center gap-x-5 gap-y-3 text-xs"
          :aria-label="t('security')"
        >
          <TextLink v-if="screen === 'login'" href="/auth/forgot-password">{{
            t('forgotLink')
          }}</TextLink>
          <TextLink
            v-if="screen === 'login' && options?.public_registration"
            href="/auth/register"
            >{{ t('register') }}</TextLink
          >
          <TextLink v-if="screen !== 'login' && !session" href="/auth/login">{{
            t('backLogin')
          }}</TextLink>
          <Button v-if="session" :busy="busy" variant="ghost" @click="logout">{{
            t('logout')
          }}</Button>
          <TextLink
            v-if="
              screen === 'complete' &&
              ['verify-email', 'confirm-address'].includes(page)
            "
            href="/auth/security"
            >{{ t('refresh') }}</TextLink
          >
        </nav>
      </template>
    </section>
  </component>
  <FormNotifications
    :attempt="feedbackAttempt"
    :error="failure ? errorMessage : ''"
    :success="notice ? t(notice) : ''"
    :invalid="Object.keys(errors).length > 0"
    :locale="locale"
  >
    <template #actions>
      <Button v-if="!ready && failure" @click="initialize">{{
        t('retry')
      }}</Button>
      <Button v-if="failure === 'csrf'" @click="refreshCsrf">{{
        t('refreshCsrf')
      }}</Button>
      <Button
        v-if="session && ['conflict', 'account_restricted'].includes(failure)"
        @click="refresh"
        >{{ t('refresh') }}</Button
      >
    </template>
  </FormNotifications>
</template>

<style scoped>
/* A programmatic reading target, not an interactive/tab-stop control. */
.auth-heading:focus {
  outline: none;
}

/* Security steps change immediately; shared controls provide reduced-motion feedback. */
@media print {
  [data-enrollment-secret] {
    visibility: hidden;
  }
}
</style>
