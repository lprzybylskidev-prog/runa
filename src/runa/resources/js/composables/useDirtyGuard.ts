import {
  computed,
  onScopeDispose,
  ref,
  toValue,
  watch,
  type MaybeRefOrGetter,
} from 'vue';
/** Supply application UI for confirmation; the only browser prompt is beforeunload. */
export function useDirtyGuard(options: {
  dirty: MaybeRefOrGetter<boolean>;
  enabled?: MaybeRefOrGetter<boolean>;
  confirmLeave: () => boolean | Promise<boolean>;
}) {
  const pending = ref(false);
  const protectedDraft = computed(
    () => toValue(options.enabled ?? true) && toValue(options.dirty),
  );
  let disposed = false;
  let skipUnload = false;
  async function beforeLeave(): Promise<boolean> {
    if (pending.value || disposed) return false;
    if (!protectedDraft.value) return true;
    pending.value = true;
    try {
      return Boolean(await options.confirmLeave()) && !disposed;
    } catch {
      return false;
    } finally {
      pending.value = false;
    }
  }
  async function requestLeave(
    action: () => void | Promise<void>,
  ): Promise<boolean> {
    if (!(await beforeLeave())) return false;
    skipUnload = true;
    try {
      await action();
      return true;
    } finally {
      skipUnload = false;
    }
  }
  function unload(event: globalThis.BeforeUnloadEvent) {
    if (!protectedDraft.value || skipUnload) return;
    event.preventDefault();
    event.returnValue = '';
  }
  const stop = watchProtection();
  function watchProtection() {
    // Listener registration follows dirty state, preserving bfcache for clean forms.
    return watch(
      protectedDraft,
      (active) => {
        window.removeEventListener('beforeunload', unload);
        if (active) window.addEventListener('beforeunload', unload);
      },
      { immediate: true },
    );
  }
  onScopeDispose(() => {
    disposed = true;
    stop();
    window.removeEventListener('beforeunload', unload);
  });
  return { pending, beforeLeave, requestLeave };
}
