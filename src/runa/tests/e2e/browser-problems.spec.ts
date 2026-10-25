import { expect, test } from '@playwright/test';
import { recordBrowserProblems } from './support/browserProblems';

test('guest diagnostics accept only same-origin session 401 and retain other HTTP failures', async ({
  page,
}) => {
  await page.goto('/__fixtures/ready');
  await page.route('**/api/runa/auth/**', (route) =>
    route.fulfill({
      status: new URL(route.request().url()).searchParams.has('unavailable')
        ? 503
        : 401,
      contentType: 'application/json',
      body: JSON.stringify({ error: { code: 'authentication_required' } }),
    }),
  );
  const guest = recordBrowserProblems(page, { anonymousSession: true });
  const strict = recordBrowserProblems(page);
  await page.evaluate(() => fetch('/api/runa/auth/session'));
  await expect
    .poll(() => strict.some((error) => error.startsWith('401 ')))
    .toBe(true);
  expect(guest).toEqual([]);
  await page.evaluate(() => fetch('/api/runa/auth/profile'));
  await expect
    .poll(() =>
      guest.some(
        (error) => error.startsWith('401 ') && error.endsWith('/profile'),
      ),
    )
    .toBe(true);
  await page.evaluate(() => fetch('/api/runa/auth/session?unavailable=1'));
  await expect
    .poll(() => guest.some((error) => error.startsWith('503 ')))
    .toBe(true);
});
