import type { BadgeTone } from '@/runa/ui';
/** Domain status polarity shared by default record and table presentations. */
export function adminBooleanTone(key: string, value: boolean): BadgeTone {
  if (!value) return 'neutral';
  if (['security_locked', 'deleted'].includes(key)) return 'danger';
  if (
    [
      'password_expired',
      'password_change_required',
      'invitation_pending',
      'operator_recovery_pending',
      'totp_setup_pending',
      'verification_required',
    ].includes(key)
  )
    return 'warning';
  return 'success';
}
