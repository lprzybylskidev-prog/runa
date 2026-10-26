type RequestEvent = {
  phase: 'start' | 'end';
  path: string;
  status?: number;
  payload?: unknown;
  context?: unknown;
};
const observers = new Set<(event: RequestEvent) => unknown>();
export function observeRequests(
  observer: (event: RequestEvent) => unknown,
): () => void {
  observers.add(observer);
  return () => observers.delete(observer);
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly payload: unknown,
  ) {
    super(`API request failed (${status}).`);
    this.name = 'ApiError';
  }
}

/** The document stopped observing a request; write completion may be unknown. Never replay it. */
export class RequestCancelledError extends Error {
  constructor() {
    super('Request cancelled by document navigation.');
    this.name = 'RequestCancelledError';
  }
}
let documentRequests = new AbortController();
let pendingRequests = 0;
function leavingDocument(event: BeforeUnloadEvent) {
  const departing = documentRequests;
  // Let synchronous dirty-form guards prevent navigation first.
  queueMicrotask(() => {
    if (!event.defaultPrevented) departing.abort();
  });
}
if (typeof window !== 'undefined') {
  window.addEventListener('pagehide', () => documentRequests.abort());
  window.addEventListener('pageshow', () => {
    if (documentRequests.signal.aborted)
      documentRequests = new AbortController();
  });
}
function documentSignal(): AbortSignal {
  // A canceled unload may leave the document alive. A new explicit request is valid.
  if (documentRequests.signal.aborted) documentRequests = new AbortController();
  return documentRequests.signal;
}

function csrfToken(): string | null {
  const cookie = document.cookie
    .split('; ')
    .find((value) => value.startsWith('XSRF-TOKEN='));
  if (!cookie) return null;
  try {
    return decodeURIComponent(cookie.slice('XSRF-TOKEN='.length));
  } catch {
    return null;
  }
}

// The response stays unknown until its caller validates the actual API contract.
export async function request(
  path: string,
  options: RequestInit = {},
  query: Readonly<
    Record<string, string | number | boolean | null | undefined>
  > = {},
): Promise<unknown> {
  if (!path.startsWith('/') || path.startsWith('//') || /[\\?#]/.test(path)) {
    throw new Error('Use a relative API resource path.');
  }
  const contexts = new Map(
    [...observers].map((observer) => [
      observer,
      observer({ phase: 'start', path }),
    ]),
  );
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');
  headers.set('X-Requested-With', 'XMLHttpRequest');
  const method = (options.method ?? 'GET').toUpperCase();
  const token = csrfToken();
  if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && token)
    headers.set('X-XSRF-TOKEN', token);
  const params = new URLSearchParams();
  for (const [key, value] of Object.entries(query)) {
    if (value !== null && value !== undefined)
      params.append(key, String(value));
  }
  const suffix = params.size ? `?${params}` : '';
  const navigationSignal = documentSignal();
  // Keep idle documents eligible for bfcache; only in-flight requests need this guard.
  if (pendingRequests++ === 0)
    window.addEventListener('beforeunload', leavingDocument);
  let response: Response;
  let payload: unknown;
  try {
    response = await fetch(`/api${path}${suffix}`, {
      ...options,
      signal: options.signal
        ? AbortSignal.any([options.signal, navigationSignal])
        : navigationSignal,
      headers,
      credentials: 'same-origin',
    });
    payload =
      response.status === 204 ||
      !response.headers.get('Content-Type')?.includes('application/json')
        ? null
        : await response.json();
  } catch (error) {
    if (navigationSignal.aborted) throw new RequestCancelledError();
    throw error;
  } finally {
    if (--pendingRequests === 0)
      window.removeEventListener('beforeunload', leavingDocument);
  }
  contexts.forEach((context, observer) =>
    observer({ phase: 'end', path, status: response.status, payload, context }),
  );
  if (!response.ok) throw new ApiError(response.status, payload);
  return payload;
}

export async function checkHealth(signal: AbortSignal): Promise<void> {
  const result = await request('/health', { signal });
  if (
    typeof result !== 'object' ||
    result === null ||
    !('status' in result) ||
    result.status !== 'ok'
  ) {
    throw new Error('Unexpected health response.');
  }
}
