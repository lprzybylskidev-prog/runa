import {
  request,
  ApiError,
  RequestCancelledError,
} from '../../../../resources/js/lib/http';
import { createPreferenceStore } from '../../../../resources/js/lib/preferenceStore';
import {
  createAccountPreferenceStore,
  bindPreferenceSession,
} from './accountPreferences';

/** The supplied UI uses one account-aware adapter; generic stores stay independent. */
export const guestPreferenceStore = /* @__PURE__ */ createPreferenceStore();
export const accountPreferences =
  createAccountPreferenceStore(guestPreferenceStore);
bindPreferenceSession(accountPreferences);
export const defaultPreferenceStore = accountPreferences.store;

let initialized = false;
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
