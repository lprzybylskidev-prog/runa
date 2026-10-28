// Snapshot the actual MinimalLayout brand tile; preserve the supplied raster mark.
import { createRequire } from 'node:module';
const require = createRequire(
  new URL('../../../src/package.json', import.meta.url),
);
const { chromium } = require('@playwright/test');
import { writeFile } from 'node:fs/promises';

const sizes = [16, 32, 48];
const browser = await chromium.launch();
try {
  const page = await browser.newPage({ deviceScaleFactor: 1 });
  await page.emulateMedia({ colorScheme: 'light', reducedMotion: 'reduce' });
  await page.goto('http://nginx-test:8080/');
  await page.locator('.brand-mark').first().waitFor();
  await page.waitForFunction(() =>
    [...document.styleSheets].some((sheet) => sheet.href?.includes('/build/')),
  );
  await page.evaluate(async () => {
    // Load the exact CSS mask source before taking the browser snapshot.
    const tile = document.querySelector('.brand-mark');
    const mark = tile.querySelector('.runa-logo');
    const source =
      getComputedStyle(mark).maskImage.match(/^url\("?(.*?)"?\)$/)[1];
    const image = new Image();
    image.src = source;
    await image.decode();
    document.documentElement.dataset.theme = 'light';
    document.body.replaceChildren(tile.cloneNode(true));
    document.documentElement.style.background = 'transparent';
    document.body.style.cssText = 'margin:0;background:transparent';
  });
  const frames = [];
  for (const size of sizes) {
    await page.evaluate((size) => {
      const tile = document.querySelector('.brand-mark');
      tile.style.transformOrigin = 'top left';
      tile.style.transform = `scale(${size / 42})`;
    }, size);
    frames.push(
      await page.screenshot({
        clip: { x: 0, y: 0, width: size, height: size },
        omitBackground: true,
        animations: 'disabled',
      }),
    );
  }
  // ICO directory points to lossless PNG frames; no image package or tracing.
  const directory = Buffer.alloc(6 + 16 * frames.length);
  directory.writeUInt16LE(1, 2);
  directory.writeUInt16LE(frames.length, 4);
  let offset = directory.length;
  frames.forEach((frame, index) => {
    const entry = 6 + 16 * index;
    directory[entry] = sizes[index];
    directory[entry + 1] = sizes[index];
    directory.writeUInt16LE(1, entry + 4);
    directory.writeUInt16LE(32, entry + 6);
    directory.writeUInt32LE(frame.length, entry + 8);
    directory.writeUInt32LE(offset, entry + 12);
    offset += frame.length;
  });
  await writeFile('public/favicon.ico', Buffer.concat([directory, ...frames]));
} finally {
  await browser.close();
}
