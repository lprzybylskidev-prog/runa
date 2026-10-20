import { afterEach, expect, it, vi } from 'vitest';
import { ApiError, checkHealth, request } from '../../resources/js/lib/http';

afterEach(() => {
  document.cookie = 'XSRF-TOKEN=; Max-Age=0; path=/';
});

it('keeps writes on the API origin and forwards the existing CSRF cookie', async () => {
  document.cookie = 'XSRF-TOKEN=encoded%20token; path=/';
  const fetchMock = vi
    .spyOn(window, 'fetch')
    .mockResolvedValue(new Response(null, { status: 204 }));
  expect(await request('/preferences', { method: 'PATCH' })).toBeNull();
  const [url, options] = fetchMock.mock.calls[0]!;
  expect(url).toBe('/api/preferences');
  expect(options?.credentials).toBe('same-origin');
  expect(new Headers(options?.headers).get('X-XSRF-TOKEN')).toBe(
    'encoded token',
  );
});

it.each([
  'https://external.test',
  '//external.test',
  '/health?token=x',
  '/health#fragment',
])('rejects a non-resource path %s before fetching', async (path) => {
  const fetchMock = vi.spyOn(window, 'fetch');
  await expect(request(path)).rejects.toThrow(
    'Use a relative API resource path.',
  );
  expect(fetchMock).not.toHaveBeenCalled();
});

it('preserves API errors and rejects an invalid health payload', async () => {
  const fetchMock = vi.spyOn(window, 'fetch').mockResolvedValue(
    new Response('{"message":"Unavailable"}', {
      status: 503,
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  await expect(request('/health')).rejects.toBeInstanceOf(ApiError);
  fetchMock.mockResolvedValue(
    new Response('{"status":"unexpected"}', {
      headers: { 'Content-Type': 'application/json' },
    }),
  );
  await expect(checkHealth(new AbortController().signal)).rejects.toThrow(
    'Unexpected health response.',
  );
});
