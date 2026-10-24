/** Public Auth entry composition; account/session-expiry integration is separate. */
import { defineAsyncComponent } from 'vue';
export const AuthEntry = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthEntry.vue'),
);
export {
  entryPage,
  safeReturnPath,
} from '../../../../modules/auth/frontend/entry';
export type { EntryPage } from '../../../../modules/auth/frontend/entry';
