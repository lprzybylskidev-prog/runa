import { ApiError } from '@/runa/http';
import type { AdminError } from './contracts';
import { object } from './decode';
import { adminFailure } from './useAdmin';
export function writeFailure(
  cause: unknown,
  fields: readonly string[],
): AdminError {
  if (!(cause instanceof ApiError)) return adminFailure(cause).error;
  const failure = adminFailure(cause).error;
  const refusals: Record<string, string> = {
    schedule_must_be_future: 'Choose a future activation or deactivation date.',
    invalid_schedule_order:
      'Activation must be scheduled before deactivation. Check the selected accounts and dates.',
    identifier_conflict:
      'An email address or login is already used by another account. Change the selection or resolve the conflict before restoring accounts.',
    identity_deleted:
      'This operation cannot be applied to deleted accounts. Change the selection or restore them first.',
    invitation_unavailable:
      'An invitation requires a passwordless account without security locks, deletion or reached deactivation.',
    password_reset_unavailable:
      'A password reset requires an account with a password that can sign in.',
    user_change_refused:
      'This operation cannot be applied to the current account selection. Check the account states and try again.',
  };
  if (refusals[failure.reason])
    return { ...failure, message: refusals[failure.reason]! };
  if (cause.status === 409)
    return {
      ...failure,
      message:
        'This change was not saved. Current values are being checked; your edits are preserved.',
    };
  if (cause.status === 422) {
    const errors: Record<string, readonly string[]> = {};
    try {
      for (const key of Object.keys(object(object(cause.payload).errors)))
        if (
          fields.some((field) => key === field || key.startsWith(field + '.'))
        )
          errors[key] = ['Invalid input.'];
    } catch {
      /* Malformed errors never expose a payload. */
    }
    return { ...failure, message: 'Check the highlighted fields.', errors };
  }
  if (cause.status === 403 && failure.reason === 'step_up_required')
    return {
      ...failure,
      message: 'Confirm the required operation proof, then submit explicitly.',
    };
  return failure;
}
