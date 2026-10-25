<script setup lang="ts">
import { onMounted, ref, watch } from 'vue';
import { useDisplayPreferences } from '../composables/useDisplayPreferences';
import { initializePreferenceSession } from '../composables/useAccountPreferenceStatus';
import AppPreferenceFeedback from '../components/AppPreferenceFeedback.vue';
import { accountPreferences } from '../lib/preferenceStore';
import AppPreferenceControls from '../components/AppPreferenceControls.vue';
import AppBrandIllustration from '../components/AppBrandIllustration.vue';
import RunaLogo from '../components/RunaLogo.vue';
import AppBackdrop from '../components/AppBackdrop.vue';
import AppFooter from '../components/AppFooter.vue';
import AppLoadingOverlay from '../components/AppLoadingOverlay.vue';
import type { Locale } from '../locales/messages';
import type { Theme } from '../composables/useTheme';
import { useIdentity } from '../runa/ui/identity';
import type { FooterOptions } from '../runa/ui/identity';
const props = withDefaults(
  defineProps<{
    footer?: FooterOptions | undefined;
    illustration?: boolean;
    constellationGlow?: boolean;
    orbitMotion?: boolean;
    staticPreferences?: { locale: Locale; theme: Theme } | undefined;
  }>(),
  {
    illustration: true,
    constellationGlow: true,
    orbitMotion: true,
    footer: undefined,
    staticPreferences: undefined,
  },
);
const identity = useIdentity();
const logoFailed = ref(false);
onMounted(() => {
  if (!props.staticPreferences) initializePreferenceSession();
});
if (!props.staticPreferences)
  watch(
    () => identity.value.applicationId,
    (app) => accountPreferences.setApplication(app),
    { immediate: true },
  );
watch(
  () => identity.value.logo?.src,
  () => {
    logoFailed.value = false;
  },
);
const {
  locale,
  t,
  theme,
  themeLabel,
  languageLabel,
  changingPreference,
  coverVisible,
  changePreference,
  applyPreference,
  finishPreferenceChange,
} = useDisplayPreferences(props.staticPreferences);
</script>

<template>
  <div
    class="minimal-page flex min-h-dvh flex-col text-ink"
    :inert="changingPreference"
    :aria-busy="changingPreference"
  >
    <AppBackdrop
      :constellation-glow="constellationGlow && !staticPreferences"
    />
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
        <div class="flex max-w-full flex-wrap items-center justify-end gap-3">
          <slot name="header-actions" :t="t" />
          <AppPreferenceControls
            :navigation="props.staticPreferences !== undefined"
            :locale="locale"
            :theme="theme"
            :theme-label="themeLabel"
            :language-label="languageLabel"
            :t="t"
            @change="changePreference"
          />
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
            { 'lg:grid-cols-2 lg:items-stretch': props.illustration },
          ]"
        >
          <div
            class="order-2 min-w-0 flex flex-col justify-center px-6 py-10 sm:px-10 sm:py-16 lg:order-1"
          >
            <AppPreferenceFeedback
              v-if="!props.staticPreferences"
              :locale="locale"
            />
            <slot name="content" :t="t"><slot :t="t" /></slot>
            <div v-if="$slots.actions" class="mt-6 flex flex-wrap gap-3">
              <slot name="actions" :t="t" />
            </div>
          </div>
          <div
            v-if="props.illustration"
            class="order-1 flex min-w-0 lg:order-2"
          >
            <slot name="illustration" :t="t"
              ><AppBrandIllustration
                :animated="orbitMotion && !staticPreferences"
            /></slot>
          </div>
        </div>
      </div>
    </main>
    <slot v-if="props.footer?.visible !== false" name="footer" :t="t"
      ><AppFooter :options="props.footer" :powered-by-label="t('poweredBy')"
    /></slot>
  </div>
  <AppLoadingOverlay
    v-if="!props.staticPreferences"
    :active="coverVisible"
    :label="t('applyingPreferences')"
    @covered="applyPreference"
    @revealed="finishPreferenceChange"
  />
</template>
