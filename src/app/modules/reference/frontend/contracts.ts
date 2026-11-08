/** Application-owned presentation contract; authority hints never authorize HTTP. */
export type ReferenceView = 'samples' | 'settings' | 'history' | 'work';
export interface ReferenceAccess {
  identity?: string | undefined;
  read: boolean;
  write: boolean;
  codeRead: boolean;
  codeWrite: boolean;
  credentialRead: boolean;
  credentialWrite: boolean;
}
export interface ReferenceWorkspaceProps {
  access: ReferenceAccess;
  view: ReferenceView;
  locale?: 'pl' | 'en';
}
export interface Sample {
  public_id: string;
  revision: string;
  code?: string;
}
export interface Settings {
  revision: number;
  review_id: string;
  label: string;
  labelNames: { pl: string; en: string };
  labelDescriptions: { pl: string; en: string };
  credentialNames?: { pl: string; en: string };
  credentialSet?: boolean;
}
export interface Snapshot {
  public_id: string;
  name: string;
  source_revision: number;
  created_at: string;
}
export interface HistoryEntry {
  public_id: string;
  occurred_at: string;
  action: string;
  outcome: string;
}
export interface WorkEntry {
  work_id: string;
  operation: string;
  status: string;
  progress: string;
  attempts: number;
  cancellation_requested: boolean;
}
export type SettingsPatch = { label?: string; credential?: string | null };

export function object(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value))
    throw new TypeError('Invalid reference response.');
  return value as Record<string, unknown>;
}
export function text(value: unknown): string {
  if (typeof value !== 'string') throw new TypeError('Invalid reference text.');
  return value;
}
function integer(value: unknown): number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value < 0)
    throw new TypeError('Invalid reference number.');
  return value;
}
export function list<T>(
  value: unknown,
  decode: (item: Record<string, unknown>) => T,
): T[] {
  if (!Array.isArray(value)) throw new TypeError('Invalid reference list.');
  return value.map((item) => decode(object(item)));
}
export function samples(value: Record<string, unknown>): Sample[] {
  return list(value.items, (item) => ({
    public_id: text(item.public_id),
    revision: text(item.revision),
    ...(item.code === undefined ? {} : { code: text(item.code) }),
  }));
}
function localized(value: unknown): { pl: string; en: string } {
  const source = object(value);
  return { pl: text(source.pl), en: text(source.en) };
}
export function settings(value: Record<string, unknown>): Settings {
  const fields = object(value.settings);
  const secret =
    fields.credential === undefined ? undefined : object(fields.credential);
  if (secret && typeof secret.set !== 'boolean')
    throw new TypeError('Invalid secret projection.');
  return {
    revision: integer(value.revision),
    review_id: text(value.review_id),
    label: text(object(fields.label).value),
    labelNames: localized(object(fields.label).names),
    labelDescriptions: localized(object(fields.label).descriptions),
    ...(secret
      ? {
          credentialSet: secret.set as boolean,
          credentialNames: localized(secret.names),
        }
      : {}),
  };
}
/** Native guarded snapshot catalog; no encoded read representation is supported. */
export function snapshots(value: Record<string, unknown>): Snapshot[] {
  return list(value.snapshots, (item) => ({
    public_id: text(item.public_id),
    name: text(item.name),
    source_revision: integer(item.source_revision),
    created_at: text(item.created_at),
  }));
}
export function history(value: Record<string, unknown>): {
  items: HistoryEntry[];
  next: string | null;
} {
  return {
    items: list(value.items, (item) => ({
      public_id: text(item.public_id),
      occurred_at: text(item.occurred_at),
      action: text(item.action),
      outcome: text(item.outcome),
    })),
    next: value.next === null ? null : text(value.next),
  };
}
export function work(value: Record<string, unknown>): WorkEntry[] {
  return list(value.items, (item) => {
    if (
      typeof item.cancellation_requested !== 'boolean' ||
      typeof item.progress !== 'string'
    )
      throw new TypeError('Invalid work response.');
    return {
      work_id: text(item.work_id),
      operation: text(item.operation),
      status: text(item.status),
      progress: item.progress,
      attempts: integer(item.attempts),
      cancellation_requested: item.cancellation_requested,
    };
  });
}
