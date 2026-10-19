import { usePreference } from './usePreference';
import type { PreferenceScope } from '../runa/ui/preferences';
import type { StatisticsMode } from '../runa/ui/loading';
import type { PreferenceStore } from '../lib/preferenceStore';
/** Scope is fixed for this mounted view; remount when switching view identity. */
export function useStatisticsMode(
  scope: PreferenceScope,
  defaultMode: StatisticsMode = 'global',
  store?: PreferenceStore,
) {
  return usePreference<StatisticsMode>(
    scope,
    'statistics.mode',
    {
      version: 1,
      defaultValue: defaultMode,
      validate: (value: unknown): value is StatisticsMode =>
        value === 'global' || value === 'filtered',
    },
    store,
  );
}
