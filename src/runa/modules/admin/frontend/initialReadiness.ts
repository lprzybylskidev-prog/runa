import { getCurrentInstance, inject, onScopeDispose } from 'vue';
import type { ComputedRef, InjectionKey } from 'vue';
export interface AdminInitialReadiness {
  ready: ComputedRef<boolean>;
  register: (settled: () => boolean) => () => void;
}
export const adminInitialReadinessKey: InjectionKey<AdminInitialReadiness> =
  Symbol('admin-initial-readiness');
/** Explicit initial presentation dependencies, never a global HTTP counter. */
export function useAdminInitialReadiness(settled?: () => boolean) {
  if (!getCurrentInstance()) return undefined;
  const region = inject(adminInitialReadinessKey, undefined);
  if (region && settled) onScopeDispose(region.register(settled));
  return region;
}
