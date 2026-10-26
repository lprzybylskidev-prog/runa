import { request } from '../../../resources/js/lib/http';
import { data, record } from './entry';

export interface Profile {
  public_id: string;
  version: number;
  display_name: string;
  initials: string;
  name: string | null;
  first_name: string | null;
  surname: string | null;
  login: string | null;
  email: string;
  email_verified: boolean;
  has_avatar: boolean;
  secondary_emails: {
    public_id: string;
    email: string;
    verified_at: string | null;
  }[];
  capabilities: Record<string, boolean | string | number>;
}
export function profileResponse(value: unknown): Profile {
  const p = data(value);
  for (const key of ['public_id', 'display_name', 'initials', 'email'])
    if (typeof p[key] !== 'string') throw new TypeError('Invalid profile.');
  for (const key of ['name', 'first_name', 'surname', 'login'])
    if (p[key] !== null && typeof p[key] !== 'string')
      throw new TypeError('Invalid profile.');
  if (
    !Number.isSafeInteger(p.version) ||
    Number(p.version) < 1 ||
    typeof p.email_verified !== 'boolean' ||
    typeof p.has_avatar !== 'boolean' ||
    !Array.isArray(p.secondary_emails)
  )
    throw new TypeError('Invalid profile.');
  const caps = record(p.capabilities);
  if (
    !Object.values(caps).every((v) =>
      ['boolean', 'string', 'number'].includes(typeof v),
    )
  )
    throw new TypeError('Invalid capabilities.');
  for (const item of p.secondary_emails) {
    const row = record(item);
    if (
      typeof row.public_id !== 'string' ||
      typeof row.email !== 'string' ||
      (row.verified_at !== null && typeof row.verified_at !== 'string')
    )
      throw new TypeError('Invalid address.');
  }
  return p as unknown as Profile;
}
export async function accountRequest(
  path: string,
  method: 'GET' | 'POST' | 'PATCH' | 'DELETE',
  input: Record<string, unknown> | FormData,
  signal: AbortSignal,
) {
  const multipart = input instanceof FormData;
  return request(
    `/runa/auth/${path}`,
    {
      method,
      signal,
      cache: 'no-store',
      ...(method === 'GET'
        ? {}
        : {
            headers: multipart ? {} : { 'Content-Type': 'application/json' },
            body: multipart ? input : JSON.stringify(input),
          }),
    },
    method === 'GET'
      ? (input as Record<string, string | number | boolean | null>)
      : {},
  );
}
export interface AccountRow {
  public_id: string;
  current?: boolean;
  authenticated_at?: string;
  activity_at?: string;
  created_at?: string;
  expires_at?: string;
  occurred_at?: string;
  action?: string;
  outcome?: string;
}
export function pageResponse(value: unknown): {
  rows: AccountRow[];
  cursor: string | null;
} {
  const v = record(value);
  if (!Array.isArray(v.data)) throw new TypeError('Invalid page.');
  for (const item of v.data) {
    const r = record(item);
    if (
      typeof r.public_id !== 'string' ||
      Object.values(r).some(
        (v) => v !== null && typeof v !== 'string' && typeof v !== 'boolean',
      )
    )
      throw new TypeError('Invalid row.');
  }
  const meta = record(v.meta);
  if (meta.next_cursor !== null && typeof meta.next_cursor !== 'string')
    throw new TypeError('Invalid cursor.');
  return { rows: v.data as AccountRow[], cursor: meta.next_cursor };
}

/** Only explicitly safe name/login drafts; never storage, credentials, tokens or file bytes. */
let draftEpoch = 0;
export const accountDraftEpoch = () => draftEpoch;
let draft: { account: string; values: Record<string, string> } | null = null;
export function rememberAccountDraft(
  account: string,
  values: Record<string, string>,
  epoch = draftEpoch,
) {
  if (epoch !== draftEpoch) return;
  const safe = Object.fromEntries(
    ['name', 'first_name', 'surname', 'login'].map((k) => [
      k,
      (values[k] ?? '').slice(0, 160),
    ]),
  );
  draft = { account, values: safe };
}
export function takeAccountDraft(account: string) {
  const result = draft?.account === account ? draft.values : null;
  draft = null;
  return result;
}
export function clearAccountDraft() {
  draftEpoch++;
  draft = null;
}
