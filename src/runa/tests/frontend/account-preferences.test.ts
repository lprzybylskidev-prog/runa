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
    'Nie udało się zsynchronizować preferencji',
  );
  await wrapper.setProps({ locale: 'en' });
  expect(wrapper.get('button').text()).toBe('Reload settings');
  wrapper.unmount();
  accountPreferences.disconnect();
});

it('mounts display consumers during delayed hydration without writes and persists explicit changes', async () => {
  const { mount, flushPromises } = await import('@vue/test-utils');
  const { vi } = await import('vitest');
  const { accountPreferences } =
    await import('../../resources/js/lib/preferenceStore');
  const Consumer = (await import('./fixtures/PreferenceDisplayConsumer.vue'))
    .default;
  const account = '00000000-0000-7000-8000-000000000001';
  let release!: () => void;
  const inputs: Record<string, unknown>[] = [];
  const fetch = vi
    .spyOn(window, 'fetch')
    .mockImplementation(async (_url, init) => {
      const input = JSON.parse(String(init?.body)) as Record<string, unknown>;
      inputs.push(input);
      if (input.operation === 'snapshot') {
        await new Promise<void>((resolve) => {
          release = resolve;
        });
        return new Response(JSON.stringify(snapshot(account, 'dark')), {
          status: 200,
          headers: { 'Content-Type': 'application/json' },
        });
      }
      return new Response(
        JSON.stringify({
          data: {
            account,
            scope: `runa:runa.auth:global:${input.key}`,
            revision: inputs.length,
            version: 1,
            value: input.value,
          },
        }),
        { status: 200, headers: { 'Content-Type': 'application/json' } },
      );
    });
  let first: ReturnType<typeof mount> | undefined;
  let second: ReturnType<typeof mount> | undefined;
  try {
    const loading = accountPreferences.connect(account);
    first = mount(Consumer);
    await flushPromises();
    expect(accountPreferences.status().error).toBeNull();
    expect(inputs.map((input) => input.operation)).toEqual(['snapshot']);
    release();
    await loading;
    expect(first.get('[data-theme]').text()).toBe('dark');
    expect(document.documentElement.dataset.theme).toBe('dark');
    expect(accountPreferences.status().error).toBeNull();
    second = mount(Consumer);
    await flushPromises();
    expect(inputs).toHaveLength(1);
    await second.get('[data-language]').trigger('click');
    await accountPreferences.flush();
    await second.get('[data-theme]').trigger('click');
    await accountPreferences.flush();
    expect(
      inputs.slice(1).map((input) => [input.operation, input.key]),
    ).toEqual([
      ['write', 'locale'],
      ['write', 'theme'],
    ]);
    expect(first.get('[data-theme]').text()).toBe('light');
    expect(accountPreferences.status().error).toBeNull();
  } finally {
    first?.unmount();
    second?.unmount();
    fetch.mockRestore();
    accountPreferences.disconnect();
  }
});

it('does not turn explicit document cancellation into preference failures while retaining real load errors', async () => {
  const { RequestCancelledError } = await import('../../resources/js/lib/http');
  let cancel = true;
  const store = createAccountPreferenceStore(guest(), async () => {
    if (cancel) throw new RequestCancelledError();
    throw new TypeError('Synthetic live network failure');
  });
  await expect(store.connect('A')).rejects.toBeInstanceOf(
    RequestCancelledError,
  );
  expect(store.status()).toEqual({ pending: false, error: null });
  cancel = false;
  await expect(store.connect('A')).rejects.toBeInstanceOf(TypeError);
  expect(store.status().error).toBe('load_failed');
  store.disconnect();
});

it('full reset uses its distinct queued operation and reloads authoritative defaults without changing legacy reset', async () => {
  const sent: Record<string, unknown>[] = [];
  let revision = 0;
  const store = createAccountPreferenceStore(guest(), async (input) => {
    sent.push(input);
    if (input.operation === 'snapshot')
      return snapshot('A', 'system', revision);
    revision++;
    return { data: { account: 'A', revision } };
  });
  await store.connect('A');
  await store.resetAll();
  await store.reset();
  expect(
    sent
      .filter((input) => input.operation !== 'snapshot')
      .map((input) => [input.operation, input.revision]),
  ).toEqual([
    ['reset_all', 0],
    ['reset', 1],
  ]);
  expect(sent.filter((input) => input.operation === 'snapshot')).toHaveLength(
    3,
  );
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('system');
});

it('shares revisions between display and delivery mutations and refreshes a failed write without replay', async () => {
  let revision = 3;
  let value = 'system';
  const inputs: Record<string, unknown>[] = [];
  const store = createAccountPreferenceStore(guest(), async (input) => {
    inputs.push(input);
    if (input.operation === 'snapshot') return snapshot('A', value, revision);
    expect(input.revision).toBe(revision);
    revision++;
    value = String(input.value);
    return {
      data: {
        account: 'A',
        revision,
        scope: 'runa:runa.auth:global:theme',
        version: 1,
        value,
      },
    };
  });
  await store.connect('A');
  store.store.write(scope, 'runa.theme', theme, 'dark');
  let deliveryRevision = 0;
  await store.mutate(async (current) => {
    deliveryRevision = current;
    revision++;
    return { data: { account: 'A', revision } };
  });
  expect(deliveryRevision).toBe(4);
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('dark');
  let attempts = 0;
  await expect(
    store.mutate(async () => {
      attempts++;
      revision++;
      value = 'light';
      throw new ApiError(409, {});
    }),
  ).rejects.toBeInstanceOf(ApiError);
  expect(attempts).toBe(1);
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('light');
  expect(inputs.filter((input) => input.operation === 'write')).toHaveLength(1);
  store.disconnect();
});

it('cancels already queued mutations after a conflict instead of rebasing old intents', async () => {
  let attempts = 0;
  const store = createAccountPreferenceStore(guest(), async (input) => {
    if (input.operation === 'snapshot') return snapshot('A', 'system', 4);
    attempts++;
    throw new ApiError(409, {});
  });
  await store.connect('A');
  store.store.write(scope, 'runa.theme', theme, 'dark');
  store.store.write(scope, 'runa.theme', theme, 'light');
  await store.flush();
  expect(attempts).toBe(1);
  expect(store.status()).toEqual({ pending: false, error: 'conflict' });
  store.disconnect();
});

it('same-account refresh retains cached values and fences a late snapshot after logout', async () => {
  let release!: (value: unknown) => void;
  let reads = 0;
  const store = createAccountPreferenceStore(guest(), async () => {
    if (reads++ === 0) return snapshot('A', 'dark', 3);
    return new Promise((done) => {
      release = done;
    });
  });
  await store.connect('A');
  const refreshing = store.connect('A');
  await Promise.resolve();
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('dark');
  store.disconnect();
  release(snapshot('A', 'light', 4));
  await refreshing;
  expect(store.currentAccount()).toBeNull();
  expect(store.store.read(scope, 'runa.theme', theme)).toBe('system');
});

it('a forbidden session refresh clears cached account preferences into a restricted hold', async () => {
  const { vi } = await import('vitest');
  const { accountPreferences } =
    await import('../../resources/js/lib/preferenceStore');
  const { request } = await import('../../resources/js/lib/http');
  const account = '00000000-0000-7000-8000-000000000001';
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/session')
      ? new Response(
          JSON.stringify({ error: { code: 'account_restricted' } }),
          { status: 403, headers: { 'Content-Type': 'application/json' } },
        )
      : new Response(JSON.stringify(snapshot(account, 'dark', 3)), {
          headers: { 'Content-Type': 'application/json' },
        }),
  );
  try {
    await accountPreferences.connect(account);
    expect(accountPreferences.store.read(scope, 'runa.theme', theme)).toBe(
      'dark',
    );
    await expect(request('/runa/auth/session')).rejects.toBeInstanceOf(
      ApiError,
    );
    expect(accountPreferences.store.read(scope, 'runa.theme', theme)).toBe(
      'system',
    );
    expect(accountPreferences.currentAccount()).toBe(account);
  } finally {
    accountPreferences.disconnect();
    fetch.mockRestore();
  }
});
