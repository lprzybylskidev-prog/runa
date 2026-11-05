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
] as const;

export function authBulkGroup(command: string) {
  return authBulkGroups.find((group) =>
    group.commands.some((key) => key === command),
  );
}

export function authActionTone(
  key: string,
  grouped = false,
): 'success' | 'danger' | 'warning' | 'primary' {
  if (grouped && authBulkGroup(key)) return 'primary';
  if (['activate', 'restore', 'schedule_activation'].includes(key))
    return 'success';
  if (
    [
      'delete',
      'deactivate',
      'schedule_deactivation',
      'set_security_lock',
      'factor_reset',
      'revoke',
      'revoke_trust',
    ].includes(key)
  )
    return 'danger';
  if (
    [
      'require_verification',
      'require_password_change',
      'password_reset',
      'set_attempt_lock',
      'invalidate_invitation',
      'revoke_exceptions',
    ].includes(key)
  )
    return 'warning';
  return 'primary';
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
    groups.push({
      key: 'other',
      title: { pl: 'Pozostałe akcje', en: 'Other actions' },
      items: other,
    });
  return groups.filter((group) => group.items.length);
}
