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
