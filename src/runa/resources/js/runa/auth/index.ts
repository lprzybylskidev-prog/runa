/** Public Auth entry, welcome and account/application composition. */
import { defineAsyncComponent } from 'vue';
export const AuthEntry = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthEntry.vue'),
);
export {
  entryPage,
  safeReturnPath,
} from '../../../../modules/auth/frontend/entry';
export type { EntryPage } from '../../../../modules/auth/frontend/entry';

export const AuthWelcome = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthWelcome.vue'),
);
export const AuthApplication = defineAsyncComponent(
  () => import('../../../../modules/auth/frontend/AuthApplication.vue'),
);
