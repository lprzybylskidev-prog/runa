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
