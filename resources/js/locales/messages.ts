export const messages = {
  pl: {
    skip: 'Przejdź do treści', preferences: 'Ustawienia wyświetlania', language: 'Język',
    theme: 'Motyw', light: 'Jasny', dark: 'Ciemny', system: 'Systemowy',
    eyebrow: 'Miejsce na Twoją pracę', title: 'Witaj w Runie.',
    description: 'To tutaj zaczyna się Twoja przestrzeń do pracy. Kolejne funkcje pojawią się wraz z rozwojem aplikacji.',
    connection: 'Połączenie z aplikacją', checking: 'Sprawdzanie połączenia…',
    connected: 'Połączenie działa', failed: 'Nie udało się połączyć',
    failedDescription: 'Sprawdź połączenie z siecią i spróbuj ponownie.', retry: 'Spróbuj ponownie',
    footer: 'Twoja przestrzeń. Twój rytm.',
  },
  en: {
    skip: 'Skip to content', preferences: 'Display preferences', language: 'Language',
    theme: 'Theme', light: 'Light', dark: 'Dark', system: 'System',
    eyebrow: 'Room for your work', title: 'Welcome to Runa.',
    description: 'Your workspace starts here. More features will arrive as the application grows.',
    connection: 'Application connection', checking: 'Checking connection…',
    connected: 'Connection is working', failed: 'Unable to connect',
    failedDescription: 'Check your network connection and try again.', retry: 'Try again',
    footer: 'Your space. Your rhythm.',
  },
} satisfies Record<'pl' | 'en', Record<string, string>>

export type Locale = keyof typeof messages
export type MessageKey = keyof typeof messages.pl
