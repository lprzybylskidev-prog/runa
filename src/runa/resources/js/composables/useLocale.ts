import { defaultPreferenceStore } from '../../../modules/auth/frontend/preferences/session';
import { computed, inject, onScopeDispose, provide, ref, watch } from 'vue';
import type { InjectionKey, Ref } from 'vue';
import { messages } from '../locales/messages';
import type { Locale, MessageKey } from '../locales/messages';
import { readPreference, savePreference } from '../lib/preferences';

const scopedLocale: InjectionKey<Readonly<Ref<Locale | undefined>>> =
  Symbol('runa.locale.scope');

/** A presentation scope never writes account preferences. */
export function provideLocale(locale: Readonly<Ref<Locale | undefined>>) {
  const parent = inject(scopedLocale, undefined);
  provide(
    scopedLocale,
    computed(() => locale.value ?? parent?.value),
  );
}
export function useLocale(override?: Readonly<Ref<Locale | undefined>>) {
  const inherited = inject(scopedLocale, undefined);
  const scope = override?.value ? override : inherited;
  if (scope?.value) {
    const locale = computed({ get: () => scope.value ?? 'en', set: () => {} });
    return {
      locale,
      t: (key: MessageKey): string => messages[locale.value][key],
    };
  }
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
