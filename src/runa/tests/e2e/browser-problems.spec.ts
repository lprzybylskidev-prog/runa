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

test('browser diagnostics distinguish cancelled requests from network failures', async ({
  page,
}) => {
  await page.goto('/__fixtures/ready');
  const errors = recordBrowserProblems(page);
  let release!: () => void;
  let finished!: () => void;
  const paused = new Promise<void>((resolve) => {
    release = resolve;
  });
  const routed = new Promise<void>((resolve) => {
    finished = resolve;
  });
  await page.route('**/__diagnostics/cancel', async (route) => {
    await paused;
    await route.fulfill({ status: 204 });
    finished();
  });
  const controller = await page.evaluateHandle(() => new AbortController());
  const seen = page.waitForRequest((request) =>
    request.url().endsWith('/__diagnostics/cancel'),
  );
  const cancelled = page.waitForEvent('requestfailed', (request) =>
    request.url().endsWith('/__diagnostics/cancel'),
  );
  const pending = page.evaluate(
    (controller) =>
      fetch('/__diagnostics/cancel', { signal: controller.signal }).catch(
        () => undefined,
      ),
    controller,
  );
  await seen;
  await controller.evaluate((controller) => controller.abort());
  await pending;
  await cancelled;
  release();
  await routed;
  expect(errors).toEqual([]);
  await page.route('**/__diagnostics/refused', (route) =>
    route.abort('connectionrefused'),
  );
  await page.evaluate(() =>
    fetch('/__diagnostics/refused').catch(() => undefined),
  );
  await expect
    .poll(() =>
      errors.some((error) => error.includes('/__diagnostics/refused')),
    )
    .toBe(true);
});
