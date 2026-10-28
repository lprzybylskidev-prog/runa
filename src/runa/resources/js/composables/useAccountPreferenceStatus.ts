import { onScopeDispose, shallowRef } from 'vue';
import { accountPreferences } from '../../../modules/auth/frontend/preferences/session';

/** The same lifecycle binding handles subsequent login/logout and reloads. */
export function useAccountPreferenceStatus() {
  const status = shallowRef(accountPreferences.status());
  const unsubscribe = accountPreferences.subscribeStatus(() => {
    status.value = accountPreferences.status();
  });
  onScopeDispose(unsubscribe);
  return status;
}
