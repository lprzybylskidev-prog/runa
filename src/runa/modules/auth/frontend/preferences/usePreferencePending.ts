import { onScopeDispose, ref } from 'vue';
import { accountPreferences } from './session';
/** Observe the existing account adapter; never start another session or snapshot. */
export function usePreferencePending() {
  const pending = ref(accountPreferences.status().pending);
  const stop = accountPreferences.subscribeStatus(() => {
    pending.value = accountPreferences.status().pending;
  });
  onScopeDispose(stop);
  return pending;
}
