export const messages = {
  pl: {
    skip: 'Przejdź do treści',
    preferences: 'Ustawienia wyświetlania',
    language: 'Język',
    theme: 'Motyw',
    light: 'Jasny',
    dark: 'Ciemny',
    system: 'Systemowy',
    title: 'Runa',
    description: 'Aplikacja w przygotowaniu.',
    connection: 'Połączenie z aplikacją',
    checking: 'Sprawdzanie połączenia…',
    connected: 'Połączenie działa',
    failed: 'Nie udało się połączyć',
    failedDescription: 'Sprawdź połączenie z siecią i spróbuj ponownie.',
    retry: 'Spróbuj ponownie',
  },
  en: {
    skip: 'Skip to content',
    preferences: 'Display preferences',
    language: 'Language',
    theme: 'Theme',
    light: 'Light',
    dark: 'Dark',
    system: 'System',
    title: 'Runa',
    description: 'Application in development.',
    connection: 'Application connection',
    checking: 'Checking connection…',
    connected: 'Connection is working',
    failed: 'Unable to connect',
    failedDescription: 'Check your network connection and try again.',
    retry: 'Try again',
  },
} satisfies Record<'pl' | 'en', Record<string, string>>;

export type Locale = keyof typeof messages;
export type MessageKey = keyof typeof messages.pl;
