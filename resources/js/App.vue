<script setup lang="ts">
import { computed, onMounted, onScopeDispose, ref } from 'vue';
import AppIcon from './components/AppIcon.vue';
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
  <div class="welcome-page flex min-h-dvh flex-col text-ink">
    <a href="#main" class="skip-link">{{ t('skip') }}</a>
    <header
      class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-6 px-6 py-7 sm:px-10"
    >
      <a
        href="/"
        aria-label="Runa"
        class="brand flex min-h-11 items-center gap-3 text-xl font-semibold tracking-tight"
      >
        <span class="brand-mark" aria-hidden="true"
          ><AppIcon name="layers-intersect"
        /></span>
        runa<span class="brand-dot" aria-hidden="true">.</span>
      </a>
      <div
        :aria-label="t('preferences')"
        class="flex flex-wrap gap-4"
        role="group"
      >
        <div role="group" :aria-label="t('language')" class="preference-group">
          <button
            v-for="option in [
              { value: 'pl', label: 'PL' },
              { value: 'en', label: 'EN' },
            ]"
            :key="option.value"
            type="button"
            :aria-label="option.value === 'pl' ? 'Polski' : 'English'"
            :aria-pressed="locale === option.value"
            @click="setLocale(option.value)"
          >
            {{ option.label }}
          </button>
        </div>
        <div role="group" :aria-label="t('theme')" class="preference-group">
          <button
            v-for="option in themeOptions"
            :key="option.value"
            type="button"
            :aria-pressed="theme === option.value"
            @click="setTheme(option.value)"
          >
            {{ option.label }}
          </button>
        </div>
      </div>
    </header>
    <main
      id="main"
      tabindex="-1"
      class="mx-auto flex w-full max-w-7xl flex-1 items-center px-6 py-8 sm:px-10 sm:py-14"
    >
      <div class="welcome-panel w-full">
        <div
          class="grid gap-12 px-6 py-10 sm:px-10 sm:py-16 lg:grid-cols-2 lg:items-center lg:gap-14"
        >
          <div>
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
          </div>
          <div class="space-y-5">
            <div class="runa-art" aria-hidden="true">
              <span class="art-grid" /><span class="art-orbit orbit-one" /><span
                class="art-orbit orbit-two"
              /><span class="art-orbit orbit-three" /><span class="art-core"
                ><AppIcon name="layers-intersect" /></span
              ><span class="art-coordinate">RUNA</span
              ><span class="art-plus">+</span>
            </div>
            <section
              :aria-label="t('connection')"
              class="connection-panel"
              :class="'connection-' + state"
            >
              <div class="flex items-start gap-4">
                <span class="status-icon"
                  ><AppIcon
                    :name="
                      state === 'connected'
                        ? 'circle-check'
                        : state === 'failed'
                          ? 'alert-circle'
                          : 'loader-2'
                    "
                    :class="{ 'status-loading': state === 'checking' }"
                /></span>
                <div class="min-w-0 flex-1">
                  <h2 class="mb-2 text-xs text-muted">{{ t('connection') }}</h2>
                  <p
                    role="status"
                    aria-live="polite"
                    class="text-sm font-semibold"
                  >
                    {{ t(state) }}
                  </p>
                  <p
                    v-if="state === 'failed'"
                    class="mt-3 text-xs leading-6 text-muted"
                  >
                    {{ t('failedDescription') }}
                  </p>
                  <button
                    v-if="state === 'failed'"
                    type="button"
                    class="retry-button mt-4 inline-flex min-h-11 items-center gap-2 px-4 py-2 text-xs font-semibold"
                    @click="connect"
                  >
                    <AppIcon name="refresh" class="size-4" />{{ t('retry') }}
                  </button>
                </div>
              </div>
            </section>
          </div>
        </div>
      </div>
    </main>
    <footer
      class="mx-auto flex w-full max-w-7xl flex-wrap justify-between gap-4 px-6 py-6 text-xs text-muted sm:px-10"
    >
      <span>© {{ new Date().getFullYear() }} Runa</span>
    </footer>
  </div>
</template>
