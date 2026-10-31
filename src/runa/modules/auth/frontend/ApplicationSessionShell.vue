<script setup lang="ts">
import { computed, onScopeDispose, provide, toRef, watch } from 'vue';
import { applicationSessionOwner } from '../../../resources/js/lib/applicationSession';
import { provideLocale } from '../../../resources/js/composables/useLocale';
import { useAdminEntry } from './useAdminEntry';
import { useAuthPresentation } from './presentation';
import {
  Alert,
  Avatar,
  Breadcrumbs,
  ApplicationLayout,
  MinimalLayout,
  Button,
  Icon,
  Skeleton,
  useImageReadiness,
} from '@/runa/ui';
import type { NavigationEntry } from '@/runa/ui';
import { useLocale } from '../../../resources/js/composables/useLocale';
import AuthEntry from './AuthEntry.vue';
const presentation = useAuthPresentation();
import type {
  ApplicationSessionShellProps,
  ApplicationSessionShellSlots,
} from './contracts/presentation';
defineSlots<ApplicationSessionShellSlots>();
const props = defineProps<ApplicationSessionShellProps>();
provideLocale(toRef(props, 'locale'));
provide(applicationSessionOwner, true);
const previousLanguage = document.documentElement.lang;
watch(
  () => props.locale,
  (value) => {
    if (value) document.documentElement.lang = value;
  },
  { immediate: true },
);
onScopeDispose(() => {
  if (props.locale) document.documentElement.lang = previousLanguage;
});
const adminEntry = useAdminEntry(props.session);
const { locale } = useLocale(toRef(props, 'locale'));
const text = (pl: string, en: string) =>
  (props.locale ?? locale.value) === 'pl' ? pl : en;
const {
  session,
  profile,
  profileLoading,
  welcomeEnabled,
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
} = props.session;
const navigation = computed<readonly NavigationEntry[]>(
  () =>
    props.navigation ?? [
      {
        key: 'application',
        routeKey: 'application',
        moduleKey: 'application',
        href: '/app',
        label: text('Aplikacja', 'Application'),
        icon: 'layers-intersect',
        availability: 'available',
      },
    ],
);
const current = computed(
  () =>
    props.current ?? {
      routeKey: 'application',
      moduleKey: 'application',
      href: '/app',
    },
);
const account = computed(() => current.value.routeKey === 'account');
const entries = computed(() =>
  (props.accountMenu ?? []).filter((entry) => {
    try {
      const url = new URL(entry.href, window.location.origin);
      return (
        entry.href.startsWith('/') &&
        !entry.href.startsWith('//') &&
        !/[\\\r\n]/.test(entry.href) &&
        url.origin === window.location.origin
      );
    } catch {
      return false;
    }
  }),
);
const userName = computed(
  () => profile.value?.display_name ?? session.value?.display_name ?? '',
);
const avatar = computed(() =>
  profile.value?.has_avatar
    ? `/api/runa/auth/profile/avatar?v=${profile.value.version}&account=${profile.value.public_id}`
    : '',
);
const initials = computed(
  () =>
    profile.value?.initials ??
    session.value?.display_name
      .split(/\s+/)
      .map((v) => v.slice(0, 1))
      .filter(Boolean)
      .slice(0, 2)
      .join('')
      .toLocaleUpperCase() ??
    '',
);
const identity = computed(() =>
  session.value && !paused.value
    ? JSON.stringify([session.value.public_id, session.value.session.public_id])
    : null,
);
const avatarImage = useImageReadiness(avatar, identity);
</script>
<template>
  <AuthEntry v-if="paused" page="login" :authenticated="authenticated" />
  <component
    v-else-if="!loading && !session"
    :is="presentation.minimalLayout ?? MinimalLayout"
  >
    <Alert tone="danger">
      {{ text('Nie można wczytać sesji.', 'Session could not be loaded.') }}
      <Button @click="refresh">{{ text('Ponów', 'Retry') }}</Button>
    </Alert>
  </component>
  <slot v-else-if="$slots.admission" name="admission" />
  <component
    :is="presentation.applicationLayout ?? ApplicationLayout"
    v-else
    width="fluid"
    :home-href="welcomeEnabled ? '/' : '/app'"
    :navigation="navigation"
    :top-navigation="topNavigation ?? []"
    :locale="props.locale"
    :current="current"
    :title="account ? text('Panel użytkownika', 'User panel') : ''"
    :user-name="userName"
    :user-avatar="avatar"
    :user-avatar-image="avatarImage"
    :user-loading="loading || profileLoading"
    :user-initials="initials"
  >
    <template #breadcrumbs>
      <Breadcrumbs
        :label="text('Ścieżka nawigacji', 'Breadcrumbs')"
        :items="[
          ...(welcomeEnabled
            ? [{ label: text('Strona główna', 'Home'), href: '/' }]
            : []),
          { label: text('Aplikacja', 'Application'), href: '/app' },
          ...(account
            ? [{ label: text('Panel użytkownika', 'User panel') }]
            : []),
        ]"
      />
    </template>
    <template v-if="session && !loading" #user-menu>
      <div class="account-menu">
        <div class="account-menu-identity">
          <Avatar
            :src="avatar"
            :image="avatarImage"
            :initials="initials"
            :size="40"
            :loading="loading || profileLoading"
          />
          <div class="min-w-0">
            <p class="account-menu-name">{{ userName }}</p>
          </div>
        </div>
        <div class="account-menu-actions">
          <a href="/account" class="account-menu-action">
            <Icon name="adjustments-horizontal" :size="18" />
            <span>{{ text('Panel użytkownika', 'User panel') }}</span>
          </a>
          <a
            v-if="adminEntry"
            href="/admin"
            class="account-menu-action"
            :aria-current="
              current.moduleKey === 'runa.admin' ? 'page' : undefined
            "
          >
            <Icon name="adjustments-horizontal" :size="18" />
            <span>{{
              text('Panel administratora', 'Administrator panel')
            }}</span>
          </a>
          <slot name="account-menu" :session="session" :entries="entries">
            <a
              v-for="entry in entries"
              :key="entry.key"
              :href="entry.href"
              class="account-menu-action"
              :aria-current="entry.current ? 'page' : undefined"
              >{{ entry.label }}</a
            >
          </slot>
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
    <slot v-if="loading" name="loading" :account="account"
      ><div aria-busy="true" class="space-y-6">
        <p role="status" class="sr-only">
          {{ text('Wczytywanie aplikacji', 'Loading application') }}
        </p>
        <Skeleton :lines="3" /></div
    ></slot>
    <Alert v-else-if="!session" tone="danger">
      {{ text('Nie można wczytać sesji.', 'Session could not be loaded.') }}
      <Button @click="refresh">{{ text('Ponów', 'Retry') }}</Button>
    </Alert>
    <div
      v-else
      :key="identity ?? undefined"
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
      <slot :session="session" />
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
.account-menu-name {
  font-size: 12px;
  font-weight: 600;
  line-height: 1.6;
  overflow-wrap: anywhere;
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
