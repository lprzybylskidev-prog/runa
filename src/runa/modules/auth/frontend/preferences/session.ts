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

let initialization: Promise<void> | undefined;
export function initializePreferenceSession(): Promise<void> {
  return (initialization ??= (async () => {
    try {
      await request('/runa/auth/session', {
        signal: AbortSignal.timeout(5000),
      });
      if (accountPreferences.status().pending)
        await new Promise<void>((resolve) => {
          const stop = accountPreferences.subscribeStatus(() => {
            if (!accountPreferences.status().pending) {
              stop();
              resolve();
            }
          });
        });
    } catch (error) {
      if (error instanceof RequestCancelledError) return;
      if (
        error instanceof ApiError &&
        [401, 403, 404, 503].includes(error.status)
      )
        return;
      accountPreferences.reportLoadFailure();
    }
  })());
}
