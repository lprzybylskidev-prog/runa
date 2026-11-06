export const adminReasonNames: Readonly<Record<string, string>> = {
  health_policy_unavailable: 'Health policy is unavailable.',
  health_busy: 'Another health check is in progress.',
  health_check_error: 'A health check could not complete.',
  health_check_failed: 'A health check failed.',
  disabled: 'The module is disabled.',
  not_registered: 'The module is not registered.',
  source_missing: 'Module source is missing.',
  not_installed: 'The module is not installed.',
  health_expired: 'Health observations have expired.',
  health_unknown: 'Health has not been confirmed.',
  health_unhealthy: 'A health check failed.',
  unhealthy: 'A health check failed.',
  runtime_version: 'Source and installed versions do not match.',
  installed_data_incompatible: 'Installed data requires an update.',
  transition_fenced: 'A module operation is in progress.',
  dependency_version_or_owner:
    'A required dependency is unavailable or incompatible.',
  dependency_version: 'A required dependency is incompatible.',
  required_cycle: 'Required dependencies contain a cycle.',
  availability_changed: 'Module availability changed. Refresh its state.',
  lifecycle_policy_unavailable: 'The lifecycle policy is unavailable.',
};

export function adminReasonMessage(code: string): string {
  return adminReasonNames[code] ?? code.replaceAll('_', ' ');
}
