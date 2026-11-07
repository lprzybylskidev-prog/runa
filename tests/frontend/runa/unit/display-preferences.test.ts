import { afterEach, expect, it } from '@runa/testing/vitest';
import { enableAutoUnmount, mount } from '@runa/testing/vue';
import { accountPreferences } from '../../../../src/runa/modules/auth/frontend/preferences/session';
import {
  readPreference,
  savePreference,
} from '../../../../src/runa/resources/js/lib/preferences';
import { preferenceKey } from '@/runa/ui';
import Consumer from './fixtures/PreferenceDisplayConsumer.vue';

enableAutoUnmount(afterEach);
afterEach(() => {
  accountPreferences.disconnect();
  localStorage.clear();
});
const scope = {
  applicationId: 'runa',
  moduleKey: 'display',
  viewKey: 'global',
};

it('renders application defaults for flat-only preferences and persists guest edits only in scoped storage', async () => {
  localStorage.clear();
  accountPreferences.disconnect();
  localStorage.setItem('runa.locale', 'en');
  localStorage.setItem('runa.theme', 'light');
  const wrapper = mount(Consumer);
  expect(wrapper.get('[data-language]').text()).toBe('pl');
  expect(wrapper.get('[data-theme]').text()).toBe('system');
  expect(localStorage.getItem(preferenceKey(scope, 'runa.locale'))).toBeNull();
  expect(localStorage.getItem(preferenceKey(scope, 'runa.theme'))).toBeNull();
  await wrapper.get('[data-language]').trigger('click');
  await wrapper.get('[data-theme]').trigger('click');
  expect(
    JSON.parse(localStorage.getItem(preferenceKey(scope, 'runa.locale'))!),
  ).toEqual({ version: 1, value: 'en' });
  expect(
    JSON.parse(localStorage.getItem(preferenceKey(scope, 'runa.theme'))!),
  ).toEqual({ version: 1, value: 'dark' });
  expect(localStorage.getItem('runa.locale')).toBe('en');
  expect(localStorage.getItem('runa.theme')).toBe('light');
  savePreference('runa.locale', 'pl');
  expect(localStorage.getItem('runa.locale')).toBe('en');
  wrapper.unmount();
  const reopened = mount(Consumer);
  expect(reopened.get('[data-language]').text()).toBe('pl');
  expect(reopened.get('[data-theme]').text()).toBe('dark');
});

it('keeps scoped guest values across account hold and reset without resurrecting flat preferences', () => {
  localStorage.clear();
  accountPreferences.disconnect();
  localStorage.setItem('runa.locale', 'en');
  savePreference('runa.locale', 'pl');
  savePreference('runa.theme', 'light');
  accountPreferences.hold('00000000-0000-7000-8000-000000000001');
  expect(readPreference('runa.locale')).toBeNull();
  expect(readPreference('runa.theme')).toBeNull();
  accountPreferences.disconnect();
  expect(readPreference('runa.locale')).toBe('pl');
  expect(readPreference('runa.theme')).toBe('light');
  accountPreferences.store.reset(scope, 'runa.locale');
  expect(readPreference('runa.locale')).toBeNull();
  expect(localStorage.getItem('runa.locale')).toBe('en');
  expect(readPreference('runa.theme')).toBe('light');
});
