import { expect, it, vi } from '@runa/testing/vitest';
import { loadApplicationIdentity } from '../../../../src/runa/resources/js/runa/ui/loadIdentity';
import { defaultIdentity } from '../../../../src/runa/resources/js/runa/ui/identity';

it('loads the public runtime identity without exposing extra response fields', async () => {
  vi.spyOn(globalThis, 'fetch').mockResolvedValue(
    new Response(
      JSON.stringify({
        data: {
          applicationId: 'atlas',
          applicationName: 'Atlas',
          applicationVersion: '2.4.1',
          platformVersion: '0.3.0',
          logo: { src: '/brand.png', accessibleName: 'Atlas brand' },
          privatePath: '/never-public',
        },
      }),
      { headers: { 'Content-Type': 'application/json' } },
    ),
  );
  expect(await loadApplicationIdentity()).toEqual({
    applicationId: 'atlas',
    applicationName: 'Atlas',
    applicationVersion: '2.4.1',
    platformVersion: '0.3.0',
    logo: { src: '/brand.png', accessibleName: 'Atlas brand' },
  });
  expect(fetch).toHaveBeenCalledWith(
    '/api/identity',
    expect.objectContaining({ credentials: 'same-origin' }),
  );
});

it.each([null, { data: {} }, { data: { applicationId: 123 } }])(
  'retains the built identity on malformed runtime responses',
  async (value) => {
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(
      new Response(JSON.stringify(value), {
        headers: { 'Content-Type': 'application/json' },
      }),
    );
    expect(await loadApplicationIdentity()).toEqual(defaultIdentity);
  },
);

it('keeps the application usable if the identity endpoint is unavailable', async () => {
  vi.spyOn(globalThis, 'fetch').mockRejectedValue(new Error('Offline'));
  expect(await loadApplicationIdentity()).toEqual(defaultIdentity);
});
