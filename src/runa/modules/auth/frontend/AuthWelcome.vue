<script setup lang="ts">
import { onMounted, onScopeDispose, ref, shallowRef } from 'vue';
import { Alert, Button, LinkButton, MinimalLayout, Spinner } from '@/runa/ui';
import { useLocale } from '../../../resources/js/composables/useLocale';
import { authFailure, authGet, decodeOptions, decodeSession } from './entry';
import type { EntryOptions, EntrySession } from './entry';
const { locale } = useLocale();
const text = (pl: string, en: string) => (locale.value === 'pl' ? pl : en);
const options = shallowRef<EntryOptions>(),
  session = shallowRef<EntrySession>();
const failed = ref(false),
  loading = ref(true);
const abort = new AbortController();
async function load() {
  loading.value = true;
  failed.value = false;
  try {
    const result = decodeOptions(await authGet('entry-options', abort.signal));
    if (abort.signal.aborted) return;
    options.value = result;
    if (!result.public_welcome_enabled) {
      window.location.replace('/auth/login');
      return;
    }
    try {
      session.value = decodeSession(await authGet('session', abort.signal));
    } catch (error) {
      if (authFailure(error).code !== 'signed_out') throw error;
      session.value = undefined;
    }
  } catch {
    if (!abort.signal.aborted) failed.value = true;
  } finally {
    if (!abort.signal.aborted) loading.value = false;
  }
}
onMounted(load);
onScopeDispose(() => abort.abort());
</script>
<template>
  <MinimalLayout>
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
      <div class="mt-8" aria-live="polite">
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
          class="flex flex-wrap gap-4"
          :aria-label="text('Dostęp do aplikacji', 'Application access')"
        >
          <LinkButton
            v-if="session"
            variant="solid"
            :href="session.restrictions.length ? '/auth/security' : '/app'"
            >{{ text('Przejdź do aplikacji', 'Open application') }}</LinkButton
          >
          <template v-else>
            <LinkButton variant="solid" href="/auth/login">{{
              text('Zaloguj się', 'Sign in')
            }}</LinkButton>
            <LinkButton
              v-if="options.public_registration"
              variant="outline"
              href="/auth/register"
              >{{ text('Utwórz konto', 'Create account') }}</LinkButton
            >
          </template>
        </nav>
      </div>
    </template>
  </MinimalLayout>
</template>
