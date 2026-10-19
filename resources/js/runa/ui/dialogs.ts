import type { ControlTone } from './controls';
export type DialogSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';
export type DialogCloseReason = 'close' | 'escape' | 'outside';
export interface DialogProps {
  modelValue: boolean;
  title: string;
  description?: string;
  size?: DialogSize;
  busy?: boolean;
  error?: string;
  closeOnEscape?: boolean;
  closeOnOutside?: boolean;
  closable?: boolean;
  initialFocus?: string;
  locale?: 'pl' | 'en';
}
export interface ConfirmDialogProps extends DialogProps {
  confirmLabel?: string;
  cancelLabel?: string;
  confirmTone?: ControlTone;
}
