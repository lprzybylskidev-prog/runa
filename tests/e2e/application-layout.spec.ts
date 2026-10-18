import { expect, test } from '@playwright/test';

for (const [device, width, height] of [
  ['desktop', 1440, 900],
  ['tablet', 1024, 768],
  ['mobile', 390, 844],
] as const) {
  for (const locale of ['pl', 'en'] as const)
    for (const theme of ['light', 'dark'] as const) {
      test(`application: ${device} ${locale} ${theme}`, async ({
        page,
      }, info) => {
        test.skip(
          info.project.name !== 'chromium',
          'Exact viewport matrix; interaction tests run across projects.',
        );
        await page.setViewportSize({ width, height });
        await page.addInitScript(
          ({ locale, theme }) => {
            localStorage.setItem('runa.locale', locale);
            localStorage.setItem('runa.theme', theme);
          },
          { locale, theme },
        );
        const errors: string[] = [];
        page.on('pageerror', (error) => errors.push(error.message));
        page.on('console', (message) => {
          if (['warning', 'error'].includes(message.type()))
            errors.push(message.text());
        });
        page.on('requestfailed', (request) => errors.push(request.url()));
        page.on('response', (response) => {
          if (response.status() >= 400)
            errors.push(`${response.status()} ${response.url()}`);
        });
        await page.goto('/__review/application/overview');
        await expect(page.getByRole('heading', { level: 1 })).toHaveText(
          locale === 'pl' ? 'Przegląd' : 'Overview',
        );
        await expect(page.getByRole('contentinfo')).toContainText(
          locale === 'pl' ? 'Oparte na Runa' : 'Powered by Runa',
        );
        await page.evaluate(() => document.fonts.ready);
        expect(
          await page.evaluate(
            () => document.documentElement.scrollWidth <= innerWidth,
          ),
        ).toBe(true);
        await page.screenshot({
          path: `artifacts/playwright/ui-foundations/application-${device}-${locale}-${theme}.png`,
          fullPage: true,
        });
        if (width < 1024) {
          await page
            .getByRole('button', {
              name: locale === 'pl' ? 'Otwórz nawigację' : 'Open navigation',
            })
            .click();
          await expect(page.getByRole('dialog')).toBeVisible();
          await page.screenshot({
            path: `artifacts/playwright/ui-foundations/application-drawer-${locale}-${theme}.png`,
            fullPage: true,
          });
          await page.keyboard.press('Escape');
        }
        await page.emulateMedia({ media: 'print' });
        await expect(page.locator('.shell-header')).toBeHidden();
        await expect(page.getByRole('contentinfo')).toBeHidden();
        await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
        expect(errors).toEqual([]);
      });
    }
}

test('known URLs reconstruct module/area on direct load, history and refresh', async ({
  page,
  request,
}) => {
  await page.goto('/__review/application/activity');
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Aktywność');
  const top = page.getByRole('navigation', { name: 'Obszary modułu' });
  await expect(
    top.getByRole('link', { name: 'Aktywność', exact: true }),
  ).toHaveAttribute('aria-current', 'page');
  await top.getByRole('link', { name: 'Projekty', exact: true }).click();
  await expect(page).toHaveURL(/\/projects$/);
  await page.goBack();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Aktywność');
  await page.goForward();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Projekty');
  await page.reload();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Projekty');
  await top.getByRole('button', { name: 'Więcej' }).click();
  await page
    .getByRole('region', { name: 'Więcej', exact: true })
    .getByRole('link', { name: 'Archiwum', exact: true })
    .focus();
  await page.keyboard.press('Escape');
  await expect(top.getByRole('button', { name: 'Więcej' })).toBeFocused();
  await page.keyboard.press('Enter');
  await page
    .getByRole('region', { name: 'Więcej', exact: true })
    .getByRole('link', { name: 'Archiwum', exact: true })
    .click();
  await expect(page).toHaveURL(/\/archive$/);
  await page.reload();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Archiwum');
  expect((await request.get('/__review/application/unknown')).status()).toBe(
    404,
  );
});

test('sidebar collapse/groups persist, header stays visible and sidebar scroll is independent', async ({
  page,
}, info) => {
  test.skip(
    info.project.name === 'mobile-chromium',
    'Desktop sidebar interaction.',
  );
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/__review/application/overview');
  const modules = page.getByRole('navigation', { name: 'Moduły', exact: true });
  await modules.getByRole('button', { name: 'Projekty', exact: true }).click();
  await expect(
    modules.getByRole('link', { name: 'Aktywność', exact: true }),
  ).toBeVisible();
  await page.reload();
  await expect(
    modules.getByRole('link', { name: 'Aktywność', exact: true }),
  ).toBeVisible();
  await page.getByRole('button', { name: 'Zwiń menu', exact: true }).click();
  await expect(page.locator('.application-page')).toHaveClass(
    /sidebar-collapsed/,
  );
  await expect
    .poll(() =>
      page
        .locator('.shell-sidebar')
        .evaluate((element) => element.getBoundingClientRect().width),
    )
    .toBe(88);
  await modules.getByRole('link', { name: 'Przegląd', exact: true }).hover();
  const tooltip = page.getByRole('tooltip').filter({ hasText: 'Przegląd' });
  await expect(tooltip).toBeVisible();
  expect((await tooltip.boundingBox())!.x).toBeGreaterThan(80);
  await page.reload();
  await expect(page.locator('.application-page')).toHaveClass(
    /sidebar-collapsed/,
  );
  const independent = await page
    .locator('.shell-sidebar-scroll')
    .evaluate((element) => {
      const content = element.querySelector('nav')!;
      for (let i = 0; i < 30; i++) {
        const row = document.createElement('p');
        row.textContent = `Additional navigation ${i}`;
        row.style.height = '44px';
        content.append(row);
      }
      const before = window.scrollY;
      element.scrollTop = 300;
      return element.scrollTop > 0 && window.scrollY === before;
    });
  expect(independent).toBe(true);
  await page.getByRole('button', { name: 'Długa treść', exact: true }).click();
  await page.evaluate(() => scrollTo(0, 650));
  expect((await page.locator('.shell-sidebar').boundingBox())!.y).toBe(0);
  expect((await page.locator('.shell-header').boundingBox())!.y).toBe(0);
  expect((await page.locator('.shell-toolbar').boundingBox())!.y).toBeLessThan(
    0,
  );
  expect(
    await page
      .locator('.shell-sidebar-scroll')
      .evaluate((element) => getComputedStyle(element).overflowY),
  ).toBe('auto');
});

test('mobile drawer traps focus, closes with Escape/outside and restores trigger focus', async ({
  page,
}) => {
  await page.setViewportSize({ width: 390, height: 844 });
  await page.goto('/__review/application/overview');
  const trigger = page.getByRole('button', { name: 'Otwórz nawigację' });
  await trigger.click();
  const dialog = page.getByRole('dialog', { name: 'Moduły' });
  await expect(dialog).toBeVisible();
  await expect(
    page.getByRole('button', { name: 'Zamknij nawigację' }).last(),
  ).toBeFocused();
  await page.keyboard.press('Shift+Tab');
  await expect(
    dialog.getByRole('link', { name: 'Atlas', exact: true }),
  ).toBeFocused();
  await page.keyboard.press('Shift+Tab');
  await expect(
    dialog.getByRole('link', { name: 'Obszar testowy 16', exact: true }),
  ).toBeFocused();
  await page.keyboard.press('Tab');
  await expect(
    dialog.getByRole('link', { name: 'Atlas', exact: true }),
  ).toBeFocused();
  await page.keyboard.press('Escape');
  await expect(trigger).toBeFocused();
  await expect(dialog).toHaveCount(0);
  await trigger.click();
  await page
    .locator('.shell-drawer-backdrop')
    .click({ position: { x: 370, y: 200 } });
  await expect(trigger).toBeFocused();
  await trigger.click();
  await dialog.getByRole('link', { name: 'Zespół', exact: true }).click();
  await expect(page).toHaveURL(/\/team$/);
  await expect(page.getByRole('main')).toBeFocused();
  await expect(trigger).toHaveAttribute('aria-expanded', 'false');
});

test('optional regions and navigations work independently; avatar dismisses', async ({
  page,
}) => {
  await page.goto('/__review/application/overview');
  const avatar = page.getByRole('button', {
    name: 'Menu użytkownika: Anna Kowalska',
  });
  await avatar.click();
  await expect(
    page.getByText('Opcje konta są obecnie niedostępne.'),
  ).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(avatar).toBeFocused();
  for (const name of [
    'Menu boczne',
    'Menu górne',
    'Statystyki',
    'Pasek narzędzi',
    'Panel dodatkowy',
  ])
    await page.getByRole('button', { name, exact: true }).click();
  await expect(page.locator('.shell-sidebar')).toHaveCount(0);
  await expect(page.locator('.shell-top-nav')).toHaveCount(0);
  await expect(page.locator('.shell-statistics')).toHaveCount(0);
  await expect(page.locator('.shell-toolbar')).toHaveCount(0);
  await expect(page.locator('.shell-supplementary')).toHaveCount(0);
  await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
  await page
    .getByRole('combobox', { name: 'Szerokość obszaru' })
    .selectOption('fluid');
  await expect(page.locator('.application-page')).toHaveClass(
    /workspace-fluid/,
  );
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth,
    ),
  ).toBe(true);
  await page.getByRole('button', { name: 'Menu boczne', exact: true }).click();
  await expect(page.locator('.shell-sidebar')).toHaveCount(1);
  await expect(page.locator('.shell-top-nav')).toHaveCount(0);
});

test('denied browser storage does not block navigation or preferences', async ({
  page,
}) => {
  await page.addInitScript(() => {
    Object.defineProperty(window, 'localStorage', {
      get() {
        throw new Error('Denied');
      },
    });
  });
  await page.goto('/__review/application/overview');
  await page.getByRole('button', { name: /^Język:/ }).click();
  await expect(page.getByRole('button', { name: /^Language:/ })).toBeFocused();
  await expect(page.getByRole('heading', { level: 1 })).toHaveText('Overview');
  await page
    .getByRole('navigation', { name: 'Module areas' })
    .getByRole('link', { name: 'Overview', exact: true })
    .click();
  await expect(page.getByRole('main')).toBeFocused();
});

test('top navigation remains reachable through horizontal overflow on a narrow screen', async ({
  page,
}) => {
  await page.setViewportSize({ width: 320, height: 844 });
  await page.goto('/__review/application/activity');
  const nav = page.locator('.shell-top-nav');
  expect(
    await nav.evaluate((element) => element.scrollWidth > element.clientWidth),
  ).toBe(true);
  await nav.getByRole('button', { name: 'Więcej' }).focus();
  await page.keyboard.press('Enter');
  await page
    .getByRole('region', { name: 'Więcej', exact: true })
    .getByRole('link', { name: 'Archiwum', exact: true })
    .click();
  await expect(page).toHaveURL(/\/archive$/);
  expect(
    await page.evaluate(
      () => document.documentElement.scrollWidth <= innerWidth,
    ),
  ).toBe(true);
});

test('both navigation scrollbars use theme violet and long-menu fixtures can be toggled', async ({
  page,
}) => {
  await page.setViewportSize({ width: 1024, height: 768 });
  await page.goto('/__review/application/projects');
  const sidebar = page.locator('.shell-sidebar-scroll');
  const top = page.locator('.shell-top-nav');
  expect(
    await sidebar.evaluate(
      (element) => element.scrollHeight > element.clientHeight,
    ),
  ).toBe(true);
  expect(
    await top.evaluate((element) => element.scrollWidth > element.clientWidth),
  ).toBe(true);
  // Headless Firefox resolves even an isolated inline `thin` declaration to `none`.
  // Compare against this browser's native resolution rather than assuming the host's widget metrics.
  const nativeThin = await page.evaluate(() => {
    const probe = document.createElement('div');
    probe.style.scrollbarWidth = 'thin';
    document.body.append(probe);
    const width = getComputedStyle(probe).scrollbarWidth;
    probe.remove();
    return width;
  });
  for (const theme of ['light', 'dark']) {
    await page.evaluate((theme) => {
      document.documentElement.dataset.theme = theme;
    }, theme);
    for (const locator of [sidebar, top]) {
      const styles = await locator.evaluate((element) => {
        const style = getComputedStyle(element);
        return { color: style.scrollbarColor, width: style.scrollbarWidth };
      });
      expect(styles.width).toBe(nativeThin);
      expect(styles.color).toContain(
        theme === 'light' ? '134, 80, 186' : '183, 132, 223',
      );
    }
  }
  await page.getByRole('button', { name: 'Długie menu', exact: true }).click();
  await expect(
    page.getByRole('link', { name: 'Obszar testowy 01', exact: true }),
  ).toHaveCount(0);
});

test('compact top navigation keeps its scrollbar at the header bottom and owns wheel scrolling', async ({
  page,
}) => {
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 844 });
    await page.goto('/__review/application/projects');
    const header = page.locator('.shell-header');
    const nav = page.locator('.shell-top-nav');
    const headerBox = (await header.boundingBox())!;
    const navBox = (await nav.boundingBox())!;
    expect(headerBox.height).toBe(64);
    expect(navBox.y + navBox.height).toBeCloseTo(
      headerBox.y + headerBox.height - 1,
      0,
    );
    await nav.hover();
    const before = await page.evaluate(() => scrollY);
    await page.mouse.wheel(0, 250);
    await expect
      .poll(() => nav.evaluate((element) => element.scrollLeft))
      .toBeGreaterThan(0);
    expect(await page.evaluate(() => scrollY)).toBe(before);
    await nav.evaluate((element) => {
      element.scrollLeft = element.scrollWidth;
    });
    await page.mouse.wheel(0, 250);
    await page.waitForTimeout(100);
    expect(await page.evaluate(() => scrollY)).toBe(before);
    await page.mouse.wheel(0, -200);
    await expect
      .poll(() =>
        nav.evaluate(
          (element) =>
            element.scrollLeft < element.scrollWidth - element.clientWidth,
        ),
      )
      .toBe(true);
    expect(await page.evaluate(() => scrollY)).toBe(before);
    await page.screenshot({
      path: `artifacts/playwright/ui-foundations/top-navigation-compact-${width}.png`,
      fullPage: true,
    });
  }
});

test('top dropdown floats outside the scrolling nav without changing header size', async ({
  page,
}) => {
  for (const width of [1440, 390]) {
    await page.setViewportSize({ width, height: 844 });
    await page.goto('/__review/application/projects');
    const nav = page.locator('.shell-top-nav');
    const trigger = nav.getByRole('button', { name: 'Więcej' });
    await trigger.click();
    const panel = page.getByRole('region', { name: 'Więcej', exact: true });
    await expect(panel).toBeVisible();
    const card = (await panel.boundingBox())!;
    const header = (await page.locator('.shell-header').boundingBox())!;
    expect(header.height).toBe(64);
    expect(card.y).toBeGreaterThanOrEqual(header.y + header.height);
    expect(card.x).toBeGreaterThanOrEqual(8);
    expect(card.x + card.width).toBeLessThanOrEqual(width - 8);
    expect(
      await panel.evaluate(
        (element) => element.closest('.shell-top-nav') === null,
      ),
    ).toBe(true);
    await expect(
      panel.getByRole('link', { name: 'Archiwum', exact: true }),
    ).toBeFocused();
    await page.screenshot({
      path: `artifacts/playwright/ui-foundations/top-dropdown-${width}.png`,
    });
    await page.keyboard.press('Shift+Tab');
    await expect(trigger).toBeFocused();
    await expect(panel).toHaveCount(0);
    await trigger.click();
    await page.keyboard.press('Tab');
    await expect(panel).toHaveCount(0);
    await expect(
      nav.getByRole('link', { name: 'Obszar testowy 01', exact: true }),
    ).toBeFocused();
    await trigger.click();
    await page.getByRole('heading', { level: 1 }).click();
    await expect(panel).toHaveCount(0);
    await trigger.click();
    await page.evaluate(() => scrollTo(0, 150));
    await expect(panel).toHaveCount(0);
  }
});

test('sidebar groups are whole-row disclosures, brand aligns with the sticky header and collapse control precedes navigation', async ({
  page,
}) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/__review/application/overview');
  const group = page
    .getByRole('navigation', { name: 'Moduły', exact: true })
    .getByRole('button', { name: 'Projekty', exact: true });
  const original = page.url();
  await group.click();
  await expect(group).toHaveAttribute('aria-expanded', 'true');
  expect(page.url()).toBe(original);
  await expect(
    page
      .getByRole('navigation', { name: 'Moduły', exact: true })
      .getByRole('link', { name: 'Aktywność', exact: true }),
  ).toBeVisible();
  expect((await page.locator('.shell-brand-row').boundingBox())!.height).toBe(
    64,
  );
  expect(
    (await page.locator('.shell-sidebar .brand-mark').boundingBox())!.width,
  ).toBe(42);
  const toggle = page.getByRole('button', { name: 'Zwiń menu', exact: true });
  expect(
    await toggle.evaluate(
      (element) => !!element.closest('.shell-header-start'),
    ),
  ).toBe(true);
  expect((await toggle.boundingBox())!.x).toBeLessThan(
    (await page.locator('.shell-top-nav').boundingBox())!.x,
  );
  await toggle.click();
  await expect(page.locator('.application-page')).toHaveClass(
    /sidebar-collapsed/,
  );
  await group.click();
  await expect(page.locator('.application-page')).not.toHaveClass(
    /sidebar-collapsed/,
  );
  await expect(group).toHaveAttribute('aria-expanded', 'true');
});

test('shell motion respects reduced motion and survives interrupted disclosures', async ({
  page,
}) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  await page.goto('/__review/application/overview');
  const group = page.locator('.shell-sidebar-disclosure').first();
  // Interrupt both directions before the 160ms expansion has finished.
  await group.evaluate((element: HTMLButtonElement) => element.click());
  await page.waitForTimeout(50);
  await group.evaluate((element: HTMLButtonElement) => element.click());
  await page.waitForTimeout(30);
  await group.evaluate((element: HTMLButtonElement) => element.click());
  await expect(group).toHaveAttribute('aria-expanded', 'true');
  const expansion = page.locator('.shell-expansion').first();
  await expect(expansion).not.toHaveClass(/shell-expand-/);
  expect((await expansion.boundingBox())!.height).toBeGreaterThan(44);
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await group.click();
  await expect(expansion).toBeHidden();
  const toggle = page.locator('.shell-sidebar-toggle');
  await toggle.click();
  await expect
    .poll(() =>
      page
        .locator('.shell-sidebar')
        .evaluate((element) => element.getBoundingClientRect().width),
    )
    .toBe(88);
  expect(
    await page
      .locator('.shell-sidebar')
      .evaluate((element) => getComputedStyle(element).transitionDuration),
  ).toBe('0s');
  const avatar = page.getByRole('button', {
    name: /^(Menu użytkownika|User menu):/,
  });
  await avatar.click();
  await expect(page.locator('.shell-profile-panel')).toBeVisible();
  expect(
    await page
      .locator('.shell-profile-panel')
      .evaluate((element) => getComputedStyle(element).transitionDuration),
  ).toBe('0s');
  await page.keyboard.press('Escape');
  await expect(page.locator('.shell-profile-panel')).toHaveCount(0);
  await expect(avatar).toBeFocused();
});
