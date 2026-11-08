import type { useAuthEntry } from '../useAuthEntry';
import type { useAuthAccount } from '../useAuthAccount';
import type { useAuthWelcome } from '../useAuthWelcome';
import type { useApplicationSession } from '../useApplicationSession';

/** Complete reactive controller surfaces; type-only imports add no runtime code.
 * Private collaborator extraction preserves the current public controller surface.
 * Intentional contract changes update consumers and the owning public guidance.
 * Mount once per keyed page/account and let Vue dispose the owning scope.
 */
export type AuthEntryController = ReturnType<typeof useAuthEntry>;
export type AuthAccountController = ReturnType<typeof useAuthAccount>;
export type AuthWelcomeController = ReturnType<typeof useAuthWelcome>;
export type ApplicationSessionController = ReturnType<
  typeof useApplicationSession
>;
export type ApplicationNavigate = NonNullable<
  Parameters<typeof useApplicationSession>[0]
>;
