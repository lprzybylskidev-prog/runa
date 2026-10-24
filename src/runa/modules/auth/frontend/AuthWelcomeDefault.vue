<script setup lang="ts">
import { Alert, Button, TextLink, MinimalLayout, Spinner } from '@/runa/ui';
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
          <TextLink
            v-if="session"
            :href="session.restrictions.length ? '/auth/security' : '/app'"
            >{{ text('Przejdź do aplikacji', 'Open application') }}</TextLink
          >
          <template v-else>
            <TextLink href="/auth/login">{{
              text('Zaloguj się', 'Sign in')
            }}</TextLink>
            <TextLink
              v-if="options.public_registration"
              href="/auth/register"
              >{{ text('Utwórz konto', 'Create account') }}</TextLink
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
