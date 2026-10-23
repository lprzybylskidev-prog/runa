import { onScopeDispose, ref, watch } from 'vue';
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
  let syncing = false;
  watch(
    value,
    (next) => {
      if (!syncing) store.write(scope, control, definition, next as T);
    },
    {
      deep: true,
      flush: 'sync',
    },
  );
  const unsubscribe = store.subscribe?.(() => {
    syncing = true;
    const next = store.read(scope, control, definition);
    if (JSON.stringify(value.value) !== JSON.stringify(next))
      value.value = next;
    syncing = false;
  });
  if (unsubscribe) onScopeDispose(unsubscribe);
  return value;
}
