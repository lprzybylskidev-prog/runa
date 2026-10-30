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
