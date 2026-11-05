export type ControlTone =
  | 'primary'
  | 'secondary'
  | 'success'
  | 'danger'
  | 'warning'
  | 'info'
  | 'accent';
export type ControlSize = 'sm' | 'md' | 'lg';
export type ButtonVariant = 'neutral' | 'solid' | 'outline' | 'soft' | 'ghost';

/** Action meaning is independent of feedback context, language and password proof. */
export type ButtonIntent =
  | 'neutral'
  | 'commit'
  | 'cancel'
  | 'discard'
  | 'inspect'
  | 'mixed'
  | 'caution'
  | 'danger';
export type ButtonTone = ControlTone | 'neutral';
export const buttonIntentTones: Readonly<Record<ButtonIntent, ButtonTone>> = {
  neutral: 'neutral',
  commit: 'success',
  cancel: 'neutral',
  discard: 'warning',
  inspect: 'info',
  mixed: 'primary',
  caution: 'warning',
  danger: 'danger',
};
