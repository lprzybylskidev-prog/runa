export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly payload: unknown,
  ) {
    super(`API request failed (${status}).`);
    this.name = 'ApiError';
  }
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
): Promise<unknown> {
  if (!path.startsWith('/') || path.startsWith('//') || /[\\?#]/.test(path)) {
    throw new Error('Use a relative API resource path.');
  }
  const headers = new Headers(options.headers);
  headers.set('Accept', 'application/json');
  headers.set('X-Requested-With', 'XMLHttpRequest');
  const method = (options.method ?? 'GET').toUpperCase();
  const token = csrfToken();
  if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && token)
    headers.set('X-XSRF-TOKEN', token);
  const response = await fetch(`/api${path}`, {
    ...options,
    headers,
    credentials: 'same-origin',
  });
  const payload: unknown =
    response.status === 204 ||
    !response.headers.get('Content-Type')?.includes('application/json')
      ? null
      : await response.json();
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
