import { ref, watch } from 'vue';
import type {
  PreferenceDefinition,
  PreferenceStore,
} from '../lib/preferenceStore';
import { defaultPreferenceStore } from '../lib/preferenceStore';
import type { PreferenceScope } from '../runa/ui/preferences';

export function usePreference<T>(
  scope: PreferenceScope,
  control: string,
  definition: PreferenceDefinition<T>,
  store: PreferenceStore = defaultPreferenceStore,
) {
  const value = ref<T>(store.read(scope, control, definition));
  watch(value, (next) => store.write(scope, control, definition, next as T), {
    deep: true,
  });
  return value;
}
