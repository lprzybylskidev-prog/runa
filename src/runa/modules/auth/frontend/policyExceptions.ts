import { computed, onScopeDispose, ref, watch } from 'vue';
import type { AdminFormController, AdminJson, AdminText } from '@/runa/admin';

export const policyRuleNames: Record<string, AdminText> = {
  password_expiry_days: { pl: 'Ważność hasła', en: 'Password lifetime' },
  session_idle_minutes: {
    pl: 'Limit bezczynności sesji',
    en: 'Session idle limit',
  },
};
export interface PolicyException {
  public_id: string;
  key: string;
  value: number;
  reason: string;
  expires_at: string | null;
  revoked_at: string | null;
  policy_revision: number;
  version: number;
}
function rows(value: AdminJson): PolicyException[] {
  const result: unknown = value;
  if (!Array.isArray(result)) return [];
  return result.filter(
    (row): row is PolicyException =>
      !!row &&
      typeof row === 'object' &&
      !Array.isArray(row) &&
      typeof row.public_id === 'string' &&
      typeof row.key === 'string' &&
      typeof row.value === 'number' &&
      typeof row.reason === 'string' &&
      (row.expires_at === null || typeof row.expires_at === 'string') &&
      (row.revoked_at === null || typeof row.revoked_at === 'string') &&
      typeof row.policy_revision === 'number' &&
      typeof row.version === 'number',
  );
}
export function usePolicyExceptions(
  controller: AdminFormController,
  section: string,
) {
  // Read authoritative records, never unsaved operation parameters.
  const get = (key: string): AdminJson =>
    controller.records.value[section]?.values[key] ?? null;
  const clock = ref(Date.now());
  const projection = computed(() => rows(get('active_exceptions')));
  const active = computed(() =>
    projection.value.filter(
      (row) =>
        row.revoked_at === null &&
        (row.expires_at === null || Date.parse(row.expires_at) > clock.value) &&
        row.policy_revision === get('policy_revision'),
    ),
  );
  let timer: ReturnType<typeof setTimeout> | undefined;
  watch(
    projection,
    () => {
      clearTimeout(timer);
      clock.value = Date.now();
      schedule();
    },
    { immediate: true },
  );
  function schedule() {
    const next = Math.min(
      ...active.value.flatMap((row) =>
        row.expires_at === null ? [] : [Date.parse(row.expires_at)],
      ),
    );
    if (!Number.isFinite(next)) return;
    timer = setTimeout(
      () => {
        clock.value = Date.now();
        schedule();
      },
      Math.min(Math.max(1, next - Date.now()), 2147483647),
    );
  }
  onScopeDispose(() => clearTimeout(timer));
  function version(key: string): number {
    const versions = get('exception_versions');
    if (!versions || typeof versions !== 'object' || Array.isArray(versions))
      return 0;
    const value: unknown = (versions as Record<string, unknown>)[key];
    return typeof value === 'number' ? value : 0;
  }
  return { active, version, get };
}
