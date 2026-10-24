/** Public Auth entry, welcome and account/application composition. */
import { defineAsyncComponent } from 'vue';
export const AuthEntry = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthEntry.vue'),
);
export {
  entryPage,
  safeReturnPath,
} from '../../../../modules/auth/frontend/entry';
export type { EntryPage } from '../../../../modules/auth/frontend/entry';

export const AuthWelcome = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthWelcome.vue'),
);
export const AuthApplication = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthApplication.vue'),
);

export const DefaultApplication = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/DefaultApplication.vue'),
);
export const AuthAccount = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthAccount.vue'),
);
export { default as AuthPresentationProvider } from '../../../../modules/auth/frontend/AuthPresentationProvider.vue';
export type { AuthPresentation } from '../../../../modules/auth/frontend/presentation';
export { useAuthEntry } from '../../../../modules/auth/frontend/useAuthEntry';
export { useAuthAccount } from '../../../../modules/auth/frontend/useAuthAccount';
export { useAuthWelcome } from '../../../../modules/auth/frontend/useAuthWelcome';
export { useApplicationSession } from '../../../../modules/auth/frontend/useApplicationSession';
export type { ActivitySession } from '../../../../modules/auth/frontend/useApplicationSession';
export type {
  EntrySession,
  EntryOptions,
  SecurityOptions,
  FactorMethod,
} from '../../../../modules/auth/frontend/entry';
export type {
  AccountTab,
  AccountTask,
} from '../../../../modules/auth/frontend/useAuthAccount';
export type {
  Profile,
  AccountRow,
} from '../../../../modules/auth/frontend/account';
