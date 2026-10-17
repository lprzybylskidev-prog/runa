<script setup lang="ts">
import { computed } from 'vue';
import AppIcon from '../components/AppIcon.vue';
import AppButton from '../components/AppButton.vue';
import AppFooter from '../components/AppFooter.vue';
import AppTooltip from '../components/AppTooltip.vue';
import { useLocale } from '../composables/useLocale';
import { useTheme } from '../composables/useTheme';
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
  <div class="auth-page flex min-h-dvh flex-col text-ink">
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
        <AppTooltip :text="t('changeLanguage')">
          <template #default="{ descriptionId }">
            <AppButton
              compact
              :aria-label="languageLabel"
              :aria-describedby="descriptionId"
              @click="locale = locale === 'pl' ? 'en' : 'pl'"
            >
              {{ locale.toUpperCase() }}
            </AppButton>
          </template>
        </AppTooltip>
        <AppTooltip :text="t('changeTheme')">
          <template #default="{ descriptionId }">
            <AppButton
              compact
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
            </AppButton>
          </template>
        </AppTooltip>
      </div>
    </header>
    <main
      id="main"
      tabindex="-1"
      class="mx-auto flex w-full max-w-7xl flex-1 items-center px-6 py-8 sm:px-10 sm:py-14"
    >
      <div class="auth-panel w-full">
        <div class="grid lg:grid-cols-2 lg:items-stretch">
          <div
            class="flex flex-col justify-center px-6 py-10 sm:px-10 sm:py-16"
          >
            <slot :t="t" />
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
    <AppFooter :version-label="t('version')" />
  </div>
</template>
