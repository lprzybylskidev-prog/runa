<script setup lang="ts">
import { computed, nextTick, onScopeDispose, ref, watch } from 'vue';
import AppIcon from '../components/AppIcon.vue';
import RunaLogo from '../components/RunaLogo.vue';
import AppButton from '../components/AppButton.vue';
import AppBackdrop from '../components/AppBackdrop.vue';
import AppFooter from '../components/AppFooter.vue';
import AppLoadingOverlay from '../components/AppLoadingOverlay.vue';
import AppTooltip from '../components/AppTooltip.vue';
import { useLocale } from '../composables/useLocale';
import { useIdentity } from '../runa/ui/identity';
import type { FooterOptions } from '../runa/ui/identity';
import { useTheme } from '../composables/useTheme';
const props = defineProps<{ footer?: FooterOptions | undefined }>();
const identity = useIdentity();
const logoFailed = ref(false);
watch(
  () => identity.value.logo?.src,
  () => {
    logoFailed.value = false;
  },
);
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
const changingPreference = ref(false);
const coverVisible = ref(false);
let pendingPreference: 'language' | 'theme' | undefined;
let trigger: globalThis.HTMLElement | undefined;
let frame = 0;
let revealTimer: ReturnType<typeof window.setTimeout> | undefined;
let disposed = false;
onScopeDispose(() => {
  disposed = true;
  window.cancelAnimationFrame(frame);
  window.clearTimeout(revealTimer);
});
function changePreference(preference: 'language' | 'theme', event: Event) {
  if (changingPreference.value) return;
  trigger =
    event.currentTarget instanceof window.HTMLElement
      ? event.currentTarget
      : undefined;
  pendingPreference = preference;
  changingPreference.value = true;
  coverVisible.value = true;
}
async function applyPreference() {
  if (pendingPreference === 'language')
    locale.value = locale.value === 'pl' ? 'en' : 'pl';
  else if (pendingPreference === 'theme') theme.value = nextTheme.value;
  pendingPreference = undefined;
  await nextTick();
  // Keep the opaque cover through a paint of the newly selected theme/locale.
  if (disposed) return;
  frame = window.requestAnimationFrame(() => {
    frame = window.requestAnimationFrame(() => {
      revealTimer = window.setTimeout(() => {
        coverVisible.value = false;
      }, 500);
    });
  });
}
async function finishPreferenceChange() {
  changingPreference.value = false;
  await nextTick();
  trigger?.focus({ preventScroll: true });
  trigger = undefined;
}
</script>

<template>
  <div
    class="minimal-page flex min-h-dvh flex-col text-ink"
    :inert="changingPreference"
    :aria-busy="changingPreference"
  >
    <AppBackdrop />
    <a href="#main" class="skip-link">{{ t('skip') }}</a>
    <header
      class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-6 px-6 py-7 sm:px-10"
    >
      <slot name="header" :t="t">
        <a
          href="/"
          :aria-label="identity.applicationName"
          class="brand flex min-h-11 items-center gap-3 text-xl font-semibold tracking-tight"
        >
          <img
            v-if="identity.logo && !logoFailed"
            :key="identity.logo.src"
            :src="identity.logo.src"
            :alt="identity.logo.accessibleName"
            class="h-7 max-w-32 object-contain"
            @error="logoFailed = true"
          />
          <span v-else class="brand-mark" aria-hidden="true"><RunaLogo /></span>
          <template
            v-if="
              identity.applicationId === 'runa' &&
              identity.applicationName === 'Runa'
            "
            >runa<span class="brand-dot" aria-hidden="true">.</span></template
          >
          <span v-else class="break-words">{{ identity.applicationName }}</span>
        </a>
        <div :aria-label="t('preferences')" class="flex gap-2" role="group">
          <AppTooltip :text="t('changeLanguage')">
            <template #default="{ descriptionId }">
              <AppButton
                compact
                :aria-label="languageLabel"
                :aria-describedby="descriptionId"
                @click="changePreference('language', $event)"
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
                @click="changePreference('theme', $event)"
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
      </slot>
    </header>
    <main
      id="main"
      tabindex="-1"
      class="mx-auto flex w-full max-w-7xl flex-1 items-center px-6 py-8 sm:px-10 sm:py-14"
    >
      <div class="minimal-panel w-full">
        <div
          :class="[
            'grid',
            { 'lg:grid-cols-2 lg:items-stretch': $slots.illustration },
          ]"
        >
          <div
            class="min-w-0 flex flex-col justify-center px-6 py-10 sm:px-10 sm:py-16"
          >
            <slot name="content" :t="t"><slot :t="t" /></slot>
            <div v-if="$slots.actions" class="mt-6 flex flex-wrap gap-3">
              <slot name="actions" :t="t" />
            </div>
          </div>
          <div v-if="$slots.illustration" class="flex min-w-0">
            <slot name="illustration" :t="t" />
          </div>
        </div>
      </div>
    </main>
    <slot v-if="props.footer?.visible !== false" name="footer" :t="t"
      ><AppFooter :options="props.footer"
    /></slot>
  </div>
  <AppLoadingOverlay
    :active="coverVisible"
    :label="t('applyingPreferences')"
    @covered="applyPreference"
    @revealed="finishPreferenceChange"
  />
</template>
