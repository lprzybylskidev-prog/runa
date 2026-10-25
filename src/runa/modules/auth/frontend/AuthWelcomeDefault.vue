<script setup lang="ts">
import {
  Alert,
  Button,
  TextLink,
  MinimalLayout,
  Spinner,
  Icon,
  Tooltip,
} from '@/runa/ui';
import { welcomeModules } from '@runa/lib/welcomeModules';
import { useAuthWelcome } from './useAuthWelcome';
import { useAuthPresentation } from './presentation';
const presentation = useAuthPresentation();
const { text, options, session, failed, loading, load } = useAuthWelcome();
</script>
<template>
  <component
    :is="presentation.minimalLayout ?? MinimalLayout"
    :home-href="options?.public_welcome_enabled ? '/' : '/app'"
  >
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
      <ul
        class="mt-7 flex flex-wrap gap-3"
        :aria-label="text('Moduły Runa', 'Runa modules')"
      >
        <li v-for="module in welcomeModules" :key="module.id">
          <Tooltip
            :text="`${text(module.name.pl, module.name.en)} — ${text(module.description.pl, module.description.en)}`"
          >
            <template #default="{ descriptionId }">
              <span
                tabindex="0"
                role="img"
                class="brand-mark welcome-module"
                :aria-label="text(module.name.pl, module.name.en)"
                :aria-describedby="descriptionId"
              >
                <Icon :name="module.icon" :size="26" />
              </span>
            </template>
          </Tooltip>
        </li>
      </ul>
    </template>
  </component>
</template>

<style scoped>
.welcome-module {
  transition:
    border-color 160ms ease-out,
    background-color 160ms ease-out;
}
.welcome-module:hover,
.welcome-module:focus-visible {
  border-color: var(--runa-primary-text);
}
.welcome-module:focus-visible {
  outline: 2px solid var(--runa-focus-ring);
  outline-offset: 3px;
}
@media (prefers-reduced-motion: reduce), print {
  .welcome-module {
    transition: none;
  }
}
</style>
