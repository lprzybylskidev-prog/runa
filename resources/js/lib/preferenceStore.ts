import type {
  PreferenceScope,
  PreferenceStorage,
} from '../runa/ui/preferences';

export interface PreferenceDefinition<T> {
  version: number;
  defaultValue: T;
  validate(value: unknown): value is T;
  migrate?: (value: unknown, version: number) => unknown;
  legacyKey?: string;
}

export interface PreferenceStore {
  read<T>(
    scope: PreferenceScope,
    control: string,
    definition: PreferenceDefinition<T>,
  ): T;
  write<T>(
    scope: PreferenceScope,
    control: string,
    definition: PreferenceDefinition<T>,
    value: T,
  ): void;
  reset(scope: PreferenceScope, control: string): void;
}

export const browserPreferenceStorage: PreferenceStorage = {
  read: (key) => localStorage.getItem(key),
  write: (key, value) => localStorage.setItem(key, value),
  remove: (key) => localStorage.removeItem(key),
};

export function preferenceKey(scope: PreferenceScope, control: string): string {
  return `runa.preferences:${[scope.applicationId, scope.moduleKey, scope.viewKey, control].map(encodeURIComponent).join(':')}`;
}

function isJsonValue(value: unknown, seen = new Set<object>()): boolean {
  if (value === null || typeof value === 'string' || typeof value === 'boolean')
    return true;
  if (typeof value === 'number') return Number.isFinite(value);
  if (typeof value !== 'object' || seen.has(value)) return false;
  if (
    !Array.isArray(value) &&
    Object.getPrototypeOf(value) !== Object.prototype
  )
    return false;
  seen.add(value);
  const valid = Object.values(value).every((item) => isJsonValue(item, seen));
  seen.delete(value);
  return valid;
}

/** A failed adapter falls back to memory for this store's lifetime; never stores security state. */
export function createPreferenceStore(
  adapter: PreferenceStorage = browserPreferenceStorage,
): PreferenceStore {
  const memory = new Map<string, string>();
  let available = true;
  function read(key: string): string | null {
    if (available) {
      try {
        const value = adapter.read(key);
        if (value !== null) memory.set(key, value);
        return value;
      } catch {
        available = false;
      }
    }
    return memory.get(key) ?? null;
  }
  function write(key: string, value: string) {
    memory.set(key, value);
    if (available) {
      try {
        adapter.write(key, value);
      } catch {
        available = false;
      }
    }
  }
  return {
    read(scope, control, definition) {
      const key = preferenceKey(scope, control);
      const raw = read(key);
      if (raw !== null) {
        try {
          const parsed: unknown = JSON.parse(raw);
          if (
            typeof parsed !== 'object' ||
            parsed === null ||
            !('version' in parsed) ||
            !Number.isInteger(parsed.version) ||
            !('value' in parsed)
          )
            return definition.defaultValue;
          const value =
            parsed.version === definition.version
              ? parsed.value
              : definition.migrate?.(parsed.value, Number(parsed.version));
          if (isJsonValue(value) && definition.validate(value)) {
            if (parsed.version !== definition.version)
              write(
                key,
                JSON.stringify({ version: definition.version, value }),
              );
            return value;
          }
        } catch {
          /* Corrupt JSON resets to validated defaults. */
        }
      } else if (definition.legacyKey) {
        const legacy = read(definition.legacyKey);
        if (definition.validate(legacy)) {
          write(
            key,
            JSON.stringify({ version: definition.version, value: legacy }),
          );
          return legacy;
        }
      }
      return definition.defaultValue;
    },
    write(scope, control, definition, value) {
      if (!isJsonValue(value) || !definition.validate(value))
        throw new TypeError('Invalid preference value.');
      const serialized = JSON.stringify({ version: definition.version, value });
      // Reject non-JSON values, including lossy/non-finite numbers, through validation of the round trip.
      const parsed: unknown = JSON.parse(serialized);
      if (
        typeof parsed !== 'object' ||
        parsed === null ||
        !('value' in parsed) ||
        !definition.validate(parsed.value)
      )
        throw new TypeError('Preference must be valid JSON.');
      write(preferenceKey(scope, control), serialized);
    },
    reset(scope, control) {
      const key = preferenceKey(scope, control);
      memory.delete(key);
      if (available) {
        try {
          adapter.remove(key);
        } catch {
          available = false;
        }
      }
    },
  };
}

export const defaultPreferenceStore = /* @__PURE__ */ createPreferenceStore();
