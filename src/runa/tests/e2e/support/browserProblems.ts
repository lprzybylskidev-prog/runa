import type { Page } from '@playwright/test';

/** Guest presentation intentionally probes this single endpoint for session presence. */
export function recordBrowserProblems(
  page: Page,
  options: { anonymousSession?: boolean } = {},
): string[] {
  const errors: string[] = [];
  function guestSession(url: string): boolean {
    if (!options.anonymousSession) return false;
    try {
      const target = new URL(url);
      return (
        target.origin === new URL(page.url()).origin &&
        target.pathname === '/api/runa/auth/session'
      );
    } catch {
      return false;
    }
  }
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (
      message.type() === 'error' &&
      /^Failed to load resource:.*\b401\b/.test(message.text()) &&
      guestSession(message.location().url)
    )
      return;
    if (['warning', 'error'].includes(message.type()))
      errors.push(message.text());
  });
  page.on('requestfailed', (request) => {
    if (request.failure()?.errorText !== 'net::ERR_ABORTED')
      errors.push(`${request.url()}: ${request.failure()?.errorText}`);
  });
  page.on('response', (response) => {
    if (response.status() === 401 && guestSession(response.url())) return;
    if (response.status() >= 400)
      errors.push(`${response.status()} ${response.url()}`);
  });
  return errors;
}
