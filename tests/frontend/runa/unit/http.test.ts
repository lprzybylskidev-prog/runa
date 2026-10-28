import { afterEach, expect, it, vi } from '@runa/testing/vitest';
import {
  ApiError,
  checkHealth,
  request,
} from '../../../../src/runa/resources/js/lib/http';

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

it('cancels departing-document reads/writes without claiming success or replaying and permits later explicit requests', async () => {
  const { RequestCancelledError } =
    await import('../../../../src/runa/resources/js/lib/http');
  const fetchMock = vi.spyOn(window, 'fetch').mockImplementation(
    (_url, options) =>
      new Promise((_resolve, reject) => {
        options?.signal?.addEventListener(
          'abort',
          () => reject(new DOMException('Canceled', 'AbortError')),
          { once: true },
        );
      }),
  );
  const read = request('/navigation-read');
  const write = request('/navigation-write', { method: 'POST', body: '{}' });
  const outcomes = Promise.allSettled([read, write]);
  window.dispatchEvent(new Event('beforeunload', { cancelable: true }));
  const results = await outcomes;
  for (const result of results) {
    expect(result.status).toBe('rejected');
    if (result.status === 'rejected')
      expect(result.reason).toBeInstanceOf(RequestCancelledError);
  }
  expect(fetchMock).toHaveBeenCalledTimes(2);
  fetchMock.mockResolvedValue(new Response(null, { status: 204 }));
  await expect(request('/new-explicit-request')).resolves.toBeNull();
  expect(fetchMock).toHaveBeenCalledTimes(3);
});

it('keeps prevented unload requests alive and preserves ordinary network failure', async () => {
  const { RequestCancelledError } =
    await import('../../../../src/runa/resources/js/lib/http');
  let signal: AbortSignal | null | undefined;
  const networkError = new TypeError('Synthetic network failure');
  let reject!: (reason: unknown) => void;
  vi.spyOn(window, 'fetch').mockImplementation((_url, options) => {
    signal = options?.signal;
    return new Promise((_done, fail) => {
      reject = fail;
    });
  });
  const pending = request('/prevented-navigation');
  const handled = pending.catch((error) => error);
  const prevent = (event: Event) => event.preventDefault();
  window.addEventListener('beforeunload', prevent, { once: true });
  window.dispatchEvent(new Event('beforeunload', { cancelable: true }));
  await Promise.resolve();
  expect(signal?.aborted).toBe(false);
  reject(networkError);
  expect(await handled).toBe(networkError);
  expect(await handled).not.toBeInstanceOf(RequestCancelledError);
});
