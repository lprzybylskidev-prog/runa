import { defaultPreferenceStore } from './preferenceStore';
import type { PreferenceDefinition } from './preferenceStore';
const scope = {
  applicationId: 'runa',
  moduleKey: 'display',
  viewKey: 'global',
};
function definition(key: string): PreferenceDefinition<string | null> {
  return {
    version: 1,
    defaultValue: null,
    legacyKey: key,
    validate: (value: unknown): value is string | null =>
      value === null ||
      (typeof value === 'string' &&
        (key === 'runa.locale'
          ? ['pl', 'en'].includes(value)
          : ['light', 'dark', 'system'].includes(value))),
  };
}
export function readPreference(key: string): string | null {
  return defaultPreferenceStore.read(scope, key, definition(key));
}
export function savePreference(key: string, value: string): void {
  defaultPreferenceStore.write(scope, key, definition(key), value);
  // Keep the established keys readable by older consumers and deployments.
  try {
    localStorage.setItem(key, value);
  } catch {
    /* Store retains session memory. */
  }
}
