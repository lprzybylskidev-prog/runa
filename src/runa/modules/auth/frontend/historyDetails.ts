import { data, record } from './entry';
export type HistoryValue =
  | string
  | number
  | boolean
  | null
  | readonly (string | number | boolean | null)[];
export interface AccountHistoryDetail {
  public_id: string;
  occurred_at: string;
  action: string;
  outcome: string;
  changes: { field: string; before: HistoryValue; after: HistoryValue }[];
}
function validValue(v: unknown): v is HistoryValue {
  const scalar = (x: unknown) =>
    x === null ||
    typeof x === 'boolean' ||
    (typeof x === 'number' && Number.isSafeInteger(x)) ||
    (typeof x === 'string' && x.length <= 512);
  return scalar(v) || (Array.isArray(v) && v.length <= 21 && v.every(scalar));
}
export function historyDetailResponse(
  response: unknown,
  expected: string,
): AccountHistoryDetail {
  const row = data(response);
  if (
    row.public_id !== expected ||
    typeof row.occurred_at !== 'string' ||
    typeof row.action !== 'string' ||
    typeof row.outcome !== 'string' ||
    !Array.isArray(row.changes) ||
    row.changes.length > 32
  )
    throw new TypeError('Invalid history detail.');
  const changes = row.changes.map((item) => {
    const change = record(item);
    if (
      typeof change.field !== 'string' ||
      !validValue(change.before) ||
      !validValue(change.after)
    )
      throw new TypeError('Invalid history change.');
    return { field: change.field, before: change.before, after: change.after };
  });
  return {
    public_id: expected,
    occurred_at: row.occurred_at,
    action: row.action,
    outcome: row.outcome,
    changes,
  };
}
export const historyEventNames: Record<string, readonly [string, string]> = {
  'profile.changed': ['Zmiana danych profilu', 'Profile details changed'],
  'profile.admin_changed': [
    'Administracyjna zmiana profilu',
    'Profile changed by an administrator',
  ],
  'email.changed': ['Zmiana głównego adresu e-mail', 'Primary email changed'],
  'email.admin_changed': [
    'Administracyjna zmiana adresu e-mail',
    'Email changed by an administrator',
  ],
  'email.reverification_required': [
    'Wymagane ponowne potwierdzenie adresu e-mail',
    'Email verification required again',
  ],
  'verification.completed': ['Potwierdzenie adresu e-mail', 'Email verified'],
  'secondary.added': [
    'Dodanie dodatkowego adresu e-mail',
    'Secondary email added',
  ],
  'secondary.changed': [
    'Zmiana dodatkowego adresu e-mail',
    'Secondary email changed',
  ],
  'secondary.removed': [
    'Usunięcie dodatkowego adresu e-mail',
    'Secondary email removed',
  ],
  'secondary.verified': [
    'Potwierdzenie dodatkowego adresu e-mail',
    'Secondary email verified',
  ],
  'avatar.changed': ['Zmiana zdjęcia profilowego', 'Profile image changed'],
  'avatar.removed': ['Usunięcie zdjęcia profilowego', 'Profile image removed'],
  'delivery.preferences_changed': ['Zmiana preferencji', 'Preferences changed'],
  'preferences.reset_all': ['Reset preferencji', 'Preferences reset'],
  'preferences.admin_reset': [
    'Administracyjny reset preferencji',
    'Administrative preferences reset',
  ],
  'password.changed': ['Zmiana hasła', 'Password changed'],
  'password.reset': [
    'Odzyskanie dostępu i reset hasła',
    'Password reset through recovery',
  ],
  'password.admin_reset': [
    'Administracyjny reset hasła',
    'Password reset by an administrator',
  ],
  'two_factor.admin_reset': [
    'Administracyjny reset drugiego składnika',
    'Second factor reset by an administrator',
  ],
  'identity.deactivate': ['Dezaktywacja konta', 'Account deactivated'],
  'identity.delete': ['Usunięcie konta', 'Account deleted'],
};
