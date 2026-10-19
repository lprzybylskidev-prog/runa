import { expect, test } from '@playwright/test';

for (const reduced of [false, true]) {
  for (const width of [390, 768, 1440]) {
    test(`component motion retains dismissal and disclosure semantics ${width} reduced=${reduced}`, async ({
      page,
    }) => {
      await page.setViewportSize({ width, height: 900 });
      await page.emulateMedia({
        reducedMotion: reduced ? 'reduce' : 'no-preference',
      });
      await page.goto('/__fixtures/ui?category=selection');
      const trigger = page.locator('button[aria-haspopup=dialog]').first();
      await trigger.click();
      const popup = page.locator('.selection-panel');
      await expect(popup).toBeVisible();
      if (reduced)
        expect(await popup.evaluate((el) => el.getAnimations().length)).toBe(0);
      if (!reduced) {
        const exit = await popup.evaluate((el) => {
          document.dispatchEvent(
            new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }),
          );
          return new Promise<{
            inert: boolean;
            hidden: string | null;
            visibility: string;
          }>((resolve) => {
            requestAnimationFrame(() =>
              resolve({
                inert: (el as HTMLElement).inert,
                hidden: el.getAttribute('aria-hidden'),
                visibility: getComputedStyle(el).visibility,
              }),
            );
          });
        });
        expect(exit).toEqual({
          inert: true,
          hidden: 'true',
          visibility: 'visible',
        });
      } else await page.keyboard.press('Escape');
      await expect(trigger).toBeFocused();
      await expect(popup).toHaveCount(0);
      // Interrupted exits must not leave the reused popup inert.
      await trigger.evaluate((el) => {
        (el as HTMLElement).click();
      });
      await page.keyboard.press('Escape');
      await trigger.evaluate((el) => {
        (el as HTMLElement).click();
      });
      await expect(popup).toBeVisible();
      await expect(popup).not.toHaveAttribute('inert');
      await page.keyboard.press('Escape');
      await expect(popup).toHaveCount(0);

      await page.goto('/__fixtures/ui?category=containers');
      const disclosure = page
        .locator('button[aria-expanded][aria-controls]')
        .filter({ hasText: /Informacje dodatkowe|Additional information/ });
      const content = page.locator('.runa-collapse').first();
      await expect(content).toBeVisible();
      await disclosure.click();
      await expect(content).toHaveAttribute('inert');
      await expect(content).toHaveAttribute('aria-hidden', 'true');
      await expect
        .poll(() => content.evaluate((el) => el.getBoundingClientRect().height))
        .toBe(0);
      await disclosure.click();
      await expect(content).not.toHaveAttribute('inert');
      await expect
        .poll(() => content.evaluate((el) => el.getBoundingClientRect().height))
        .toBeGreaterThan(0);
      await page.screenshot({
        path: `artifacts/playwright/motion/disclosures-${width}-${reduced}.png`,
        fullPage: true,
      });
    });
  }
}

test('tag and file exits preserve live controls and return focus', async ({
  page,
}) => {
  await page.goto('/__fixtures/ui?category=additional');
  // Locate the actual public component by its remove buttons, independent of translated copy.
  const tag = page.getByRole('button', { name: /Usuń: Runa|Remove: Runa/ });
  await tag.click();
  await expect(tag).toHaveCount(0);
  expect(await page.evaluate(() => document.activeElement?.tagName)).toBe(
    'INPUT',
  );
  await expect(
    page.getByRole('button', { name: /Usuń: Vue|Remove: Vue/ }),
  ).toBeVisible();
  const file = page.locator('input[type=file]').first();
  await file.evaluate((el) => {
    const transfer = new DataTransfer();
    transfer.items.add(
      new File(['%PDF-1.4\n%%EOF'], 'motion.pdf', { type: 'application/pdf' }),
    );
    (el as HTMLInputElement).files = transfer.files;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  });
  const remove = page.locator('[data-file-remove]').first();
  await expect(remove).toBeVisible();
  await remove.click();
  await expect(remove).toHaveCount(0);
  expect(
    await page.evaluate(() => !!document.activeElement?.closest('[inert]')),
  ).toBe(false);
});

test('wizard content motion preserves drafts and stops for live reduced motion', async ({
  page,
}) => {
  await page.goto('/__fixtures/ui?category=advanced');
  const wizard = page.getByRole('region', {
    name: /Kroki formularza|Form steps/,
  });
  await wizard
    .getByRole('textbox', { name: /^(Nazwa|Name)/ })
    .fill('Retained draft');
  await wizard.getByRole('button', { name: /^(Dalej|Next)$/ }).click();
  await expect(
    wizard.getByRole('heading', { name: /Kontakt|Contact/ }),
  ).toBeFocused();
  await page.emulateMedia({ reducedMotion: 'reduce' });
  expect(
    await wizard
      .locator('fieldset')
      .evaluate((el) => el.getAnimations().length),
  ).toBe(0);
  await wizard.getByRole('button', { name: /Wstecz|Back/ }).click();
  await expect(
    wizard.getByRole('textbox', { name: /^(Nazwa|Name)/ }),
  ).toHaveValue('Retained draft');
  expect(
    await wizard
      .locator('fieldset')
      .evaluate((el) => el.getAnimations().length),
  ).toBe(0);
});
