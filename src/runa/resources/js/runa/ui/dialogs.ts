import type { ContentLoadingShape } from './loading';
import type { ButtonIntent } from './controls';
export type DialogSize = 'sm' | 'md' | 'lg' | 'xl' | 'full';
export type DialogCloseReason = 'close' | 'escape' | 'outside';
export interface DialogProps {
  modelValue: boolean;
  title: string;
  description?: string;
  size?: DialogSize;
  busy?: boolean;
  loading?: boolean;
  loadingLabel?: string;
  loadingShape?: ContentLoadingShape;
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
  confirmIntent?: ButtonIntent | undefined;
}
