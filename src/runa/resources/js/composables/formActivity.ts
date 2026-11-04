import type { ComputedRef, InjectionKey } from 'vue';
/** Private UI composition: the nearest Form owns submit progress, never HTTP traffic. */
export const formActivity: InjectionKey<ComputedRef<boolean>> =
  Symbol('formActivity');
