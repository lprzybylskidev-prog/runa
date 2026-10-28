import { computed, inject } from 'vue';
import type { Component, ComputedRef, InjectionKey } from 'vue';

/** Application-owned Vue component definitions. Omitted entries retain defaults.
 * Replacement props/slots/events are named in contracts/presentation and exported
 * by @/runa/auth. Keep Component compatibility for existing Vue wrappers which
 * intentionally consume only part of the supplied props or forward $attrs.
 */
export interface AuthPresentation {
  minimalLayout?: Component;
  applicationLayout?: Component;
  entry?: Component;
  account?: Component;
  accountLoading?: Component;
  historyDetail?: Component;
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
