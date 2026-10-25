import { computed, inject } from 'vue';
import type { Component, ComputedRef, InjectionKey } from 'vue';

/** Application-owned presentation. Omitted entries retain the supplied defaults. */
export interface AuthPresentation {
  minimalLayout?: Component;
  applicationLayout?: Component;
  entry?: Component;
  account?: Component;
  accountLoading?: Component;
  welcome?: Component;
}
export const authPresentationKey: InjectionKey<ComputedRef<AuthPresentation>> =
  Symbol('runa.auth.presentation');
export function useAuthPresentation() {
  return inject(
    authPresentationKey,
    computed<AuthPresentation>(() => ({})),
  );
}
