import assert from 'node:assert/strict';
import { recordBrowserProblems } from '../../../../src/runa/Testing/browserProblems.ts';
import { fileURLToPath } from 'node:url';
import { mkdir, readFile } from 'node:fs/promises';
import {
  chromium,
  firefox,
  expect,
} from '../../../../src/runa/Testing/playwright.ts';

process.chdir(fileURLToPath(new URL('../../../../src', import.meta.url)));

const base = 'http://nginx-test:8080';
const version = (await readFile('../VERSION', 'utf8')).trim();
const applicationVersion = JSON.parse(
  await readFile('app/application.json', 'utf8'),
).version;
const artifacts = '../.runtime/artifacts/playwright/foundation';
await mkdir(artifacts, { recursive: true });
const browser = await chromium.launch();
async function settled(page) {
  await expect(page.locator('.minimal-page')).toHaveAttribute(
    'aria-busy',
    'false',
  );
  await expect(page.locator('.loading-overlay')).toHaveCount(0);
}
async function language(page, locale) {
  if ((await page.locator('html').getAttribute('lang')) !== locale) {
    await page.getByRole('button', { name: /^(Język|Language):/ }).click();
    await settled(page);
  }
  await expect(page.locator('html')).toHaveAttribute('lang', locale);
}
async function theme(page, value) {
  const locale = await page.locator('html').getAttribute('lang');
  const labels =
    locale === 'pl'
      ? { light: 'Jasny', dark: 'Ciemny', system: 'Systemowy' }
      : { light: 'Light', dark: 'Dark', system: 'System' };
  const button = page.getByRole('button', { name: /^(Motyw|Theme):/ });
  for (let attempt = 0; attempt < 3; attempt++) {
    if (
      (await button.getAttribute('aria-label')).includes(`: ${labels[value]}.`)
    )
      return;
    await button.click();
    await settled(page);
  }
  assert.fail(`Theme control did not reach ${value}`);
}
try {
  const context = await browser.newContext();
  const page = await context.newPage();
  const errors = recordBrowserProblems(page, { anonymousSession: true });
  // Readiness is independent of the deliberately removed root status panel.
  assert.equal((await page.request.get(`${base}/api/health`)).ok(), true);
  await page.goto(base);
  await expect(
    page.getByRole('heading', { name: '.runa', exact: true }),
  ).toBeVisible();
  for (const [name, width, height] of [
    ['desktop', 1440, 900],
    ['tablet', 1024, 768],
    ['mobile', 390, 844],
  ]) {
    await page.setViewportSize({ width, height });
    for (const locale of ['pl', 'en']) {
      await language(page, locale);
      for (const value of ['light', 'dark']) {
        await theme(page, value);
        await expect(page.locator('html')).toHaveAttribute('data-theme', value);
        assert.equal(
          await page.evaluate(
            () =>
              globalThis.document.documentElement.scrollWidth <=
              globalThis.window.innerWidth,
          ),
          true,
        );
        await page.evaluate(() => globalThis.document.fonts.ready);
        assert.equal(
          await page.evaluate(() =>
            globalThis.document.fonts.check('16px "IBM Plex Mono"'),
          ),
          true,
        );
        await page.screenshot({
          path: `${artifacts}/${name}-${locale}-${value}.png`,
          fullPage: true,
        });
      }
    }
  }
  await page.reload();
  await expect(page.locator('html')).toHaveAttribute('lang', 'en');
  await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
  await theme(page, 'system');
  for (const colorScheme of ['light', 'dark']) {
    await page.emulateMedia({ colorScheme });
    await expect(page.locator('html')).toHaveAttribute(
      'data-theme',
      colorScheme,
    );
  }
  await page.evaluate(() => {
    globalThis.document.documentElement.style.fontSize = '200%';
  });
  assert.equal(
    await page.evaluate(
      () =>
        globalThis.document.documentElement.scrollWidth <=
        globalThis.window.innerWidth,
    ),
    true,
  );
  await page.screenshot({
    path: `${artifacts}/mobile-zoom.png`,
    fullPage: true,
  });
  await page.reload();
  await page.keyboard.press('Tab');
  await page.keyboard.press('Enter');
  await expect(page.locator('#main')).toBeFocused();
  const footer = page.getByRole('contentinfo');
  await expect(footer).toContainText(`Runa v${applicationVersion}`);
  await expect(footer).toContainText(`Powered by Runa v${version}`);
  await expect(page.getByRole('link', { name: 'GitHub' })).toHaveAttribute(
    'target',
    '_blank',
  );
  assert.deepEqual(errors, []);
  await context.close();

  const restricted = await browser.newContext({ reducedMotion: 'reduce' });
  await restricted.addInitScript(() => {
    globalThis.Storage.prototype.getItem = () => {
      throw new globalThis.DOMException('Blocked', 'SecurityError');
    };
    globalThis.Storage.prototype.setItem = () => {
      throw new globalThis.DOMException('Blocked', 'SecurityError');
    };
  });
  const restrictedPage = await restricted.newPage();
  await restrictedPage.goto(base);
  await language(restrictedPage, 'en');
  await theme(restrictedPage, 'dark');
  await expect(restrictedPage.locator('html')).toHaveAttribute(
    'data-theme',
    'dark',
  );
  await restricted.close();
} finally {
  await browser.close();
}
const secondary = await firefox.launch();
try {
  const page = await secondary.newPage();
  await page.goto(base);
  await expect(
    page.getByRole('heading', { name: '.runa', exact: true }),
  ).toBeVisible();
  await language(page, 'en');
  await theme(page, 'dark');
} finally {
  await secondary.close();
}
console.info(
  'PASS: independent API readiness, PL/EN, light/dark/system, three viewports, 200% text, keyboard skip, font loading, shared footer, preference persistence/storage denial, reduced motion, console/network and Firefox smoke.',
);
