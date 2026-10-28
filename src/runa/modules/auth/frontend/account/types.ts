export type AccountTab =
  | 'details'
  | 'security'
  | 'addresses'
  | 'avatar'
  | 'sessions'
  | 'trust'
  | 'preferences'
  | 'loginHistory'
  | 'accountHistory';
export type AccountTask =
  | 'details'
  | 'password'
  | 'email'
  | 'secondaryAdd'
  | 'secondaryChange'
  | 'secondaryRemove'
  | 'avatar'
  | 'avatarRemove'
  | 'totpBegin'
  | 'totpConfirm'
  | 'emailEnable'
  | 'disable'
  | 'recoveryRotate'
  | 'trustIssue'
  | 'deactivate'
  | 'delete';
export type AccountSection = 'details' | 'avatar' | 'password' | 'security';
export interface DeliveryChoice {
  module: string;
  key: string;
  names: { pl: string; en: string };
  mandatory: boolean;
  sensitive: boolean;
  enabled: boolean;
  recipients: string[];
}
