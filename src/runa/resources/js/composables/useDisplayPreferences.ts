import { computed, nextTick, onScopeDispose, ref } from 'vue';
import { useLocale } from './useLocale';
import { useTheme } from './useTheme';
import type { Theme } from './useTheme';
import { messages } from '../locales/messages';
import type { Locale, MessageKey } from '../locales/messages';

export function useDisplayPreferences(staticPreferences?: {
  locale: Locale;
  theme: Theme;
}) {
  const { locale, t } = staticPreferences
    ? {
        locale: ref(staticPreferences.locale),
        t: (key: MessageKey) => messages[staticPreferences.locale][key],
      }
    : useLocale();
  const { theme } = staticPreferences
    ? { theme: ref(staticPreferences.theme) }
    : useTheme();
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
  let pendingValue: Locale | Theme | undefined;
  let trigger: globalThis.HTMLElement | undefined;
  let frame = 0;
  let revealTimer: ReturnType<typeof window.setTimeout> | undefined;
  let disposed = false;
  onScopeDispose(() => {
    disposed = true;
    if (typeof window !== 'undefined') {
      window.cancelAnimationFrame(frame);
      window.clearTimeout(revealTimer);
    }
  });
  function selectPreference(
    preference: 'language' | 'theme',
    value: string,
    origin?: globalThis.HTMLElement,
  ) {
    const valid =
      preference === 'language' ? ['pl', 'en'] : ['system', 'light', 'dark'];
    const current = preference === 'language' ? locale.value : theme.value;
    if (changingPreference.value || !valid.includes(value) || current === value)
      return;
    trigger = origin;
    pendingPreference = preference;
    pendingValue = value as Locale | Theme;
    changingPreference.value = true;
    coverVisible.value = true;
  }
  function changePreference(preference: 'language' | 'theme', event: Event) {
    selectPreference(
      preference,
      preference === 'language'
        ? locale.value === 'pl'
          ? 'en'
          : 'pl'
        : nextTheme.value,
      event.currentTarget instanceof window.HTMLElement
        ? event.currentTarget
        : undefined,
    );
  }
  async function applyPreference() {
    if (pendingPreference === 'language') locale.value = pendingValue as Locale;
    else if (pendingPreference === 'theme') theme.value = pendingValue as Theme;
    pendingPreference = undefined;
    pendingValue = undefined;
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
  return {
    locale,
    t,
    theme,
    themeLabel,
    languageLabel,
    changingPreference,
    coverVisible,
    changePreference,
    selectPreference,
    applyPreference,
    finishPreferenceChange,
  };
}
