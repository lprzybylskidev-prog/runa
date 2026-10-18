import { nextTick, ref, useId } from 'vue';
import type { ToastMessage, FeedbackTone } from '../runa/ui/feedback';
/** Local queue: pass messages to ToastViewport; no application shell or global store. */
export function useToasts() {
  const messages = ref<ToastMessage[]>([]);
  const prefix = useId();
  let sequence = 0;
  const triggers = new Map<string, globalThis.HTMLElement>();
  function dismiss(id: string) {
    const restore =
      typeof document !== 'undefined' &&
      document.activeElement
        ?.closest('[data-toast-id]')
        ?.getAttribute('data-toast-id') === id;
    const trigger = triggers.get(id);
    triggers.delete(id);
    messages.value = messages.value.filter((message) => message.id !== id);
    if (restore)
      void nextTick(() => {
        if (trigger?.isConnected && !trigger.closest('[inert]'))
          trigger.focus();
      });
  }
  function show(input: {
    title: string;
    description?: string;
    tone?: FeedbackTone;
    duration?: number;
  }) {
    const id = `${prefix}-toast-${++sequence}`;
    if (
      typeof document !== 'undefined' &&
      document.activeElement instanceof globalThis.HTMLElement
    )
      triggers.set(id, document.activeElement);
    const tone = input.tone ?? 'info';
    const duration = input.duration ?? (tone === 'danger' ? 0 : 5000);
    messages.value = [
      ...messages.value,
      {
        ...input,
        id,
        tone,
        duration: Number.isFinite(duration) ? Math.max(0, duration) : 0,
      },
    ];
    return id;
  }
  return { messages, show, dismiss };
}
