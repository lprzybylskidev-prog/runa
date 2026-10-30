import type { InjectionKey } from 'vue';
/** Shared shell already validates the session; standalone layouts retain preference bootstrap. */
export const applicationSessionOwner: InjectionKey<boolean> = Symbol(
  'runa.application.session-owner',
);
