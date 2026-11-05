/** Public Auth entry, welcome and account/application composition. */
import { defineAsyncComponent } from 'vue';
export const AuthEntry = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthEntry.vue'),
);
export const AuthAdminCreation = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthAdminCreation.vue'),
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
export const AuthRecoveryCodesDialog = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthRecoveryCodesDialog.vue'),
);
export const AuthTotpSetup = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthTotpSetup.vue'),
);
export const AuthAvatarCropper = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthAvatarCropper.vue'),
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
  AccountSection,
} from '../../../../modules/auth/frontend/useAuthAccount';
export type {
  Profile,
  AccountRow,
} from '../../../../modules/auth/frontend/account';

export { default as AuthAccountLoading } from '../../../../modules/auth/frontend/AuthAccountLoading.vue';

export type {
  AccountHistoryDetail,
  HistoryValue,
} from '../../../../modules/auth/frontend/historyDetails';

export type * from '../../../../modules/auth/frontend/contracts/controllers';
export type * from '../../../../modules/auth/frontend/contracts/presentation';
export type { Restriction } from '../../../../modules/auth/frontend/entry';

export { default as ApplicationSessionShell } from '../../../../modules/auth/frontend/ApplicationSessionShell.vue';
export { useOperationProof } from '../../../../modules/auth/frontend/useOperationProof';
export type {
  OperationProofController,
  ProofRequirement,
} from '../../../../modules/auth/frontend/useOperationProof';

/** Auth-owned Admin presentation; public props come from @/runa/admin. */
export const AuthAdminUsers = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/components/admin_users.vue'),
);
export const AuthAdminSection = defineAsyncComponent(
  () =>
    import('../../../../modules/auth/frontend/components/admin_section.vue'),
);

export { default as AuthOperationProofPage } from '../../../../modules/auth/frontend/AuthOperationProofPage.vue';
export { useAuthPresentation } from '../../../../modules/auth/frontend/presentation';

export { useProtectedActivity } from '../../../../modules/auth/frontend/useProtectedActivity';
export type {
  ProtectedActivityOptions,
  ProtectedActivityStatus,
} from '../../../../modules/auth/frontend/useProtectedActivity';

export { default as AuthIdentityFields } from '../../../../modules/auth/frontend/AuthIdentityFields.vue';
export { default as AuthDisplayPreferenceFields } from '../../../../modules/auth/frontend/AuthDisplayPreferenceFields.vue';
export { default as AuthEmailPreferenceFields } from '../../../../modules/auth/frontend/AuthEmailPreferenceFields.vue';
export type {
  AuthIdentityField,
  AuthIdentityFieldsProps,
  AuthDisplayPreferenceFieldsProps,
  AuthEmailPreferenceFieldsProps,
} from '../../../../modules/auth/frontend/contracts/fields';

export { default as AuthSecondaryEmailFields } from '../../../../modules/auth/frontend/AuthSecondaryEmailFields.vue';
export type { AuthSecondaryEmailFieldsProps } from '../../../../modules/auth/frontend/contracts/fields';

export {
  usePasswordConfirmation,
  providePasswordConfirmation,
} from '../../../../modules/auth/frontend/passwordConfirmation';
export type { PasswordConfirmation } from '../../../../modules/auth/frontend/passwordConfirmation';
