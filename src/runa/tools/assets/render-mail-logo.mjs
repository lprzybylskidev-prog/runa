// Snapshot the actual Vue brand tile for email engines without CSS mask support.
import { chromium } from '@playwright/test';

const browser = await chromium.launch();
try {
  const page = await browser.newPage({ deviceScaleFactor: 3 });
  await page.emulateMedia({ colorScheme: 'light', reducedMotion: 'reduce' });
  await page.goto('http://nginx-test:8080/');
  const tile = page.locator('.brand-mark').first();
  await tile.waitFor();
  await page.evaluate(() => {
    const mark = document.querySelector('.brand-mark').cloneNode(true);
    document.body.replaceChildren(mark);
    document.documentElement.style.background = 'transparent';
    document.body.style.cssText = 'margin:0;background:transparent';
  });
  await page.locator('.brand-mark').screenshot({
    path: 'runa/resources/images/runa-mail-logo.png',
    omitBackground: true,
    animations: 'disabled',
  });
} finally {
  await browser.close();
}
