import { computed, inject } from 'vue';
import type { ComputedRef, InjectionKey } from 'vue';
import type { AdminPresentation } from './contracts';
export const adminPresentationKey: InjectionKey<
  ComputedRef<AdminPresentation>
> = Symbol('runa.admin.presentation');
export function useAdminPresentation(): ComputedRef<AdminPresentation> {
  return inject(
    adminPresentationKey,
    computed(() => ({})),
  );
}
