import { ref, watch } from 'vue'
import { messages } from '../locales/messages'
import type { Locale, MessageKey } from '../locales/messages'
import { readPreference, savePreference } from '../lib/preferences'

export function useLocale() {
  const saved = readPreference('runa.locale')
  const locale = ref<Locale>(saved === 'en' ? 'en' : 'pl')
  watch(locale, value => {
    document.documentElement.lang = value
    savePreference('runa.locale', value)
  }, { immediate: true })
  const t = (key: MessageKey): string => messages[locale.value][key]
  return { locale, t }
}
