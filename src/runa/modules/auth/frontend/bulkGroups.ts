import type { ButtonIntent } from '@/runa/ui';
/** Private presentation grouping; native projected commands retain all admission/proof authority. */
export const authBulkGroups = [
  {
    key: 'activity',
    title: { pl: 'Aktywność kont', en: 'Account activity' },
    commands: ['activate', 'deactivate', 'toggle_activation'],
  },
  {
    key: 'schedule',
    title: { pl: 'Terminy aktywności', en: 'Activity schedules' },
    commands: ['schedule_activation', 'schedule_deactivation'],
  },
  {
    key: 'deletion',
    title: { pl: 'Usuwanie i przywracanie', en: 'Deletion and restoration' },
    commands: ['delete', 'restore', 'toggle_deletion'],
  },
  {
    key: 'invitations',
    title: { pl: 'Zaproszenia', en: 'Invitations' },
    commands: ['invite', 'invalidate_invitation'],
  },
  {
    key: 'attempt_lock',
    title: { pl: 'Blokada prób', en: 'Attempt lock' },
    commands: ['set_attempt_lock'],
  },
  {
    key: 'security_lock',
    title: { pl: 'Blokada bezpieczeństwa', en: 'Security lock' },
    commands: ['set_security_lock', 'toggle_security_lock'],
  },
  {
    key: 'authority',
    title: { pl: 'Superadministrator', en: 'Super administrator' },
    commands: ['grant_superadmin', 'revoke_superadmin'],
  },
] as const;

export function authBulkGroup(command: string) {
  return authBulkGroups.find((group) =>
    group.commands.some((key) => key === command),
  );
}

/** Source-owned effect; modal admission does not change action meaning. */
export function authActionIntent(
  key: string,
  enabled?: boolean | null,
  clearDate = false,
): ButtonIntent {
  if (key === 'require_password_change' && enabled === false) return 'commit';
  if (key === 'toggle_activation')
    return enabled === true ? 'commit' : 'caution';
  if (key === 'toggle_deletion') return enabled === false ? 'commit' : 'danger';
  if (['set_security_lock', 'toggle_security_lock'].includes(key))
    return enabled === false ? 'commit' : 'caution';
  if (key === 'set_attempt_lock') return clearDate ? 'commit' : 'caution';
  if (
    [
      'delete',
      'remove',
      'remove_avatar',
      'factor_reset',
      'revoke',
      'revoke_device',
      'revoke_trust',
      'revoke_superadmin',
    ].includes(key)
  )
    return 'danger';
  if (
    [
      'deactivate',
      'schedule_deactivation',
      'require_verification',
      'require_password_change',
      'password_reset',
      'invalidate_invitation',
      'revoke_exceptions',
      'reset_preferences',
    ].includes(key)
  )
    return 'caution';
  return 'commit';
}

/** Different effects are purple until the user chooses a concrete operation. */
export function authActionGroupIntent(
  intents: readonly ButtonIntent[],
): ButtonIntent {
  const effects = new Set(intents);
  return effects.size > 1 ? 'mixed' : (intents[0] ?? 'neutral');
}

export function authBulkActionIntent(
  commands: readonly string[],
): ButtonIntent {
  return authActionGroupIntent(
    commands.flatMap((key) => {
      if (
        [
          'set_attempt_lock',
          'set_security_lock',
          'toggle_security_lock',
          'toggle_activation',
        ].includes(key)
      )
        return ['commit', 'caution'] as const;
      if (key === 'toggle_deletion') return ['commit', 'danger'] as const;
      return [authActionIntent(key)];
    }),
  );
}

/** One thematic order for single-account and selected-account commands. */
export const authActionSections = [
  {
    key: 'activity',
    title: { pl: 'Aktywność i terminy', en: 'Activity and schedules' },
    commands: [
      'activate',
      'deactivate',
      'toggle_activation',
      'schedule_activation',
      'schedule_deactivation',
    ],
  },
  {
    key: 'locks',
    title: { pl: 'Blokady', en: 'Locks' },
    commands: ['set_attempt_lock', 'set_security_lock', 'toggle_security_lock'],
  },
  {
    key: 'email',
    title: { pl: 'E-mail i zaproszenia', en: 'Email and invitations' },
    commands: [
      'verify_email',
      'require_verification',
      'invite',
      'invalidate_invitation',
    ],
  },
  {
    key: 'credentials',
    title: {
      pl: 'Hasło i uwierzytelnianie',
      en: 'Password and authentication',
    },
    commands: ['require_password_change', 'password_reset', 'factor_reset'],
  },
  {
    key: 'sessions',
    title: { pl: 'Sesje i urządzenia', en: 'Sessions and devices' },
    commands: ['revoke', 'revoke_trust'],
  },
  {
    key: 'preferences',
    title: { pl: 'Preferencje i wyjątki', en: 'Preferences and exceptions' },
    commands: ['reset_preferences', 'remove_avatar', 'revoke_exceptions'],
  },
  {
    key: 'deletion',
    title: { pl: 'Usuwanie i przywracanie', en: 'Deletion and restoration' },
    commands: ['delete', 'restore', 'toggle_deletion'],
  },
  {
    key: 'authority',
    title: { pl: 'Superadministrator', en: 'Super administrator' },
    commands: ['grant_superadmin', 'revoke_superadmin'],
  },
];

export function groupAuthActions<T>(
  items: readonly T[],
  command: (item: T) => string,
) {
  const groups = authActionSections.map((group) => ({
    key: group.key,
    title: group.title,
    items: items
      .filter((item) => group.commands.includes(command(item)))
      .sort(
        (a, b) =>
          group.commands.indexOf(command(a)) -
          group.commands.indexOf(command(b)),
      ),
  }));
  const other = items.filter(
    (item) =>
      !authActionSections.some((group) =>
        group.commands.includes(command(item)),
      ),
  );
  if (other.length)
    groups.splice(
      groups.findIndex((group) => group.key === 'authority'),
      0,
      {
        key: 'other',
        title: { pl: 'Pozostałe akcje', en: 'Other actions' },
        items: other,
      },
    );
  return groups.filter((group) => group.items.length);
}
