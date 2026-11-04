/** Auth-owned presentation controls; consumers own drafts, validation and writes. */
export type AuthIdentityField = 'name' | 'first_name' | 'surname' | 'login';
export interface AuthIdentityFieldsProps {
  locale: 'pl' | 'en';
  naming: string;
  columns?: 1 | 2;
  values: Partial<Record<AuthIdentityField, string | null>>;
  errors?: Partial<Record<AuthIdentityField, string | readonly string[]>>;
  showName?: boolean;
  showLogin?: boolean;
  loginRequired?: boolean;
  readonly?: boolean;
  readonlyFields?: readonly string[];
  disabled?: boolean;
}
export interface AuthDisplayPreferenceFieldsProps {
  locale: 'pl' | 'en';
  language: string;
  theme: string;
  disabled?: boolean;
}
export interface AuthEmailPreferenceFieldsProps {
  locale: 'pl' | 'en';
  label: string;
  enabled: boolean;
  recipients: string[];
  recipientSelection?: boolean;
  options: { value: string; label: string }[];
  error?: string | readonly string[];
  disabled?: boolean;
}
