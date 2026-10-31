import type { Locale } from '@/runa/ui';
const en = {
  title: 'Confirm operation',
  description:
    'Confirm your identity to continue. This does not submit the operation.',
  password: 'Current password',
  method: 'Verification method',
  code: 'Verification code',
  totp: 'Authenticator',
  email: 'Email',
  recovery: 'Recovery code',
  send: 'Send code',
  confirm: 'Confirm',
  cancel: 'Cancel',
  open: 'Confirm required operation proof',
  fresh: 'Confirm a fresh operation proof',
  confirmed: 'Proof confirmed. Submit the operation explicitly.',
  failed: 'Proof confirmation could not be completed. Try again explicitly.',
  noMethod: 'No eligible proof method is available.',
  fields: 'Complete the required proof fields.',
};
const pl: Record<keyof typeof en, string> = {
  title: 'Potwierdź operację',
  description:
    'Potwierdź swoją tożsamość, aby kontynuować. Nie spowoduje to wykonania operacji.',
  password: 'Obecne hasło',
  method: 'Metoda weryfikacji',
  code: 'Kod weryfikacyjny',
  totp: 'Aplikacja uwierzytelniająca',
  email: 'E-mail',
  recovery: 'Kod odzyskiwania',
  send: 'Wyślij kod',
  confirm: 'Potwierdź',
  cancel: 'Anuluj',
  open: 'Potwierdź tożsamość przed operacją',
  fresh: 'Potwierdź tożsamość ponownie',
  confirmed: 'Tożsamość potwierdzona. Wykonaj operację osobno.',
  failed: 'Nie udało się potwierdzić tożsamości. Spróbuj ponownie.',
  noMethod: 'Brak dostępnej metody potwierdzenia.',
  fields: 'Uzupełnij wymagane pola potwierdzenia.',
};
export const operationProofCopy = (locale: Locale) =>
  locale === 'pl' ? pl : en;
