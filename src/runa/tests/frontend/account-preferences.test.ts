import { expect, it } from 'vitest';
import { createAccountPreferenceStore } from '../../resources/js/lib/accountPreferences';
import { createPreferenceStore } from '../../resources/js/lib/preferenceStore';
import { ApiError } from '../../resources/js/lib/http';
const scope = {
  applicationId: 'runa',
  moduleKey: 'display',
  viewKey: 'global',
};
const theme = {
  version: 1,
  defaultValue: 'system',
  validate: (v: unknown): v is string =>
    typeof v === 'string' && ['system', 'dark', 'light'].includes(v),
};
const snapshot = (account: string, value = 'system', revision = 0) => ({
  data: {
    account,
    revision,
    legacy_claimed: false,
    values: [
      {
        scope: 'runa:runa.auth:global:theme',
        value,
        version: 1,
        stored: value !== 'system',
      },
    ],
  },
});
function guest() {
  return createPreferenceStore({
    read: () => null,
    write: () => {},
    remove: () => {},
  });
}
it('discards late account responses and never copies account preferences into guest storage', async () => {
  let resolve!: (value: unknown) => void;
  const store = createAccountPreferenceStore(guest(), (input) =>
    input.application && !resolve
      ? new Promise((done) => {
          resolve = done;
        })
      : Promise.resolve(snapshot('B')),
  );
  const first = store.connect('A');
  await store.connect('B');
  resolve(snapshot('A', 'dark'));
  await first;
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('system');
  store.disconnect();
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('system');
});
it('serializes revision writes and reports conflicts without replay or false success', async () => {
  const writes: unknown[] = [];
  let fail = false;
  const store = createAccountPreferenceStore(guest(), async (input) => {
    if (input.operation === 'snapshot') return snapshot('A');
    writes.push(input);
    if (fail) throw new ApiError(409, {});
    return {
      data: {
        account: 'A',
        scope: 'runa:runa.auth:global:theme',
        revision: writes.length,
        version: 1,
        value: input.value,
      },
    };
  });
  await store.connect('A');
  store.store.write(scope, 'runa.theme', theme, 'dark');
  await store.flush();
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('dark');
  fail = true;
  store.store.write(scope, 'runa.theme', theme, 'light');
  await store.flush();
  expect(store.status().error).toBe('conflict');
  expect(writes).toHaveLength(2);
  store.disconnect();
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('system');
});
it('fences queued writes on logout and resets from server defaults', async () => {
  let release!: (value: unknown) => void;
  let sends = 0;
  const store = createAccountPreferenceStore(guest(), async (input) => {
    if (input.operation === 'snapshot') return snapshot('A');
    sends++;
    return new Promise((done) => {
      release = done;
    });
  });
  await store.connect('A');
  store.store.write(scope, 'runa.theme', theme, 'dark');
  store.store.write(scope, 'runa.theme', theme, 'light');
  await Promise.resolve();
  store.disconnect();
  release({
    data: {
      account: 'A',
      scope: 'runa:runa.auth:global:theme',
      revision: 1,
      version: 1,
      value: 'dark',
    },
  });
  await Promise.resolve();
  await Promise.resolve();
  expect(sends).toBe(1);
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('system');
});

it('renders localized rejected-save feedback through the shared layout control', async () => {
  const { mount } = await import('@vue/test-utils');
  const { nextTick } = await import('vue');
  const { accountPreferences } =
    await import('../../resources/js/lib/preferenceStore');
  const Feedback = (
    await import('../../resources/js/components/AppPreferenceFeedback.vue')
  ).default;
  accountPreferences.hold('00000000-0000-7000-8000-000000000001');
  const wrapper = mount(Feedback, { props: { locale: 'pl' } });
  accountPreferences.store.write(scope, 'runa.theme', theme, 'dark');
  await accountPreferences.flush();
  await nextTick();
  expect(wrapper.get('[role="alert"]').text()).toContain(
    'Nie zapisano preferencji',
  );
  await wrapper.setProps({ locale: 'en' });
  expect(wrapper.get('button').text()).toBe('Reload settings');
  wrapper.unmount();
  accountPreferences.disconnect();
});
