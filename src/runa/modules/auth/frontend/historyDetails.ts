import { data, record } from './entry';
export type HistoryValue =
  | string
  | number
  | boolean
  | null
  | readonly HistoryValue[]
  | { readonly [key: string]: HistoryValue };
export interface AccountHistoryDetail {
  public_id: string;
  occurred_at: string;
  action: string;
  outcome: string;
  changes: { field: string; before: HistoryValue; after: HistoryValue }[];
}
function validValue(
  v: unknown,
  depth = 0,
  budget = { nodes: 0 },
): v is HistoryValue {
  if (++budget.nodes > 1024 || depth > 8) return false;
  if (v === null || typeof v === 'boolean') return true;
  if (typeof v === 'number') return Number.isFinite(v);
  if (typeof v === 'string') return new TextEncoder().encode(v).length <= 8192;
  if (Array.isArray(v))
    return v.every((item) => validValue(item, depth + 1, budget));
  if (typeof v === 'object')
    return Object.entries(v).every(
      ([key, item]) =>
        /^[a-zA-Z0-9_.-]{1,128}$/.test(key) &&
        validValue(item, depth + 1, budget),
    );
  return false;
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
  'email.requested': [
    'Zlecenie zmiany głównego adresu e-mail',
    'Primary email change requested',
  ],
  'email.admin_requested': [
    'Administracyjne zlecenie zmiany adresu e-mail',
    'Email change requested by an administrator',
  ],
  'invitation.completed': ['Przyjęcie zaproszenia', 'Invitation accepted'],
  'delivery.preferences_reset': [
    'Reset preferencji powiadomienia',
    'Notification preference reset',
  ],
  'preferences.changed': ['Zmiana preferencji', 'Preferences changed'],
  'preferences.reset': ['Reset ustawienia', 'Setting reset'],
  'two_factor.enrollment_started': [
    'Rozpoczęcie konfiguracji aplikacji uwierzytelniającej',
    'Authenticator setup started',
  ],
  'two_factor.totp_confirm': [
    'Włączenie aplikacji uwierzytelniającej',
    'Authenticator enabled',
  ],
  'two_factor.email_enable': [
    'Włączenie drugiego składnika przez e-mail',
    'Email second factor enabled',
  ],
  'two_factor.disable': [
    'Wyłączenie drugiego składnika',
    'Second factor disabled',
  ],
  'two_factor.recovery_rotate': [
    'Wymiana kodów zapasowych',
    'Backup codes replaced',
  ],
  'two_factor.recovery_used': ['Użycie kodu zapasowego', 'Backup code used'],
  'policy.exception_set': [
    'Ustawienie wyjątku polityki konta',
    'Account policy exception set',
  ],
  'policy.exception_revoked': [
    'Cofnięcie wyjątku polityki konta',
    'Account policy exception revoked',
  ],
  'identity.created': ['Utworzenie konta', 'Account created'],
  'identity.approve': ['Zatwierdzenie konta', 'Account approved'],
  'identity.activate': ['Aktywacja konta', 'Account activated'],
  'identity.restore': ['Przywrócenie konta', 'Account restored'],
  'identity.schedule_activation': [
    'Zmiana terminu aktywacji konta',
    'Account activation schedule changed',
  ],
  'identity.schedule_deactivation': [
    'Zmiana terminu dezaktywacji konta',
    'Account deactivation schedule changed',
  ],
  'identity.set_expiry': [
    'Zmiana terminu ważności konta',
    'Account expiry changed',
  ],
  'identity.set_attempt_lock': [
    'Zmiana blokady logowania',
    'Login lock changed',
  ],
  'identity.set_security_lock': [
    'Zmiana blokady bezpieczeństwa',
    'Security lock changed',
  ],
  'identity.require_password_change': [
    'Zmiana wymogu odnowienia hasła',
    'Password renewal requirement changed',
  ],
  'identity.scheduled_activated': [
    'Zaplanowana aktywacja konta',
    'Scheduled account activation',
  ],
  'identity.scheduled_deactivated': [
    'Zaplanowana dezaktywacja konta',
    'Scheduled account deactivation',
  ],
  'identity.deactivate': ['Dezaktywacja konta', 'Account deactivated'],
  'identity.delete': ['Usunięcie konta', 'Account deleted'],
};
