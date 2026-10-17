export const messages = {
  pl: {
    skip: 'Przejdź do treści',
    preferences: 'Ustawienia wyświetlania',
    language: 'Język',
    switchTo: 'Zmień na',
    theme: 'Motyw',
    light: 'Jasny',
    dark: 'Ciemny',
    system: 'Systemowy',
    title: 'Runa',
    description: 'Aplikacja w przygotowaniu.',
  },
  en: {
    skip: 'Skip to content',
    preferences: 'Display preferences',
    language: 'Language',
    switchTo: 'Switch to',
    theme: 'Theme',
    light: 'Light',
    dark: 'Dark',
    system: 'System',
    title: 'Runa',
    description: 'Application in development.',
  },
} satisfies Record<'pl' | 'en', Record<string, string>>;

export type Locale = keyof typeof messages;
export type MessageKey = keyof typeof messages.pl;
