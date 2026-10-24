import { ApiError, request } from '../../../resources/js/lib/http';

export const entryPages = [
  'login',
  'register',
  'forgot-password',
  'reset-password',
  'invitation',
  'verify-email',
  'confirm-address',
  'security',
] as const;
export type EntryPage = (typeof entryPages)[number];
export type Restriction =
  | 'email_verification_required'
  | 'two_factor_enrollment_required'
  | 'two_factor_required'
  | 'password_change_required';
export type FactorMethod = 'totp' | 'email' | 'recovery';
export interface EntryOptions {
  public_registration: boolean;
  identifier_mode: 'email' | 'login' | 'either';
  remember_enabled: boolean;
  password: {
    min_length: number;
    max_bytes: number;
    mixed_case: boolean;
    numbers: boolean;
    symbols: boolean;
  };
}
export interface EntrySession {
  public_id: string;
  display_name: string;
  restrictions: Restriction[];
}
export interface SecurityOptions {
  email_verified: boolean;
  required: 'optional' | 'any' | 'totp' | 'email';
  enrolled: ('totp' | 'email')[];
  totp_enabled: boolean;
  email_otp_enabled: boolean;
  recovery_codes_enabled: boolean;
  password_change_factor: boolean;
  trusted_devices_enabled: boolean;
}
export function record(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value))
    throw new TypeError('Invalid Auth response.');
  return value as Record<string, unknown>;
}
export function data(value: unknown) {
  return record(record(value).data);
}
function boolean(value: unknown): boolean {
  if (typeof value !== 'boolean') throw new TypeError('Invalid Auth flag.');
  return value;
}
export function decodeOptions(value: unknown): EntryOptions {
  const v = data(value),
    p = record(v.password);
  if (
    !['email', 'login', 'either'].includes(String(v.identifier_mode)) ||
    !Number.isInteger(p.min_length) ||
    Number(p.min_length) < 12 ||
    Number(p.min_length) > 72 ||
    p.max_bytes !== 72
  )
    throw new TypeError('Invalid Auth options.');
  return {
    public_registration: boolean(v.public_registration),
    identifier_mode: v.identifier_mode as EntryOptions['identifier_mode'],
    remember_enabled: boolean(v.remember_enabled),
    password: {
      min_length: Number(p.min_length),
      max_bytes: 72,
      mixed_case: boolean(p.mixed_case),
      numbers: boolean(p.numbers),
      symbols: boolean(p.symbols),
    },
  };
}
export function decodeSession(value: unknown): EntrySession {
  const v = data(value);
  if (
    typeof v.public_id !== 'string' ||
    !/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/.test(
      v.public_id,
    ) ||
    typeof v.display_name !== 'string' ||
    !Array.isArray(v.restrictions) ||
    !v.restrictions.every((r) =>
      [
        'email_verification_required',
        'two_factor_enrollment_required',
        'two_factor_required',
        'password_change_required',
      ].includes(String(r)),
    )
  )
    throw new TypeError('Invalid Auth session.');
  return {
    public_id: v.public_id,
    display_name: v.display_name,
    restrictions: v.restrictions as Restriction[],
  };
}
export function decodeSecurity(value: unknown): SecurityOptions {
  const v = data(value);
  if (
    !['optional', 'any', 'totp', 'email'].includes(String(v.required)) ||
    !Array.isArray(v.enrolled) ||
    !v.enrolled.every((m) => m === 'totp' || m === 'email')
  )
    throw new TypeError('Invalid Auth security.');
  return {
    email_verified: boolean(v.email_verified),
    required: v.required as SecurityOptions['required'],
    enrolled: v.enrolled as SecurityOptions['enrolled'],
    totp_enabled: boolean(v.totp_enabled),
    email_otp_enabled: boolean(v.email_otp_enabled),
    recovery_codes_enabled: boolean(v.recovery_codes_enabled),
    password_change_factor: boolean(v.password_change_factor),
    trusted_devices_enabled: boolean(v.trusted_devices_enabled),
  };
}
export function factorMethods(
  security: SecurityOptions,
  enrollment = false,
): FactorMethod[] {
  const result: FactorMethod[] = [];
  if (
    security.totp_enabled &&
    security.required !== 'email' &&
    (enrollment || security.enrolled.includes('totp'))
  ) {
    result.push('totp');
    if (!enrollment && security.recovery_codes_enabled) result.push('recovery');
  }
  if (
    security.email_otp_enabled &&
    security.required !== 'totp' &&
    (enrollment || security.enrolled.includes('email'))
  )
    result.push('email');
  return result;
}
export function entryPage(path: string): EntryPage | null {
  return entryPages.find((page) => path === `/auth/${page}`) ?? null;
}
/** Conservative local path only; never send an untrusted return URL to location. */
export function safeReturnPath(value: string | null): string {
  return value &&
    /^\/(?:[a-zA-Z0-9_-]+\/?)*$/.test(value) &&
    !/^\/(auth|api)(\/|$)/.test(value)
    ? value
    : '/';
}
export function takeEntryToken(
  location: Pick<Location, 'hash' | 'pathname' | 'search'> = window.location,
  history: Pick<History, 'replaceState'> = window.history,
): string {
  const token = new URLSearchParams(location.hash.slice(1)).get('token') ?? '';
  // Remove even malformed fragments before making any network request.
  if (location.hash)
    history.replaceState(null, '', location.pathname + location.search);
  return /^[0-9a-f]{64}$/.test(token) ? token : '';
}
export async function authGet(path: string, signal: AbortSignal) {
  return request(`/runa/auth/${path}`, { signal, cache: 'no-store' });
}
export async function authPost(
  path: string,
  input: Record<string, string | boolean>,
  signal: AbortSignal,
) {
  return request(`/runa/auth/${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
    signal,
    cache: 'no-store',
  });
}
export function authFailure(error: unknown): {
  code: string;
  fields: string[];
} {
  if (!(error instanceof ApiError)) return { code: 'unavailable', fields: [] };
  let body: Record<string, unknown> = {};
  try {
    body = record(record(error.payload).error);
  } catch {
    /* Unknown errors stay generic. */
  }
  return {
    code:
      error.status >= 500
        ? 'unavailable'
        : error.status === 419
          ? 'csrf'
          : error.status === 429
            ? 'throttled'
            : error.status === 409
              ? 'conflict'
              : error.status === 401
                ? 'signed_out'
                : body.code === 'entry_token_invalid'
                  ? 'token_invalid'
                  : typeof body.code === 'string'
                    ? body.code
                    : 'unavailable',
    fields:
      body.fields && typeof body.fields === 'object'
        ? Object.keys(body.fields)
        : [],
  };
}
