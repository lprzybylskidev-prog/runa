import type { Locale } from '@/runa/ui';
const en = {
  title: 'Confirm with password',
  description: 'Confirm your password to continue.',
  password: 'Current password',
  confirm: 'Confirm',
  cancel: 'Cancel',
  open: 'Confirm required operation proof',
  fresh: 'Confirm a fresh operation proof',
  confirmed: 'Password confirmed.',
  invalidProof: 'Identity verification failed. Check your password.',
  throttled: 'Too many attempts. Try again later.',
  csrf: 'Form security was renewed. Enter your password and confirm again.',
  signedOut: 'Your session expired. Sign in again.',
  failed: 'Proof confirmation could not be completed. Try again explicitly.',
  fields: 'Complete the required proof fields.',
};
const pl: Record<keyof typeof en, string> = {
  title: 'Potwierdź hasłem',
  description: 'Potwierdź swoje hasło, aby kontynuować.',
  password: 'Obecne hasło',
  confirm: 'Potwierdź',
  cancel: 'Anuluj',
  open: 'Potwierdź tożsamość przed operacją',
  fresh: 'Potwierdź tożsamość ponownie',
  confirmed: 'Hasło potwierdzone.',
  invalidProof: 'Nie udało się potwierdzić tożsamości. Sprawdź hasło.',
  throttled: 'Zbyt wiele prób. Spróbuj później.',
  csrf: 'Zabezpieczenie formularza odnowione. Wpisz hasło i potwierdź ponownie.',
  signedOut: 'Sesja wygasła. Zaloguj się ponownie.',
  failed: 'Nie udało się potwierdzić tożsamości. Spróbuj ponownie.',
  fields: 'Uzupełnij wymagane pola potwierdzenia.',
};
export const operationProofCopy = (locale: Locale) =>
  locale === 'pl' ? pl : en;
