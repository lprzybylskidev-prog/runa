<script setup lang="ts">
import { computed, onMounted, onScopeDispose, ref } from 'vue';
import AppSelect from './components/AppSelect.vue';
import AppForm from './components/AppForm.vue';
import { useLocale } from './composables/useLocale';
import { useTheme } from './composables/useTheme';
import { checkHealth } from './lib/http';

const { locale, t } = useLocale();
const { theme } = useTheme();
const state = ref<'checking' | 'connected' | 'failed'>('checking');
let controller: AbortController | undefined;
const themeOptions = computed(() =>
  (['system', 'light', 'dark'] as const).map((value) => ({
    value,
    label: t(value),
  })),
);
function setLocale(value: string) {
  if (value === 'pl' || value === 'en') locale.value = value;
}
function setTheme(value: string) {
  if (value === 'system' || value === 'light' || value === 'dark')
    theme.value = value;
}
async function connect() {
  controller?.abort();
  const current = new AbortController();
  controller = current;
  state.value = 'checking';
  try {
    await checkHealth(current.signal);
    if (!current.signal.aborted) state.value = 'connected';
  } catch {
    if (!current.signal.aborted) state.value = 'failed';
  }
}
onMounted(connect);
onScopeDispose(() => controller?.abort());
</script>

<template>
  <div class="flex min-h-dvh flex-col bg-canvas text-ink">
    <a
      href="#main"
      class="sr-only rounded-lg bg-accent p-3 text-canvas focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-10"
      >{{ t('skip') }}</a
    >
    <header class="border-b border-line">
      <div
        class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-6 px-6 py-6 sm:px-10"
      >
        <a
          href="/"
          aria-label="Runa"
          class="flex min-h-11 items-center gap-3 text-xl font-semibold tracking-tight"
        >
          <span
            aria-hidden="true"
            class="flex size-9 items-center justify-center rounded-xl bg-accent text-base text-canvas"
            >r</span
          >Runa
        </a>
        <AppForm :aria-label="t('preferences')" class="flex flex-wrap gap-3">
          <AppSelect
            :label="t('language')"
            :model-value="locale"
            :options="[
              { value: 'pl', label: 'Polski' },
              { value: 'en', label: 'English' },
            ]"
            @update:model-value="setLocale"
          />
          <AppSelect
            :label="t('theme')"
            :model-value="theme"
            :options="themeOptions"
            @update:model-value="setTheme"
          />
        </AppForm>
      </div>
    </header>
    <main
      id="main"
      tabindex="-1"
      class="mx-auto flex w-full max-w-6xl flex-1 flex-col justify-center px-6 py-16 sm:px-10 sm:py-24"
    >
      <div class="max-w-2xl">
        <p class="mb-5 text-sm font-medium text-accent">{{ t('eyebrow') }}</p>
        <h1 class="text-4xl font-semibold tracking-tight sm:text-6xl">
          {{ t('title') }}
        </h1>
        <p class="mt-6 max-w-xl text-lg leading-relaxed text-muted">
          {{ t('description') }}
        </p>
        <section
          :aria-label="t('connection')"
          class="mt-12 border-t border-line pt-6"
        >
          <p
            role="status"
            aria-live="polite"
            class="flex items-center gap-3 text-sm font-medium"
          >
            <span
              aria-hidden="true"
              class="size-2 rounded-full"
              :class="state === 'connected' ? 'bg-accent' : 'bg-muted'"
            />{{ t(state) }}
          </p>
          <template v-if="state === 'failed'">
            <p class="mt-2 text-sm text-muted">{{ t('failedDescription') }}</p>
            <button
              type="button"
              class="mt-4 min-h-11 rounded-lg border border-control bg-surface px-4 py-2 text-sm font-medium hover:border-accent"
              @click="connect"
            >
              {{ t('retry') }}
            </button>
          </template>
        </section>
      </div>
    </main>
    <footer
      class="mx-auto flex w-full max-w-6xl flex-wrap justify-between gap-3 px-6 py-6 text-xs text-muted sm:px-10"
    >
      <span>Runa</span><span>{{ t('footer') }}</span>
    </footer>
  </div>
</template>
