import type {
  AuthEntryController,
  AuthAccountController,
  AuthWelcomeController,
  ApplicationSessionController,
  ApplicationNavigate,
  AuthEntryProps,
  AuthAccountProps,
  AuthApplicationProps,
  AuthApplicationSlots,
  AuthHistoryDetailProps,
  AuthHistoryDetailEmits,
  AuthRecoveryCodesDialogProps,
  AuthRecoveryCodesDialogEmits,
  AuthTotpSetupProps,
  AuthAvatarCropperProps,
  AuthAvatarCropperEmits,
  AuthAvatarCropperHandle,
  AuthMinimalLayoutProps,
  AuthMinimalLayoutSlots,
  AuthApplicationLayoutProps,
  AuthApplicationLayoutSlots,
} from '@/runa/auth';
import type {
  MinimalLayoutProps,
  MinimalLayoutSlots,
  ApplicationLayoutProps,
  ApplicationLayoutSlots,
  HttpErrorLayoutProps,
  HttpErrorContentData,
} from '@/runa/ui';
import type {
  ModulePageProps,
  ModulePageSlots,
  ModuleSlotProps,
  ModuleSlotSlots,
} from '@/runa/modules';

/** A customer can name nested signature types without importing implementations. */
export interface CustomerScreens {
  entry: AuthEntryController;
  account: AuthAccountController;
  welcome: AuthWelcomeController;
  session: ApplicationSessionController;
  navigate: ApplicationNavigate;
  entryProps: AuthEntryProps;
  accountProps: AuthAccountProps;
  application: { props: AuthApplicationProps; slots: AuthApplicationSlots };
  history: { props: AuthHistoryDetailProps; events: AuthHistoryDetailEmits };
  codes: {
    props: AuthRecoveryCodesDialogProps;
    events: AuthRecoveryCodesDialogEmits;
  };
  totp: AuthTotpSetupProps;
  avatar: {
    props: AuthAvatarCropperProps;
    events: AuthAvatarCropperEmits;
    handle: AuthAvatarCropperHandle;
  };
  minimal: {
    props: MinimalLayoutProps & AuthMinimalLayoutProps;
    slots: MinimalLayoutSlots & AuthMinimalLayoutSlots;
  };
  layout: {
    props: ApplicationLayoutProps & AuthApplicationLayoutProps;
    slots: ApplicationLayoutSlots & AuthApplicationLayoutSlots;
  };
  error: { props: HttpErrorLayoutProps; content: HttpErrorContentData };
  page: { props: ModulePageProps; slots: ModulePageSlots };
  contribution: { props: ModuleSlotProps; slots: ModuleSlotSlots };
}

export function customerAccountActions(account: AuthAccountController) {
  return {
    history: account.openHistoryDetail,
    security: account.saveSection,
    details: account.resolveDetails,
    avatar: account.resolveAvatar,
    addresses: account.saveSecondary,
    preferences: account.saveDelivery,
    reset: account.resetPreferences,
    devices: account.revokeTrustedDevice,
    restore: account.restoreDraft,
    refresh: account.refresh,
  };
}
