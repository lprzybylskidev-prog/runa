<script setup lang="ts">
import { Alert, Button, MinimalLayout, Spinner } from '@/runa/ui';
import { useAuthWelcome } from './useAuthWelcome';
import { useAuthPresentation } from './presentation';
const presentation = useAuthPresentation();
const { text, options, session, failed, loading, load } = useAuthWelcome();
</script>
<template>
  <component :is="presentation.minimalLayout ?? MinimalLayout">
    <template #header-actions>
      <div class="max-w-full" aria-live="polite">
        <Spinner
          v-if="loading"
          :label="text('Sprawdzanie sesji', 'Checking session')"
        />
        <Alert v-else-if="failed" tone="danger"
          >{{
            text(
              'Nie udało się pobrać opcji logowania.',
              'Sign-in options could not be loaded.',
            )
          }}
          <Button @click="load">{{ text('Ponów', 'Retry') }}</Button></Alert
        >
        <nav
          v-else-if="options"
          class="flex flex-wrap items-center gap-4"
          :aria-label="text('Dostęp do aplikacji', 'Application access')"
        >
          <a
            v-if="session"
            class="welcome-header-link"
            :href="session.restrictions.length ? '/auth/security' : '/app'"
            >{{ text('Przejdź do aplikacji', 'Open application') }}</a
          >
          <template v-else>
            <a class="welcome-header-link" href="/auth/login">{{
              text('Zaloguj się', 'Sign in')
            }}</a>
            <a
              v-if="options.public_registration"
              class="welcome-header-link"
              href="/auth/register"
              >{{ text('Utwórz konto', 'Create account') }}</a
            >
          </template>
        </nav>
      </div>
    </template>
    <template #default="{ t }">
      <h1
        class="max-w-xl text-4xl font-semibold leading-tight tracking-tight sm:text-5xl xl:text-6xl"
      >
        {{ t('title') }}
      </h1>
      <p
        class="mt-6 max-w-lg text-sm leading-7 text-muted sm:text-base sm:leading-8"
      >
        {{ t('description') }}
      </p>
    </template>
  </component>
</template>

<style scoped>
.welcome-header-link {
  display: inline-flex;
  min-height: 44px;
  align-items: center;
  color: var(--runa-text-secondary);
  font-size: 12px;
  text-decoration: none;
  text-underline-offset: 4px;
  transition: color 160ms ease-out;
}
.welcome-header-link:hover {
  color: var(--runa-primary-text);
  text-decoration: underline;
}
.welcome-header-link:focus-visible {
  color: var(--runa-primary-text);
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 4px;
  border-radius: 2px;
}
@media (prefers-reduced-motion: reduce) {
  .welcome-header-link {
    transition: none;
  }
}
</style>
