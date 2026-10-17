<script setup lang="ts">
import { computed, onMounted, onScopeDispose, ref } from 'vue';
import AppSelect from './components/AppSelect.vue';
import AppForm from './components/AppForm.vue';
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
      <AppForm
        :aria-label="t('preferences')"
        class="flex flex-wrap gap-3 sm:gap-5"
      >
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
    </header>
    <main
      id="main"
      tabindex="-1"
      class="mx-auto flex w-full max-w-7xl flex-1 items-center px-6 py-8 sm:px-10 sm:py-14"
    >
      <div class="welcome-panel w-full">
        <div
          class="panel-topline flex items-center justify-between gap-4 px-6 py-4 sm:px-10"
        >
          <span class="flex items-center gap-2 text-xs text-muted"
            ><AppIcon name="sparkles" class="size-4 text-primary" />{{
              t('eyebrow')
            }}</span
          >
          <span class="text-xs text-muted" aria-hidden="true">R / 01</span>
        </div>
        <div
          class="grid gap-12 px-6 py-10 sm:px-10 sm:py-16 lg:grid-cols-2 lg:items-center lg:gap-14"
        >
          <div>
            <p class="mb-5 text-xs uppercase tracking-widest text-primary">
              {{ t('welcomeLabel') }}
            </p>
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
            <div class="mt-9 flex items-center gap-3 text-xs text-muted">
              <span class="accent-rule" aria-hidden="true" />{{ t('footer') }}
            </div>
          </div>
          <div class="space-y-5">
            <div class="runa-art" aria-hidden="true">
              <span class="art-grid" /><span class="art-orbit orbit-one" /><span
                class="art-orbit orbit-two"
              /><span class="art-orbit orbit-three" /><span class="art-core"
                ><AppIcon name="layers-intersect" /></span
              ><span class="art-coordinate">RUNA / {{ t('workspace') }}</span
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
        <div
          class="panel-bottom flex flex-wrap items-center justify-between gap-3 px-6 py-4 text-xs text-muted sm:px-10"
        >
          <span>{{ t('foundation') }}</span
          ><span class="flex items-center gap-2"
            ><span
              class="size-1.5 rounded-full bg-primary"
              aria-hidden="true"
            />{{ t('growing') }}</span
          >
        </div>
      </div>
    </main>
    <footer
      class="mx-auto flex w-full max-w-7xl flex-wrap justify-between gap-4 px-6 py-6 text-xs text-muted sm:px-10"
    >
      <span>© {{ new Date().getFullYear() }} Runa</span
      ><span>{{ t('footer') }}</span>
    </footer>
  </div>
</template>
