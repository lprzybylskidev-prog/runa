import { computed, nextTick, onScopeDispose, ref } from 'vue';
import { useLocale } from './useLocale';
import { useTheme } from './useTheme';

export function useDisplayPreferences() {
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
  return {
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
  };
}
