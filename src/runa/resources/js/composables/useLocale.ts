import { defaultPreferenceStore } from '../lib/preferenceStore';
import { onScopeDispose, ref, watch } from 'vue';
import { messages } from '../locales/messages';
import type { Locale, MessageKey } from '../locales/messages';
import { readPreference, savePreference } from '../lib/preferences';

export function useLocale() {
  const saved = readPreference('runa.locale');
  const locale = ref<Locale>(saved === 'en' ? 'en' : 'pl');
  let syncing = false;
  const unsubscribe = defaultPreferenceStore.subscribe?.(() => {
    const saved = readPreference('runa.locale');
    syncing = true;
    locale.value = saved === 'en' ? 'en' : 'pl';
    syncing = false;
  });
  if (unsubscribe) onScopeDispose(unsubscribe);
  // Apply initial presentation without treating hydration/mount as a user edit.
  document.documentElement.lang = locale.value;
  watch(
    locale,
    (value) => {
      document.documentElement.lang = value;
      if (!syncing) savePreference('runa.locale', value);
    },
    { flush: 'sync' },
  );
  const t = (key: MessageKey): string => messages[locale.value][key];
  return { locale, t };
}
