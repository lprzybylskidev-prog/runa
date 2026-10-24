<script setup lang="ts">
import { computed } from 'vue';
import { useAuthPresentation } from './presentation';
import {
  Alert,
  ApplicationLayout,
  Button,
  Icon,
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
    :title="account ? text('Panel użytkownika', 'User panel') : ''"
    :user-name="session.display_name"
    :user-initials="initials"
  >
    <template #user-menu>
      <div class="account-menu">
        <div class="account-menu-identity">
          <span class="account-menu-avatar" aria-hidden="true">{{
            initials
          }}</span>
          <div class="min-w-0">
            <p class="account-menu-name">{{ session.display_name }}</p>
            <p class="account-menu-caption">
              {{ text('Twoje konto', 'Your account') }}
            </p>
          </div>
        </div>
        <div class="account-menu-actions">
          <a href="/account" class="account-menu-action">
            <Icon name="adjustments-horizontal" :size="18" />
            <span>{{ text('Panel użytkownika', 'User panel') }}</span>
          </a>
          <button
            type="button"
            class="account-menu-action account-menu-signout"
            :disabled="pending"
            :aria-busy="pending"
            @click="logout"
          >
            <Icon name="logout" :size="18" />
            <span>{{ text('Wyloguj się', 'Sign out') }}</span>
          </button>
        </div>
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
      <slot v-else :session="session" />
    </div>
  </component>
</template>

<style scoped>
.account-menu {
  width: 264px;
  max-width: 100%;
}
.account-menu-identity {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 8px 16px;
  border-bottom: 1px solid var(--runa-border);
}
.account-menu-avatar {
  display: grid;
  place-items: center;
  width: 40px;
  height: 40px;
  flex-shrink: 0;
  border-radius: 50%;
  background: var(--runa-primary-subtle);
  color: var(--runa-primary-text);
  font-size: 12px;
  font-weight: 600;
}
.account-menu-name {
  font-size: 12px;
  font-weight: 600;
  line-height: 1.6;
  overflow-wrap: anywhere;
}
.account-menu-caption {
  margin-top: 3px;
  font-size: 11px;
  color: var(--runa-text-secondary);
}
.account-menu-actions {
  display: grid;
  gap: 4px;
  padding-top: 8px;
}
.account-menu-action {
  display: flex;
  align-items: center;
  gap: 12px;
  width: 100%;
  min-height: 44px;
  padding: 10px 12px;
  border: 0;
  border-radius: 4px;
  background: transparent;
  color: var(--runa-text);
  text-align: left;
  font-size: 12px;
  text-decoration: none;
  cursor: pointer;
  transition:
    background-color 160ms ease-out,
    color 160ms ease-out;
}
.account-menu-action:hover {
  background: var(--runa-primary-subtle);
  color: var(--runa-primary-text);
}
.account-menu-action:focus-visible {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: -2px;
}
.account-menu-action:disabled {
  opacity: 0.5;
  cursor: wait;
}
.account-menu-signout {
  color: var(--runa-text-secondary);
}
@media (prefers-reduced-motion: reduce) {
  .account-menu-action {
    transition: none;
  }
}
</style>
