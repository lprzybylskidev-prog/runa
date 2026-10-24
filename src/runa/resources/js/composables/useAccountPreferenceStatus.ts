import { onScopeDispose, shallowRef } from 'vue';
import { accountPreferences } from '../lib/preferenceStore';
import { request, ApiError, RequestCancelledError } from '../lib/http';
let initialized = false;

/** The same lifecycle binding handles subsequent login/logout and reloads. */
export function useAccountPreferenceStatus() {
  const status = shallowRef(accountPreferences.status());
  const unsubscribe = accountPreferences.subscribeStatus(() => {
    status.value = accountPreferences.status();
  });
  onScopeDispose(unsubscribe);
  return status;
}

export function initializePreferenceSession(): void {
  if (!initialized) {
    initialized = true;
    void request('/runa/auth/session').catch((error: unknown) => {
      if (!(
        error instanceof RequestCancelledError ||
        (error instanceof ApiError &&
          [401, 403, 404, 503].includes(error.status))
      ))
        accountPreferences.reportLoadFailure();
    });
  }
}
