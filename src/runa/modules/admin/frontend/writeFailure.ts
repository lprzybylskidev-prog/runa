import { ApiError } from '@/runa/http';
import type { AdminError } from './contracts';
import { object } from './decode';
import { refusalMessages } from './refusals';
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
    identity_deleted:
      'This operation cannot be applied to deleted accounts. Change the selection or restore them first.',
    invitation_unavailable:
      'An invitation requires a passwordless account without security locks, deletion or reached deactivation.',
    password_reset_unavailable:
      'A password reset requires an account with a password that can sign in.',
    user_change_refused:
      'This operation cannot be applied to the current account selection. Check the account states and try again.',
  };
  const message =
    cause.status < 500 &&
    (Object.hasOwn(refusalMessages, failure.reason)
      ? refusalMessages[failure.reason]
      : Object.hasOwn(refusals, failure.reason)
        ? refusals[failure.reason]
        : undefined);
  if (message) {
    const names =
      failure.reason === 'identifier_conflict'
        ? ['email', 'replacement_email', 'login']
        : failure.reason === 'access_definition_conflict'
          ? ['code']
          : failure.reason === 'password_reused'
            ? ['password']
            : failure.reason === 'email_unchanged'
              ? ['email', 'replacement_email']
              : failure.reason === 'login_required'
                ? ['login']
                : ['avatar_invalid', 'avatar_upload_expired'].includes(
                      failure.reason,
                    )
                  ? ['avatar_upload_pending']
                  : [];
    const candidates = fields.filter((path) =>
      names.includes(path.split('.').at(-1)!),
    );
    const fieldMessage =
      failure.reason === 'identifier_conflict' && candidates.length === 1
        ? candidates[0]!.endsWith('login')
          ? 'This login is already in use. Choose another.'
          : 'This email address is already in use. Choose another.'
        : message;
    return {
      ...failure,
      message: fieldMessage,
      ...(candidates.length === 1
        ? { errors: { [candidates[0]!]: [fieldMessage] } }
        : {}),
    };
  }
  if (cause.status === 409)
    return {
      ...failure,
      message: 'This change was not saved. Check the current values.',
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
    return {
      ...failure,
      message: Object.keys(errors).length
        ? 'Check the highlighted fields.'
        : 'Invalid operation data. Check the entered values.',
      errors,
    };
  }
  return failure;
}
