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
export interface ButtonAction {
  id: string;
  label: string;
  disabled?: boolean;
}
