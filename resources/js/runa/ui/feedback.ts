export type FeedbackTone = 'success' | 'info' | 'warning' | 'danger';
export type BadgeTone = FeedbackTone | 'neutral';
export interface ToastMessage {
  id: string;
  title: string;
  description?: string;
  tone: FeedbackTone;
  /** Milliseconds; 0 stays visible. Errors default to persistent. */
  duration: number;
}
