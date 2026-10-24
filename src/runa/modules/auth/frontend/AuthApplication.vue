<script setup lang="ts">
import { computed } from 'vue';
import { useAuthPresentation } from './presentation';
import {
  Alert,
  ApplicationLayout,
  Button,
  LinkButton,
  MinimalLayout,
  Spinner,
} from '@/runa/ui';
import type { NavigationEntry } from '@/runa/ui';
import { useLocale } from '../../../resources/js/composables/useLocale';
import AuthEntry from './AuthEntry.vue';
import AuthAccount from './AuthAccount.vue';
import { useApplicationSession } from './useApplicationSession';
const presentation = useAuthPresentation();
const props = withDefaults(defineProps<{ account?: boolean }>(), {
  account: false,
});
const { locale } = useLocale();
const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
const {
  session,
  paused,
  loading,
  failure,
  pending,
  warning,
  remaining,
  refresh,
  logout,
  authenticated,
  activity,
} = useApplicationSession();
const navigation = computed<NavigationEntry[]>(() => [
  {
    key: 'application',
    routeKey: 'application',
    moduleKey: 'application',
    href: '/app',
    label: text('Aplikacja', 'Application'),
    icon: 'layers-intersect',
    availability: 'available',
  },
  {
    key: 'account',
    routeKey: 'account',
    moduleKey: 'runa.auth',
    href: '/account',
    label: text('Moje konto', 'My account'),
    icon: 'adjustments-horizontal',
    availability: 'available',
  },
]);
const current = computed(() => ({
  routeKey: props.account ? 'account' : 'application',
  moduleKey: props.account ? 'runa.auth' : 'application',
  href: props.account ? '/account' : '/app',
}));
const initials = computed(
  () =>
    session.value?.display_name
      .split(/\s+/)
      .map((v) => v.slice(0, 1))
      .filter(Boolean)
      .slice(0, 2)
      .join('')
      .toLocaleUpperCase() ?? '',
);
</script>
<template>
  <AuthEntry v-if="paused" page="login" :authenticated="authenticated" />
  <component
    :is="presentation.minimalLayout ?? MinimalLayout"
    v-else-if="loading || !session"
  >
    <Spinner
      v-if="loading"
      :label="text('Wczytywanie konta', 'Loading account')"
    />
    <Alert v-else tone="danger"
      >{{ text('Nie można wczytać sesji.', 'Session could not be loaded.') }}
      <Button @click="refresh">{{ text('Ponów', 'Retry') }}</Button></Alert
    >
  </component>
  <component
    :is="presentation.applicationLayout ?? ApplicationLayout"
    v-else
    :navigation="navigation"
    :current="current"
    :title="
      account
        ? text('Moje konto', 'My account')
        : text('Aplikacja', 'Application')
    "
    :user-name="session.display_name"
    :user-initials="initials"
  >
    <template #user-menu>
      <div class="grid min-w-48 gap-2">
        <p class="px-2 text-sm font-semibold [overflow-wrap:anywhere]">
          {{ session.display_name }}
        </p>
        <LinkButton class="justify-start" variant="ghost" href="/account">{{
          text('Moje konto', 'My account')
        }}</LinkButton>
        <Button variant="ghost" :busy="pending" @click="logout">{{
          text('Wyloguj się', 'Sign out')
        }}</Button>
      </div>
    </template>
    <div
      :key="session.public_id"
      class="space-y-6"
      :inert="pending || undefined"
    >
      <Alert v-if="failure" tone="danger" role="alert"
        >{{
          text(
            'Operacja nie powiodła się. Odśwież sesję i spróbuj ponownie.',
            'The operation failed. Refresh the session and try again.',
          )
        }}
        <Button @click="refresh">{{
          text('Odśwież sesję', 'Refresh session')
        }}</Button></Alert
      >
      <Alert v-if="warning" tone="warning" role="status">
        {{ text('Sesja wkrótce wygaśnie.', 'Your session will expire soon.') }}
        {{ Math.ceil(remaining / 1000) }} s.
        <Button v-if="session.activity.extension" @click="activity('click')">{{
          text('Kontynuuj pracę', 'Continue working')
        }}</Button>
      </Alert>
      <AuthAccount
        v-if="account"
        :key="session.public_id"
        :account-id="session.public_id"
      />
      <slot v-else :session="session">
        <section
          class="rounded-lg border border-[var(--runa-border)] bg-[var(--runa-surface)] p-6"
        >
          <h2 class="text-lg font-semibold">
            {{ text('Witaj,', 'Welcome,') }} {{ session.display_name }}
          </h2>
          <p class="mt-3 text-sm text-muted">
            {{
              text(
                'Ustawienia profilu, zabezpieczenia i aktywne sesje znajdziesz w swoim koncie.',
                'Your account contains profile settings, security and active sessions.',
              )
            }}
          </p>
          <LinkButton class="mt-5" href="/account">{{
            text('Moje konto', 'My account')
          }}</LinkButton>
        </section>
      </slot>
    </div>
  </component>
</template>
