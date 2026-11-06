/* eslint-disable vue/one-component-per-file -- Independent application lifecycle and display-default fixtures. */
import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import { enableAutoUnmount, flushPromises } from '@runa/testing/vue';
import { defineComponent, h } from 'vue';
import { createApplication } from '../../../../src/runa/resources/js/runa/ui/createApplication';
import { loadApplicationPresentation } from '../../../../src/runa/resources/js/runa/ui/presentation';
import { useIdentity } from '../../../../src/runa/resources/js/runa/ui/identity';
import { request } from '../../../../src/runa/resources/js/lib/http';
enableAutoUnmount(afterEach);
const response = (data: unknown) =>
  new Response(JSON.stringify({ data }), {
    headers: { 'Content-Type': 'application/json' },
  });
it('projects public presentation only and rejects unsafe logo transport', async () => {
  vi.spyOn(window, 'fetch').mockResolvedValueOnce(
    response({
      name: 'Atlas',
      logo: { src: '/atlas.png', accessibleName: 'Atlas logo' },
      revision: 4,
      secret: 'hidden',
    }),
  );
  expect(await loadApplicationPresentation()).toEqual({
    name: 'Atlas',
    logo: { src: '/atlas.png', accessibleName: 'Atlas logo' },
    revision: 4,
    favicon: null,
  });
  vi.mocked(window.fetch).mockResolvedValueOnce(
    response({
      name: 'Atlas',
      logo: { src: 'javascript:alert(1)', accessibleName: 'Invalid' },
      revision: 5,
    }),
  );
  expect(await loadApplicationPresentation()).toBeUndefined();
});

it('coalesces overlapping presentation reads and reloads a publication that overtakes an in-flight read', async () => {
  const releases: Array<(value: Response) => void> = [];
  const fetch = vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    if (String(input).endsWith('/presentation'))
      return new Promise<Response>((resolve) => releases.push(resolve));
    return response({});
  });
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApplication(
    defineComponent({
      setup() {
        const identity = useIdentity();
        return () => h('p', identity.value.applicationName);
      },
    }),
  );
  try {
    app.mount(root);
    await flushPromises();
    window.dispatchEvent(new Event('focus'));
    document.dispatchEvent(new Event('visibilitychange'));
    window.dispatchEvent(new Event('focus'));
    await flushPromises();
    expect(
      fetch.mock.calls.filter(([input]) =>
        String(input).endsWith('/presentation'),
      ),
    ).toHaveLength(1);
    await request('/admin/operations/application.update', { method: 'POST' });
    releases.shift()!(response({ name: 'Obsolete', logo: null, revision: 1 }));
    await flushPromises();
    await vi.waitFor(() => expect(releases).toHaveLength(1));
    expect(root.textContent).not.toBe('Obsolete');
    releases.shift()!(response({ name: 'Current', logo: null, revision: 2 }));
    await flushPromises();
    await vi.waitFor(() => expect(root.textContent).toBe('Current'));
    expect(
      fetch.mock.calls.filter(([input]) =>
        String(input).endsWith('/presentation'),
      ),
    ).toHaveLength(2);
  } finally {
    app.unmount();
    root.remove();
  }
});
it('hydrates the shared shell and updates presentation after a current publication without changing canonical versions', async () => {
  let name = 'Atlas';
  vi.spyOn(window, 'fetch').mockImplementation(async (input) => {
    if (String(input).endsWith('/identity'))
      return response({
        applicationId: 'atlas',
        applicationName: 'Source name',
        applicationVersion: '2.4.1',
        platformVersion: '0.7.0',
      });
    if (String(input).endsWith('/presentation'))
      return response({ name, logo: null, revision: 2 });
    return response({ revision: 2 });
  });
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApplication(
    defineComponent({
      setup() {
        const identity = useIdentity();
        return () =>
          h(
            'p',
            identity.value.applicationName +
              ' ' +
              identity.value.applicationVersion,
          );
      },
    }),
  );
  try {
    app.mount(root);
    await flushPromises();
    await vi.waitFor(() => expect(root.textContent).toBe('Atlas 2.4.1'));
    name = 'Updated Atlas';
    await request('/admin/operations/application.update', { method: 'POST' });
    await flushPromises();
    await vi.waitFor(() =>
      expect(root.textContent).toBe('Updated Atlas 2.4.1'),
    );
  } finally {
    app.unmount();
    root.remove();
  }
});

it('hydrates application display defaults and favicon while retaining explicit user preferences', async () => {
  const { useLocale } =
    await import('../../../../src/runa/resources/js/composables/useLocale');
  const { useTheme } =
    await import('../../../../src/runa/resources/js/composables/useTheme');
  const { savePreference } =
    await import('../../../../src/runa/resources/js/lib/preferences');
  const { guestPreferenceStore } =
    await import('../../../../src/runa/modules/auth/frontend/preferences/session');
  const { formatApplicationInstant } = await import('@/runa/ui');
  const scope = {
    applicationId: 'runa',
    moduleKey: 'display',
    viewKey: 'global',
  };
  guestPreferenceStore.reset(scope, 'runa.locale');
  guestPreferenceStore.reset(scope, 'runa.theme');
  localStorage.removeItem('runa.locale');
  localStorage.removeItem('runa.theme');
  const favicon =
    '/api/presentation/assets/logo_icon/' + 'a'.repeat(64) + '.png';
  let timezone = 'America/New_York';
  vi.spyOn(window, 'fetch').mockImplementation(async (input) =>
    String(input).endsWith('/presentation')
      ? response({
          name: 'Atlas',
          logo: null,
          revision: 2,
          favicon,
          defaults: { locale: 'en', theme: 'dark', timezone },
        })
      : response({}),
  );
  const root = document.createElement('div');
  document.body.append(root);
  const app = createApplication(
    defineComponent({
      setup() {
        const { locale } = useLocale();
        const { theme } = useTheme();
        return () =>
          h(
            'p',
            `${locale.value}/${theme.value}/${formatApplicationInstant('2026-01-01T12:00:00Z', 'en')}`,
          );
      },
    }),
  );
  try {
    app.mount(root);
    await vi.waitFor(() => expect(root.textContent).toContain('en/dark/'));
    expect(root.textContent).toContain('07:00:00');
    expect(
      document
        .querySelector('link[rel="icon"][type="image/png"]')
        ?.getAttribute('href'),
    ).toBe(favicon);
    savePreference('runa.locale', 'pl');
    savePreference('runa.theme', 'light');
    timezone = 'UTC';
    await request('/admin/operations/application.update', { method: 'POST' });
    await vi.waitFor(() => expect(root.textContent).toContain('pl/light/'));
    await vi.waitFor(() => expect(root.textContent).toContain('12:00:00'));
  } finally {
    app.unmount();
    root.remove();
    guestPreferenceStore.reset(scope, 'runa.locale');
    guestPreferenceStore.reset(scope, 'runa.theme');
    localStorage.removeItem('runa.locale');
    localStorage.removeItem('runa.theme');
  }
});
