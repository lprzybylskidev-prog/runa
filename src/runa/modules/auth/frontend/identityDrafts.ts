import type { AdminJson } from '@/runa/admin';
/** Private inactive-name drafts; only the active representation enters the form owner. */
export function identityDrafts(
  get: (key: string) => AdminJson,
  set: (key: string, value: AdminJson) => void,
) {
  const drafts = new Map<string, AdminJson>();
  const keys = (mode: string) =>
    mode === 'full' ? ['first_name', 'surname'] : ['name'];
  return {
    clear: () => drafts.clear(),
    change(before: string, after: string) {
      if (before === after) return;
      for (const key of keys(before)) drafts.set(key, get(key));
      for (const key of keys(after))
        if (drafts.has(key)) set(key, drafts.get(key) ?? null);
      for (const key of keys(before)) set(key, null);
    },
  };
}
