export const messages = {
  pl: {
    skip: 'Przejdź do treści',
    preferences: 'Ustawienia wyświetlania',
    applyingPreferences: 'Zmienianie ustawień…',
    language: 'Język',
    switchTo: 'Zmień na',
    changeLanguage: 'Zmień język',
    changeTheme: 'Zmień motyw',
    theme: 'Motyw',
    light: 'Jasny',
    dark: 'Ciemny',
    system: 'Systemowy',
    version: 'Wersja',
    poweredBy: 'Oparte na',
    title: 'Runa',
    description: 'Aplikacja w przygotowaniu.',
  },
  en: {
    skip: 'Skip to content',
    preferences: 'Display preferences',
    applyingPreferences: 'Applying preferences…',
    language: 'Language',
    switchTo: 'Switch to',
    changeLanguage: 'Change language',
    changeTheme: 'Change theme',
    theme: 'Theme',
    light: 'Light',
    dark: 'Dark',
    system: 'System',
    version: 'Version',
    poweredBy: 'Powered by',
    title: 'Runa',
    description: 'Application in development.',
  },
} satisfies Record<'pl' | 'en', Record<string, string>>;

export type Locale = keyof typeof messages;
export type MessageKey = keyof typeof messages.pl;
