import type { VNode } from 'vue';
import type { EntryPage, EntrySession } from '../entry';
import type { ActivitySession } from '../useApplicationSession';
import type { AccountHistoryDetail } from '../historyDetails';
import type {
  MinimalLayoutProps,
  MinimalLayoutSlots,
  ApplicationLayoutProps,
  ApplicationLayoutSlots,
} from '../../../../resources/js/runa/ui/layouts';
import type AuthAvatarCropper from '../AuthAvatarCropper.vue';

export type AuthAuthenticated = (session: EntrySession) => void;
export interface AuthEntryProps {
  page: EntryPage;
  authenticated?: AuthAuthenticated | undefined;
}
export interface AuthAccountProps {
  accountId: string;
}
export interface AuthApplicationProps {
  account?: boolean;
}
export interface AuthApplicationSlots {
  default?(scope: { session: ActivitySession }): VNode[];
  loading?(scope: { account: boolean }): VNode[];
}
export interface AuthHistoryDetailProps {
  open: boolean;
  loading: boolean;
  failed: boolean;
  detail: AccountHistoryDetail | undefined;
  locale: 'pl' | 'en';
}
export type AuthHistoryDetailEmits = { close: []; retry: [] };
export interface AuthRecoveryCodesDialogProps {
  codes: readonly string[];
  locale?: 'pl' | 'en';
  busy?: boolean;
}
export type AuthRecoveryCodesDialogEmits = { acknowledge: [] };
export interface AuthTotpSetupProps {
  secret: string;
  qrCode?: string;
  locale?: 'pl' | 'en';
}
export interface AuthAvatarCropperProps {
  file: File;
  locale: 'pl' | 'en';
  maxBytes: number;
}
export type AuthAvatarCropperEmits = { ready: [value: boolean] };
export type AuthAvatarCropperHandle = Pick<
  InstanceType<typeof AuthAvatarCropper>,
  'crop'
>;

/** Layout replacements forward these props and all supplied scoped slots. */
export type AuthMinimalLayoutProps = MinimalLayoutProps;
export type AuthMinimalLayoutSlots = MinimalLayoutSlots;
export type AuthApplicationLayoutProps = ApplicationLayoutProps;
export type AuthApplicationLayoutSlots = ApplicationLayoutSlots;
