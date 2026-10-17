<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from './components/AppIcon.vue';
import AppTooltip from './components/AppTooltip.vue';
import { useLocale } from './composables/useLocale';
import { useTheme } from './composables/useTheme';
const { locale, t } = useLocale();
const { theme } = useTheme();
const nextTheme = computed(() =>
  theme.value === 'system'
    ? 'light'
    : theme.value === 'light'
      ? 'dark'
      : 'system',
);
const themeLabel = computed(
  () =>
    `${t('theme')}: ${t(theme.value)}. ${t('switchTo')}: ${t(nextTheme.value)}`,
);
const languageLabel = computed(
  () =>
    `${t('language')}: ${locale.value === 'pl' ? 'Polski' : 'English'}. ${t('switchTo')}: ${locale.value === 'pl' ? 'English' : 'Polski'}`,
);
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
      <div :aria-label="t('preferences')" class="flex gap-2" role="group">
        <AppTooltip :text="languageLabel">
          <template #default="{ descriptionId }">
            <button
              type="button"
              class="preference-button"
              :aria-label="languageLabel"
              :aria-describedby="descriptionId"
              @click="locale = locale === 'pl' ? 'en' : 'pl'"
            >
              {{ locale.toUpperCase() }}
            </button>
          </template>
        </AppTooltip>
        <AppTooltip :text="themeLabel">
          <template #default="{ descriptionId }">
            <button
              type="button"
              class="preference-button"
              :aria-label="themeLabel"
              :aria-describedby="descriptionId"
              @click="theme = nextTheme"
            >
              <AppIcon
                :name="
                  theme === 'system'
                    ? 'device-desktop'
                    : theme === 'light'
                      ? 'sun'
                      : 'moon'
                "
              />
            </button>
          </template>
        </AppTooltip>
      </div>
    </header>
    <main
      id="main"
      tabindex="-1"
      class="mx-auto flex w-full max-w-7xl flex-1 items-center px-6 py-8 sm:px-10 sm:py-14"
    >
      <div class="welcome-panel w-full">
        <div
          class="grid gap-12 px-6 py-10 sm:px-10 sm:py-16 lg:grid-cols-2 lg:items-stretch lg:gap-14"
        >
          <div class="flex flex-col justify-center">
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
          <div class="flex">
            <div class="runa-art" aria-hidden="true">
              <span class="art-grid" /><span class="art-orbit orbit-one" /><span
                class="art-orbit orbit-two"
              /><span class="art-orbit orbit-three" /><span class="art-core"
                ><AppIcon name="layers-intersect" /></span
              ><span class="art-coordinate">RUNA</span
              ><span class="art-plus">+</span>
            </div>
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
