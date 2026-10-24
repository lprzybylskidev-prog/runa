<script setup lang="ts">
import { computed } from 'vue';
import { useAuthPresentation } from './presentation';
import {
  Alert,
  Button,
  Checkbox,
  Form,
  MinimalLayout,
  RadioGroup,
  Spinner,
  TextInput,
} from '@/runa/ui';
import type { EntrySession, EntryPage } from './entry';
import type { EntryCopyKey } from './messages';
import { useAuthEntry } from './useAuthEntry';
const presentation = useAuthPresentation();
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
          ref="heading"
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
        <Alert v-else tone="danger"
          >{{ errorMessage
          }}<template #actions
            ><Button @click="initialize">{{ t('retry') }}</Button></template
          ></Alert
        >
      </div>
      <template v-else>
        <Form ref="form" :label="title" :busy="busy" @submit="submit">
          <template #feedback>
            <Alert v-if="failure" tone="danger">{{ errorMessage }}</Alert>
            <Alert v-if="notice" tone="success">{{ t(notice) }}</Alert>
          </template>
          <Alert v-if="screen === 'disabled'" tone="info">{{
            t('disabled')
          }}</Alert>
          <Alert v-else-if="screen === 'missingToken'" tone="warning">{{
            t('missingToken')
          }}</Alert>
          <template v-else-if="screen === 'recoveryCodes'">
            <p class="text-sm leading-7 text-muted">{{ t('recoveryHelp') }}</p>
            <ul
              class="space-y-2 rounded border border-line bg-surface p-4 text-xs [overflow-wrap:anywhere]"
              :aria-label="t('recoveryTitle')"
            >
              <li v-for="code in recoveryCodes" :key="code">
                <code>{{ code }}</code>
              </li>
            </ul>
            <Button variant="solid" @click="codesSaved">{{
              t('savedCodes')
            }}</Button>
          </template>
          <template v-else-if="screen === 'signedIn'">
            <p class="break-words text-sm text-muted">
              {{ session?.display_name }}
            </p>
            <a :href="returnPath" class="auth-link">{{ t('continue') }}</a>
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
              <p class="text-sm font-semibold">{{ t('secretLabel') }}</p>
              <code
                class="block select-all text-sm [overflow-wrap:anywhere]"
                data-enrollment-secret
                >{{ enrollmentSecret }}</code
              >
              <p class="text-xs leading-6 text-muted">{{ t('secretHelp') }}</p>
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
              <Button variant="solid" @click="sendEmail">{{
                t('resend')
              }}</Button>
              <Button @click="refresh">{{ t('refresh') }}</Button>
            </template>
          </template>
          <template v-if="hasSubmit || failure" #actions>
            <Button
              v-if="hasSubmit"
              type="submit"
              variant="solid"
              :busy="busy"
              >{{ submitLabel }}</Button
            >
            <Button v-if="failure === 'csrf'" @click="refreshCsrf">{{
              t('refreshCsrf')
            }}</Button>
            <Button
              v-if="
                session && ['conflict', 'account_restricted'].includes(failure)
              "
              @click="refresh"
              >{{ t('refresh') }}</Button
            >
          </template>
        </Form>
        <nav
          class="flex flex-wrap items-center gap-x-5 gap-y-3 text-xs"
          :aria-label="t('security')"
        >
          <a
            v-if="screen === 'login'"
            href="/auth/forgot-password"
            class="auth-link"
            >{{ t('forgotLink') }}</a
          >
          <a
            v-if="screen === 'login' && options?.public_registration"
            href="/auth/register"
            class="auth-link"
            >{{ t('register') }}</a
          >
          <a
            v-if="screen !== 'login' && !session"
            href="/auth/login"
            class="auth-link"
            >{{ t('backLogin') }}</a
          >
          <Button v-if="session" :busy="busy" variant="ghost" @click="logout">{{
            t('logout')
          }}</Button>
          <a
            v-if="
              screen === 'complete' &&
              ['verify-email', 'confirm-address'].includes(page)
            "
            href="/auth/security"
            class="auth-link"
            >{{ t('refresh') }}</a
          >
        </nav>
      </template>
    </section>
  </component>
</template>

<style scoped>
/* A programmatic reading target, not an interactive/tab-stop control. */
.auth-heading:focus {
  outline: none;
}

.auth-link {
  display: inline-flex;
  min-height: 44px;
  align-items: center;
  color: var(--runa-primary-text);
  text-decoration: underline;
  text-underline-offset: 4px;
}
.auth-link:focus-visible {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 4px;
}
.auth-link:hover {
  color: var(--runa-primary-hover);
}
/* Security steps change immediately; shared controls provide reduced-motion feedback. */
@media print {
  [data-enrollment-secret] {
    visibility: hidden;
  }
}
</style>
