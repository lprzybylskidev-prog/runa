import {
  hasInjectionContext,
  inject,
  onScopeDispose,
  provide,
  shallowRef,
  type InjectionKey,
} from 'vue';
import type { OperationProofController } from './useOperationProof';
export interface PasswordConfirmation {
  request(controller: OperationProofController): Promise<boolean>;
  cancel(controller?: OperationProofController): void;
}
const key: InjectionKey<PasswordConfirmation> = Symbol(
  'auth-password-confirmation',
);
export function usePasswordConfirmation() {
  return hasInjectionContext() ? inject(key, undefined) : undefined;
}
export function providePasswordConfirmation() {
  const active = shallowRef<OperationProofController>();
  let finish: ((confirmed: boolean) => void) | undefined;
  function complete(confirmed = true) {
    const resolve = finish;
    finish = undefined;
    active.value = undefined;
    resolve?.(confirmed);
  }
  const host: PasswordConfirmation = {
    async request(controller) {
      if (active.value) return false;
      void controller.begin();
      if (!controller.open.value) return false;
      active.value = controller;
      return new Promise<boolean>((resolve) => {
        finish = resolve;
      });
    },
    cancel(controller) {
      if (controller && active.value !== controller) return;
      const proof = active.value;
      complete(false);
      proof?.reset();
    },
  };
  provide(key, host);
  onScopeDispose(() => host.cancel());
  return { active, complete, cancel: host.cancel };
}
