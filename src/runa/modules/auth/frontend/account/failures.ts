/** Authenticated account refusals only; anonymous entry retains its non-enumerating copy. */
export const accountFailureMessages: Record<string, readonly [string, string]> =
  {
    identifier_conflict: [
      'Ten adres e-mail lub login jest już zajęty. Wybierz inny.',
      'This email address or login is already in use. Choose another.',
    ],
    secondary_limit: [
      'Osiągnięto limit dodatkowych adresów. Usuń jeden z nich, aby dodać kolejny.',
      'The secondary address limit has been reached. Remove an address before adding another.',
    ],
    secondary_missing: [
      'Ten dodatkowy adres został usunięty. Wybierz aktualny adres.',
      'This secondary address was removed. Select a current address.',
    ],
    secondary_disabled: [
      'Dodatkowe adresy e-mail są wyłączone.',
      'Secondary email addresses are disabled.',
    ],
    secondary_edit_disabled: [
      'Zmiana dodatkowych adresów e-mail jest wyłączona.',
      'Editing secondary email addresses is disabled.',
    ],
    email_unchanged: [
      'Podaj adres inny niż obecny.',
      'Enter a different email address.',
    ],
    email_edit_disabled: [
      'Zmiana głównego adresu e-mail jest wyłączona.',
      'Changing the primary email address is disabled.',
    ],
    email_token_invalid: [
      'Link potwierdzający jest nieprawidłowy lub wygasł. Wyślij nowy link.',
      'The verification link is invalid or expired. Send a new link.',
    ],
    password_reused: [
      'Wybierz hasło inne niż ostatnio używane.',
      'Choose a password different from recently used passwords.',
    ],
    security_proof_invalid: [
      'Nie udało się potwierdzić tożsamości. Sprawdź hasło i aktualny kod.',
      'Identity verification failed. Check your password and current code.',
    ],
    recipient_unavailable: [
      'Wybrany adres odbiorcy jest niedostępny. Wybierz aktualny, potwierdzony adres.',
      'The selected recipient is unavailable. Select a current verified address.',
    ],
    mandatory_delivery: [
      'Nie można wyłączyć wymaganych powiadomień.',
      'Required notifications cannot be disabled.',
    ],
    delivery_category_unavailable: [
      'Ta kategoria powiadomień jest niedostępna.',
      'This notification category is unavailable.',
    ],
    profile_field_readonly: [
      'Zmiana tego pola profilu jest wyłączona.',
      'Editing this profile field is disabled.',
    ],
    login_required: ['Login jest wymagany.', 'A login is required.'],
    avatar_invalid: [
      'Nieprawidłowy obraz. Wybierz obsługiwany plik w dozwolonym rozmiarze.',
      'Invalid image. Choose a supported file within the size limit.',
    ],
    avatar_upload_expired: [
      'Przesłany obraz wygasł. Wybierz go ponownie.',
      'The uploaded image expired. Select it again.',
    ],
    avatar_edit_disabled: [
      'Zmiana awatara jest wyłączona.',
      'Avatar editing is disabled.',
    ],
    avatar_missing: [
      'Awatar został już usunięty.',
      'The avatar has already been removed.',
    ],
    self_change_disabled: [
      'Samodzielna zmiana stanu konta jest wyłączona.',
      'Changing your own account state is disabled.',
    ],
    preference_invalid: [
      'Nieprawidłowa wartość preferencji. Wybierz dostępną opcję.',
      'Invalid preference value. Choose an available option.',
    ],
    preference_undeclared: [
      'Ta preferencja jest niedostępna.',
      'This preference is unavailable.',
    ],
    preference_module_unavailable: [
      'Preferencje tego modułu są niedostępne.',
      'Preferences for this module are unavailable.',
    ],
    preferences_already_adopted: [
      'Ustawienia tego urządzenia zostały już przeniesione.',
      'Settings from this device have already been adopted.',
    ],
    signed_out: [
      'Sesja wygasła. Zaloguj się ponownie.',
      'Your session expired. Sign in again.',
    ],
    account_restricted: [
      'Konto wymaga dodatkowego kroku. Dokończ go przed zmianą danych.',
      'Your account requires an additional step. Complete it before editing.',
    ],
    permission_denied: [
      'Nie masz uprawnień do tej operacji.',
      'You do not have permission for this operation.',
    ],
    entry_token_invalid: [
      'Potwierdzenie wygasło. Potwierdź operację ponownie.',
      'Verification expired. Confirm the operation again.',
    ],
  };
export function accountFailureField(
  reason: string,
  fields: readonly string[],
): string | undefined {
  const names =
    reason === 'identifier_conflict'
      ? ['email', 'login']
      : reason === 'email_unchanged'
        ? ['email']
        : reason === 'password_reused'
          ? ['password']
          : reason === 'login_required'
            ? ['login']
            : [];
  return names.find((name) => fields.includes(name));
}
