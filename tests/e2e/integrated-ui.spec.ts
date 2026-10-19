import { expect, test } from '@playwright/test';

const categories = [
  'controls',
  'feedback',
  'containers',
  'loading',
  'forms',
  'additional',
  'selection',
  'advanced',
  'dialogs',
  'data',
];
function recordProblems(page: import('@playwright/test').Page) {
  const errors: string[] = [];
  page.on('pageerror', (error) => errors.push(error.message));
  page.on('console', (message) => {
    if (['warning', 'error'].includes(message.type()))
      errors.push(message.text());
  });
  page.on('requestfailed', (request) => {
    if (request.failure()?.errorText !== 'net::ERR_ABORTED')
      errors.push(`${request.url()}: ${request.failure()?.errorText}`);
  });
  page.on('response', (response) => {
    if (response.status() >= 400)
      errors.push(`${response.status()} ${response.url()}`);
  });
  return errors;
}
for (const locale of ['pl', 'en'])
  for (const theme of ['light', 'dark']) {
    test(`integrated scenario semantics and 200% reflow ${locale} ${theme}`, async ({
      browser,
    }) => {
      test.setTimeout(90000);
      const errors: string[] = [];
      for (const scale of [1, 2]) {
        // 1440x900 physical pixels at 200%: 720x450 CSS pixels with DPR 2.
        const context = await browser.newContext({
          viewport: { width: 1440 / scale, height: 900 / scale },
          deviceScaleFactor: scale,
        });
        const page = await context.newPage();
        const pageErrors = recordProblems(page);
        await page.addInitScript(
          ({ locale, theme }) => {
            localStorage.setItem('runa.locale', locale);
            localStorage.setItem('runa.theme', theme);
          },
          { locale, theme },
        );
        const cdp = await context.newCDPSession(page);
        for (const category of categories) {
          await page.goto(`/__fixtures/ui?category=${category}`);
          await expect(page.getByRole('heading', { level: 1 })).toBeVisible();
          if (category === 'data')
            await expect(page.locator('table')).toHaveCount(2);
          expect(await page.evaluate(() => innerWidth)).toBe(1440 / scale);
          const issues = await page.evaluate(() => {
            const ids = new Set<string>(),
              problems: string[] = [];
            for (const element of document.querySelectorAll('[id]')) {
              if (ids.has(element.id))
                problems.push(`duplicate ID: ${element.id}`);
              ids.add(element.id);
            }
            for (const element of document.querySelectorAll(
              '[aria-labelledby],[aria-describedby]',
            ))
              for (const attr of ['aria-labelledby', 'aria-describedby'])
                for (const id of (element.getAttribute(attr) ?? '')
                  .split(/\s+/)
                  .filter(Boolean))
                  if (!document.getElementById(id))
                    problems.push(`missing ${attr}: ${id}`);
            if (document.documentElement.scrollWidth > innerWidth + 1)
              problems.push('document horizontal overflow');
            for (const control of document.querySelectorAll<HTMLElement>(
              'button,input[type=checkbox],input[type=radio]',
            )) {
              if (
                !control.getClientRects().length ||
                control.matches(':disabled')
              )
                continue;
              const target = control.matches('input')
                ? (control.closest('label') ?? control)
                : control;
              const rect = target.getBoundingClientRect();
              if (rect.width < 24 || rect.height < 24)
                problems.push(
                  `small target ${control.getAttribute('aria-label') ?? control.textContent?.trim()}: ${rect.width}x${rect.height}`,
                );
            }
            return problems;
          });
          expect(issues, `${category} at ${scale * 100}%`).toEqual([]);
          const tree = await cdp.send('Accessibility.getFullAXTree');
          const unnamed = tree.nodes.filter(
            (node) =>
              !node.ignored &&
              [
                'button',
                'textbox',
                'checkbox',
                'switch',
                'radio',
                'combobox',
                'tab',
              ].includes(node.role?.value ?? '') &&
              !node.name?.value?.trim(),
          );
          expect(
            unnamed.map((node) => node.role?.value),
            `${category} accessible control names`,
          ).toEqual([]);
          if (scale === 2)
            await page.screenshot({
              path: `artifacts/playwright/integrated/${category}-${locale}-${theme}-200.png`,
              fullPage: true,
            });
        }
        errors.push(...pageErrors);
        await context.close();
      }
      expect(errors).toEqual([]);
    });
  }

for (const [device, width, height] of [
  ['desktop', 1440, 900],
  ['tablet', 1024, 768],
  ['mobile', 390, 844],
] as const)
  for (const locale of ['pl', 'en'])
    for (const theme of ['light', 'dark']) {
      test(`all optional workspace combinations ${device} ${locale} ${theme}`, async ({
        page,
      }) => {
        test.setTimeout(120000);
        await page.setViewportSize({ width, height });
        await page.emulateMedia({ reducedMotion: 'reduce' });
        const errors = recordProblems(page);
        await page.addInitScript(
          ({ locale, theme }) => {
            localStorage.setItem('runa.locale', locale);
            localStorage.setItem('runa.theme', theme);
          },
          { locale, theme },
        );
        await page.goto('/__fixtures/application/overview');
        const names =
          locale === 'pl'
            ? [
                'Menu boczne',
                'Menu górne',
                'Statystyki',
                'Pasek narzędzi',
                'Panel dodatkowy',
              ]
            : [
                'Sidebar',
                'Top navigation',
                'Statistics',
                'Toolbar',
                'Supplementary panel',
              ];
        const selectors = [
          '.shell-sidebar',
          '.shell-top-nav',
          '.shell-statistics',
          '.shell-toolbar',
          '.shell-supplementary',
        ];
        let previous = 31;
        for (const workspaceWidth of ['fluid', 'contained']) {
          await page
            .getByLabel(
              locale === 'pl' ? 'Szerokość obszaru' : 'Workspace width',
              { exact: true },
            )
            .click();
          await page
            .getByRole('option', {
              name:
                locale === 'pl'
                  ? workspaceWidth === 'fluid'
                    ? 'Pełna'
                    : 'Ograniczona'
                  : workspaceWidth === 'fluid'
                    ? 'Fluid'
                    : 'Contained',
              exact: true,
            })
            .click();
          for (let index = 0; index < 32; index++) {
            const mask = index ^ (index >> 1);
            for (let bit = 0; bit < 5; bit++)
              if ((mask & (1 << bit)) !== (previous & (1 << bit)))
                await page
                  .getByRole('button', { name: names[bit]!, exact: true })
                  .click();
            previous = mask;
            for (let bit = 0; bit < 5; bit++)
              await expect(page.locator(selectors[bit]!)).toHaveCount(
                mask & (1 << bit) ? 1 : 0,
              );
            await expect(page.getByRole('main')).toBeVisible();
            expect(
              await page.evaluate(
                () => document.documentElement.scrollWidth <= innerWidth + 1,
              ),
              `${workspaceWidth} mask ${mask}`,
            ).toBe(true);
          }
        }
        expect(errors).toEqual([]);
      });
    }

for (const theme of ['light', 'dark']) {
  test(`semantic and control contrast ${theme}`, async ({ page }) => {
    await page.goto(`/__fixtures/standalone/standalone.html?theme=${theme}`);
    const failures = await page.evaluate(() => {
      const css = getComputedStyle(document.documentElement);
      function luminance(key: string) {
        const raw = css.getPropertyValue(`--runa-${key}`).trim();
        const value =
          raw.length === 4
            ? '#' + [...raw.slice(1)].map((char) => char + char).join('')
            : raw;
        const rgb = value
          .match(/[a-f\d]{2}/gi)
          ?.map((x) => parseInt(x, 16) / 255)
          .map((x) =>
            x <= 0.04045 ? x / 12.92 : ((x + 0.055) / 1.055) ** 2.4,
          );
        if (!rgb || rgb.length !== 3)
          throw new Error(`Unsupported color ${key}: ${value}`);
        return rgb[0]! * 0.2126 + rgb[1]! * 0.7152 + rgb[2]! * 0.0722;
      }
      function ratio(a: string, b: string) {
        const x = luminance(a),
          y = luminance(b);
        return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05);
      }
      const failures: string[] = [];
      for (const tone of [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info',
        'accent',
      ])
        for (const [foreground, background] of [
          ['foreground', 'solid'],
          ['foreground', 'hover'],
          ['text', 'subtle'],
        ]) {
          const value = ratio(`${tone}-${foreground}`, `${tone}-${background}`);
          if (value < 4.5)
            failures.push(`${tone} ${foreground}/${background}: ${value}`);
        }
      for (const surface of [
        'surface',
        'surface-raised',
        'surface-muted',
        'input-background',
      ])
        for (const foreground of ['text', 'text-secondary']) {
          const value = ratio(foreground, surface);
          if (value < 4.5) failures.push(`${foreground}/${surface}: ${value}`);
        }
      for (const foreground of ['control-border', 'focus-ring']) {
        const value = ratio(foreground, 'input-background');
        if (value < 3)
          failures.push(`${foreground}/input-background: ${value}`);
      }
      return failures;
    });
    expect(failures).toEqual([]);
  });
}

for (const locale of ['pl', 'en'])
  for (const theme of ['light', 'dark']) {
    test(`bare consumer keyboard, validation, dialog and export ${locale} ${theme}`, async ({
      page,
    }) => {
      await page.setViewportSize({ width: 390, height: 844 });
      const errors = recordProblems(page);
      await page.addInitScript(() => {
        for (const method of ['getItem', 'setItem', 'removeItem'] as const)
          Object.defineProperty(Storage.prototype, method, {
            configurable: true,
            value() {
              throw new Error(
                'Storage is unavailable in the independent consumer',
              );
            },
          });
      });
      await page.goto(
        `/__fixtures/standalone/standalone.html?locale=${locale}&theme=${theme}`,
      );
      await page.screenshot({
        path: `artifacts/playwright/integrated/standalone-${locale}-${theme}.png`,
        fullPage: true,
      });
      await expect(page.locator('.minimal-page,.application-page')).toHaveCount(
        0,
      );
      expect(
        await page.evaluate(
          () => document.documentElement.scrollWidth <= innerWidth,
        ),
      ).toBe(true);
      await page
        .getByRole('button', {
          name: locale === 'pl' ? 'Zapisz' : 'Save',
          exact: true,
        })
        .click();
      const name = page.getByRole('textbox', {
        name: locale === 'pl' ? 'Nazwa' : 'Name',
        exact: true,
      });
      await expect(name).toBeFocused();
      await expect(name).toHaveAttribute('aria-invalid', 'true');
      const description = await name.getAttribute('aria-describedby');
      expect(description).toBeTruthy();
      await expect(page.locator(`[id="${description}"]`)).toContainText(
        locale === 'pl' ? 'Podaj nazwę.' : 'Enter a name.',
      );
      await name.fill('Atlas');
      const amount = page.getByRole('textbox', {
        name: locale === 'pl' ? 'Kwota' : 'Amount',
        exact: true,
      });
      await amount.fill('invalid');
      await page
        .getByRole('button', {
          name: locale === 'pl' ? 'Zapisz' : 'Save',
          exact: true,
        })
        .click();
      await expect(amount).toBeFocused();
      await expect(amount).toHaveAttribute('aria-invalid', 'true');
      await expect(page.getByRole('dialog')).toHaveCount(0);
      await amount.fill(locale === 'pl' ? '12,50' : '12.50');

      await page
        .getByLabel(locale === 'pl' ? 'Miasto' : 'City', { exact: true })
        .focus();
      await page.keyboard.press('ArrowDown');
      await expect(page.getByRole('listbox')).toBeVisible();
      await page.keyboard.press('ArrowDown');
      await page.keyboard.press('Enter');
      const save = page.getByRole('button', {
        name: locale === 'pl' ? 'Zapisz' : 'Save',
        exact: true,
      });
      await save.click();
      const dialog = page.getByRole('dialog', {
        name: locale === 'pl' ? 'Zapisać dane testowe?' : 'Save test data?',
        exact: true,
      });
      await expect(dialog).toBeVisible();
      await expect(dialog).toHaveAttribute('aria-modal', 'true');
      await expect(
        dialog.getByRole('button', {
          name: locale === 'pl' ? 'Anuluj' : 'Cancel',
          exact: true,
        }),
      ).toBeFocused();
      await page.keyboard.press('Tab');
      await expect(
        dialog.getByRole('button', {
          name: locale === 'pl' ? 'Potwierdź' : 'Confirm',
          exact: true,
        }),
      ).toBeFocused();
      await page.keyboard.press('Tab');
      await expect(
        dialog.getByRole('button', {
          name: locale === 'pl' ? 'Zamknij dialog' : 'Close dialog',
          exact: true,
        }),
      ).toBeFocused();
      await page.keyboard.press('Shift+Tab');
      await expect(
        dialog.getByRole('button', {
          name: locale === 'pl' ? 'Potwierdź' : 'Confirm',
          exact: true,
        }),
      ).toBeFocused();
      await page.keyboard.press('Tab');
      await page.keyboard.press('Tab');
      await expect(
        dialog.getByRole('button', {
          name: locale === 'pl' ? 'Anuluj' : 'Cancel',
          exact: true,
        }),
      ).toBeFocused();
      await page.keyboard.press('Escape');
      await expect(save).toBeFocused();
      await save.click();
      await page
        .getByRole('dialog', {
          name: locale === 'pl' ? 'Zapisać dane testowe?' : 'Save test data?',
          exact: true,
        })
        .getByRole('button', {
          name: locale === 'pl' ? 'Potwierdź' : 'Confirm',
          exact: true,
        })
        .click();
      await expect(
        page.getByText(
          locale === 'pl' ? 'Zapisano dane testowe' : 'Test data saved',
          { exact: true },
        ),
      ).toBeVisible();
      const formTab = page.getByRole('tab', {
        name: locale === 'pl' ? 'Formularz' : 'Form',
        exact: true,
      });
      await formTab.focus();
      await page.keyboard.press('ArrowRight');
      await expect(
        page.getByRole('tab', {
          name: locale === 'pl' ? 'Tabela' : 'Table',
          exact: true,
        }),
      ).toHaveAttribute('aria-selected', 'true');
      await page.getByRole('button', { name: /Nazwa|Name/ }).click();
      await expect(page.locator('th[aria-sort=ascending]')).toHaveCount(1);
      await page.locator('thead').getByRole('checkbox').check();
      const pending = page.waitForEvent('download');
      await page.getByRole('button', { name: 'CSV', exact: true }).click();
      expect(await (await pending).failure()).toBeNull();
      expect(errors).toEqual([]);
    });
  }

test('reduced motion keeps interactions usable and print removes shell controls', async ({
  page,
}) => {
  await page.emulateMedia({ reducedMotion: 'reduce' });
  await page.goto('/__fixtures/ui?category=data');
  await page
    .getByRole('button', { name: 'Ustawienia eksportu', exact: true })
    .first()
    .click();
  const panel = page.getByRole('dialog', {
    name: 'Ustawienia eksportu',
    exact: true,
  });
  await expect(panel).toBeVisible();
  const motion = await panel.evaluate((element) => ({
    transition: getComputedStyle(element).transitionDuration,
    animation: getComputedStyle(element).animationDuration,
  }));
  expect(motion.transition).toBe('0s');
  expect(motion.animation).toBe('0s');
  await page.keyboard.press('Escape');
  await page.goto('/__fixtures/application/overview');
  await page.emulateMedia({ media: 'print' });
  await expect(page.locator('.shell-header')).toBeHidden();
  await expect(page.locator('.shell-sidebar')).toBeHidden();
  await expect(page.locator('.shell-toolbar')).toBeHidden();
  await expect(page.getByRole('contentinfo')).toBeHidden();
  await expect(page.getByRole('main')).toBeVisible();
});

for (const locale of ['pl', 'en']) {
  test(`calendar keyboard and assistive semantics ${locale}`, async ({
    page,
  }) => {
    await page.addInitScript(
      (locale) => localStorage.setItem('runa.locale', locale),
      locale,
    );
    await page.goto('/__fixtures/ui?category=additional');
    const date = page.getByRole('textbox', {
      name: locale === 'pl' ? 'Data' : 'Date',
      exact: true,
    });
    await date.focus();
    await page.keyboard.press('Alt+ArrowDown');
    const dialog = page
      .getByRole('dialog')
      .filter({ has: page.getByRole('grid') });
    await expect(dialog).toBeVisible();
    const selected = dialog.locator('[data-date="2026-10-19"]');
    await expect(selected).toBeFocused();
    await page.keyboard.press('ArrowRight');
    const next = dialog.locator('[data-date="2026-10-20"]');
    await expect(next).toBeFocused();
    await expect(next).toHaveAttribute('role', 'gridcell');
    await expect(next).toHaveAttribute('aria-label', /20/);
    await page.keyboard.press('Enter');
    await expect(dialog).not.toBeVisible();
    await expect(date).toHaveValue(
      locale === 'pl' ? '20.10.2026' : '2026-10-20',
    );
    const checkbox = page.getByRole('checkbox', {
      name: /Uwzględnij w zestawieniu|Include in summary/,
    });
    await expect(checkbox).toHaveAttribute('aria-checked', 'mixed');
    await checkbox.focus();
    await page.keyboard.press('Space');
    await expect(checkbox).toBeChecked();
    const active = page.getByRole('switch', {
      name: /Aktywny widok|Active view/,
    });
    await active.focus();
    await page.keyboard.press('Space');
    await expect(active).not.toBeChecked();
  });
}
