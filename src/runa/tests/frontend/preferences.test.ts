import { expect, it } from 'vitest';
import { createPreferenceStore, preferenceKey } from '@/runa/ui';
import type { PreferenceDefinition } from '@/runa/ui';
const scope = {
  applicationId: 'atlas',
  moduleKey: 'shell',
  viewKey: 'navigation',
};
const bool: PreferenceDefinition<boolean> = {
  version: 2,
  defaultValue: false,
  validate: (value): value is boolean => typeof value === 'boolean',
  migrate: (value, version) =>
    version === 1 && value === 'collapsed' ? true : undefined,
};
function fixture() {
  const data = new Map<string, string>();
  return {
    data,
    store: createPreferenceStore({
      read: (key) => data.get(key) ?? null,
      write: (key, value) => {
        data.set(key, value);
      },
      remove: (key) => {
        data.delete(key);
      },
    }),
  };
}
it('isolates application/view/control identities and persists/reset values', () => {
  const { store } = fixture();
  store.write(scope, 'collapse', bool, true);
  expect(store.read(scope, 'collapse', bool)).toBe(true);
  expect(
    store.read({ ...scope, applicationId: 'other' }, 'collapse', bool),
  ).toBe(false);
  expect(store.read({ ...scope, viewKey: 'other' }, 'collapse', bool)).toBe(
    false,
  );
  expect(store.read(scope, 'other', bool)).toBe(false);
  store.reset(scope, 'collapse');
  expect(store.read(scope, 'collapse', bool)).toBe(false);
});
it('validates JSON, migrates older schemas and resets corrupt/future/invalid values', () => {
  const { data, store } = fixture();
  const key = preferenceKey(scope, 'collapse');
  data.set(key, JSON.stringify({ version: 1, value: 'collapsed' }));
  expect(store.read(scope, 'collapse', bool)).toBe(true);
  expect(JSON.parse(data.get(key)!)).toEqual({ version: 2, value: true });
  for (const raw of [
    '{broken',
    'null',
    '{}',
    '{"version":100,"value":true}',
    '{"version":2,"value":"true"}',
  ]) {
    data.set(key, raw);
    expect(store.read(scope, 'collapse', bool)).toBe(false);
  }
});
it('retains session values when storage is denied or reaches quota', () => {
  const denied = createPreferenceStore({
    read: () => {
      throw new Error('Denied');
    },
    write: () => {
      throw new Error('Denied');
    },
    remove: () => {
      throw new Error('Denied');
    },
  });
  expect(denied.read(scope, 'collapse', bool)).toBe(false);
  denied.write(scope, 'collapse', bool, true);
  expect(denied.read(scope, 'collapse', bool)).toBe(true);
  const quota = createPreferenceStore({
    read: () => null,
    write: () => {
      throw new Error('Quota');
    },
    remove: () => {},
  });
  quota.write(scope, 'collapse', bool, true);
  expect(quota.read(scope, 'collapse', bool)).toBe(true);
  quota.reset(scope, 'collapse');
  expect(quota.read(scope, 'collapse', bool)).toBe(false);
});
it('reads an existing legacy preference without removing its key', () => {
  const { data, store } = fixture();
  data.set('runa.locale', 'en');
  const definition: PreferenceDefinition<string> = {
    version: 1,
    defaultValue: 'pl',
    validate: (v): v is string => v === 'pl' || v === 'en',
    legacyKey: 'runa.locale',
  };
  expect(store.read(scope, 'locale', definition)).toBe('en');
  expect(data.get('runa.locale')).toBe('en');
  expect(data.has(preferenceKey(scope, 'locale'))).toBe(true);
});
