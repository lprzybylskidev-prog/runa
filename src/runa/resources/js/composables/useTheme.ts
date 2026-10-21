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
  watch(
    theme,
    (value) => {
      savePreference('runa.theme', value);
      apply();
    },
    { immediate: true },
  );
  media.addEventListener('change', apply);
  onScopeDispose(() => media.removeEventListener('change', apply));
  return { theme };
}
