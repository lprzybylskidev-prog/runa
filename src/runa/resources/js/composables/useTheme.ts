import { useApplicationDisplayDefaults } from '../runa/ui/displayDefaults';
import { defaultPreferenceStore } from '../../../modules/auth/frontend/preferences/session';
import { onScopeDispose, ref, watch } from 'vue';
import { readPreference, savePreference } from '../lib/preferences';

export type Theme = 'light' | 'dark' | 'system';

export function useTheme() {
  const defaults = useApplicationDisplayDefaults();
  const resolve = (): Theme => {
    const saved = readPreference('runa.theme');
    return saved === 'light' || saved === 'dark' || saved === 'system'
      ? saved
      : defaults.value.theme;
  };
  const theme = ref<Theme>(resolve());
  const media = window.matchMedia('(prefers-color-scheme: dark)');
  function apply() {
    document.documentElement.dataset.theme =
      theme.value === 'system'
        ? media.matches
          ? 'dark'
          : 'light'
        : theme.value;
  }
  let syncing = false;
  const unsubscribe = defaultPreferenceStore.subscribe?.(() => {
    syncing = true;
    theme.value = resolve();
    syncing = false;
  });
  if (unsubscribe) onScopeDispose(unsubscribe);
  watch(
    defaults,
    () => {
      syncing = true;
      theme.value = resolve();
      syncing = false;
    },
    { flush: 'sync' },
  );
  // Initial rendering is not a persisted preference change.
  apply();
  watch(
    theme,
    (value) => {
      if (!syncing) savePreference('runa.theme', value);
      apply();
    },
    { flush: 'sync' },
  );
  media.addEventListener('change', apply);
  onScopeDispose(() => media.removeEventListener('change', apply));
  return { theme };
}
