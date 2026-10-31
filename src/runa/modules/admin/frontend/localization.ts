import { computed, inject, provide } from 'vue';
import type { ComputedRef, InjectionKey, Ref } from 'vue';
import { useLocale } from '../../../resources/js/composables/useLocale';
import type { AdminMessages, AdminText } from './contracts';
import { adminMessages } from './messages';
const messagesKey: InjectionKey<ComputedRef<AdminMessages>> = Symbol(
  'runa.admin.messages',
);
export function adminText(
  text: AdminText,
  locale: 'pl' | 'en',
  messages: AdminMessages = {},
): string {
  if (typeof text !== 'string') return text[locale];
  const value = text;
  if (locale === 'en') return value;
  if (Object.hasOwn(messages, value)) return messages[value]!;
  const maximum = /^Use at most (\d+) characters\.$/.exec(value);
  if (maximum) return `Użyj najwyżej ${maximum[1]} znaków.`;
  return Object.hasOwn(adminMessages, value) ? adminMessages[value]! : value;
}
export function useAdminLocale(messages?: () => AdminMessages | undefined) {
  const { locale } = useLocale();
  const inherited = inject(
    messagesKey,
    computed<AdminMessages>(() => ({})),
  );
  return {
    locale,
    t: (text: AdminText): string =>
      adminText(text, locale.value, { ...inherited.value, ...messages?.() }),
  };
}
export function provideAdminMessages(
  messages: Readonly<Ref<AdminMessages | undefined>>,
) {
  const inherited = inject(
    messagesKey,
    computed<AdminMessages>(() => ({})),
  );
  provide(
    messagesKey,
    computed(() => ({ ...inherited.value, ...messages.value })),
  );
}
