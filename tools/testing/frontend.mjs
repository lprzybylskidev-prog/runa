import assert from 'node:assert/strict'
import { mkdir } from 'node:fs/promises'
import { chromium, firefox } from '@playwright/test'

const base = 'http://nginx-test:8080'
const artifacts = 'artifacts/playwright/foundation'
await mkdir(artifacts, { recursive: true })
const browser = await chromium.launch()
const errors = []
try {
  const context = await browser.newContext()
  const page = await context.newPage()
  page.on('pageerror', error => errors.push(error.message))
  page.on('console', message => { if (['error', 'warning'].includes(message.type())) errors.push(message.text()) })
  page.on('requestfailed', request => errors.push(request.url()))
  page.on('response', response => { if (response.status() >= 400) errors.push(`${response.status()} ${response.url()}`) })
  await page.goto(base)
  await page.getByRole('status').filter({ hasText: 'Połączenie działa' }).waitFor()
  for (const [name, width, height] of [['desktop', 1440, 900], ['tablet', 1024, 768], ['mobile', 390, 844]]) {
    await page.setViewportSize({ width, height })
    for (const locale of ['pl', 'en']) {
      await page.locator('select').nth(0).selectOption(locale)
      await page.getByRole('heading', { name: locale === 'pl' ? 'Witaj w Runie.' : 'Welcome to Runa.' }).waitFor()
      assert.equal(await page.locator('html').getAttribute('lang'), locale)
      for (const theme of ['light', 'dark']) {
        await page.locator('select').nth(1).selectOption(theme)
        assert.equal(await page.locator('html').getAttribute('data-theme'), theme)
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true)
        await page.screenshot({ path: `${artifacts}/${name}-${locale}-${theme}.png`, fullPage: true })
      }
    }
  }
  await page.reload()
  await page.getByRole('heading', { name: 'Welcome to Runa.' }).waitFor()
  assert.equal(await page.locator('html').getAttribute('data-theme'), 'dark')
  await page.locator('select').nth(1).selectOption('system')
  await page.emulateMedia({ colorScheme: 'light' })
  await page.waitForFunction(() => document.documentElement.dataset.theme === 'light')
  await page.emulateMedia({ colorScheme: 'dark' })
  await page.waitForFunction(() => document.documentElement.dataset.theme === 'dark')
  await page.evaluate(() => { document.documentElement.style.fontSize = '200%' })
  assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth), true)
  await page.screenshot({ path: `${artifacts}/mobile-zoom.png`, fullPage: true })
  await page.reload()
  await page.keyboard.press('Tab')
  await page.keyboard.press('Enter')
  assert.equal(await page.evaluate(() => document.activeElement?.id), 'main')
  assert.equal(await page.locator('form').evaluate(form => form.noValidate), true)
  assert.deepEqual(errors, [])
  await context.close()

  // Failure and retry are scoped to the connection status, without a global notification.
  const recovery = await browser.newContext()
  const retryPage = await recovery.newPage()
  await retryPage.route('**/api/health', route => route.fulfill({ status: 503, contentType: 'application/json', body: '{"message":"Unavailable"}' }))
  await retryPage.goto(base)
  await retryPage.getByRole('status').filter({ hasText: 'Nie udało się połączyć' }).waitFor()
  await retryPage.unroute('**/api/health')
  await retryPage.getByRole('button', { name: 'Spróbuj ponownie' }).click()
  await retryPage.getByRole('status').filter({ hasText: 'Połączenie działa' }).waitFor()
  await recovery.close()

  const restricted = await browser.newContext()
  await restricted.addInitScript(() => {
    Storage.prototype.getItem = () => { throw new DOMException('Blocked', 'SecurityError') }
    Storage.prototype.setItem = () => { throw new DOMException('Blocked', 'SecurityError') }
  })
  const restrictedPage = await restricted.newPage()
  await restrictedPage.goto(base)
  await restrictedPage.locator('select').nth(0).selectOption('en')
  await restrictedPage.getByRole('heading', { name: 'Welcome to Runa.' }).waitFor()
  await restricted.close()
} finally {
  await browser.close()
}
const secondary = await firefox.launch()
try {
  const page = await secondary.newPage()
  await page.goto(base)
  await page.getByRole('status').filter({ hasText: 'Połączenie działa' }).waitFor()
} finally {
  await secondary.close()
}
console.info('PASS: PL/EN, light/dark/system, three viewports, 200% text, keyboard skip, preference persistence/storage denial, retry, console/network and Firefox smoke.')
