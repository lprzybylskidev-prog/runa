import { defaultPreferenceStore } from '../lib/preferenceStore';
import { onScopeDispose, ref, watch } from 'vue';
import { readPreference, savePreference } from '../lib/preferences';

export type Theme = 'light' | 'dark' | 'system';

export function useTheme() {
  const saved = readPreference('runa.theme');
  const theme = ref<Theme>(
    saved === 'light' || saved === 'dark' ? saved : 'system',
  );
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
    const saved = readPreference('runa.theme');
    syncing = true;
    theme.value = saved === 'light' || saved === 'dark' ? saved : 'system';
    syncing = false;
  });
  if (unsubscribe) onScopeDispose(unsubscribe);
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
