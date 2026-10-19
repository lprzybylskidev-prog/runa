/* eslint-disable vue/one-component-per-file -- Independent mounted harnesses exercise nested dialog and floating-layer integration. */
import { afterEach, expect, it, vi } from 'vitest';
import { defineComponent, h, nextTick, ref } from 'vue';
import { enableAutoUnmount, mount } from '@vue/test-utils';
import { AlertDialog, ConfirmDialog, Dialog, Select } from '@/runa/ui';
import { lockDocumentScroll } from '../../resources/js/lib/scrollLock';
enableAutoUnmount(afterEach);
async function settle() {
  await nextTick();
  await nextTick();
}
async function key(value: string, shift = false) {
  document.activeElement?.dispatchEvent(
    new KeyboardEvent('keydown', {
      key: value,
      shiftKey: shift,
      bubbles: true,
      cancelable: true,
    }),
  );
  await settle();
}
function dialog() {
  return document.querySelector<globalThis.HTMLElement>('.dialog-panel')!;
}
it('labels the modal, focuses its content, traps Tab and restores trigger focus and background state', async () => {
  const trigger = document.createElement('button');
  trigger.textContent = 'Open';
  document.body.append(trigger);
  trigger.focus();
  const before = document.body.style.overflow;
  const w = mount(Dialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Details', description: 'Description' },
    slots: { default: '<input aria-label="Name" /><button>Last</button>' },
  });
  await settle();
  const panel = dialog();
  expect(panel.getAttribute('aria-modal')).toBe('true');
  expect(panel.getAttribute('aria-labelledby')).toBeTruthy();
  expect(panel.getAttribute('aria-describedby')).toBeTruthy();
  expect(document.activeElement).toBe(panel.querySelector('input'));
  expect(trigger.inert).toBe(true);
  expect(document.body.style.overflow).toBe('hidden');
  const buttons = panel.querySelectorAll('button');
  buttons[buttons.length - 1]!.focus();
  await key('Tab');
  expect(document.activeElement).toBe(buttons[0]);
  await key('Tab', true);
  expect(document.activeElement).toBe(buttons[buttons.length - 1]);
  await key('Escape');
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([false]);
  await w.setProps({ modelValue: false });
  await settle();
  expect(document.activeElement).toBe(trigger);
  expect(trigger.inert).toBe(false);
  expect(document.body.style.overflow).toBe(before);
  trigger.remove();
});
it('makes dynamically inserted background roots inert and preserves existing inert/scroll-lock ownership', async () => {
  const inert = document.createElement('div');
  inert.inert = true;
  document.body.append(inert);
  const release = lockDocumentScroll();
  const w = mount(Dialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Modal' },
  });
  await settle();
  const late = document.createElement('button');
  document.body.append(late);
  await settle();
  expect(late.inert).toBe(true);
  w.unmount();
  await settle();
  expect(inert.inert).toBe(true);
  expect(late.inert).toBe(false);
  expect(document.body.style.overflow).toBe('hidden');
  release();
  inert.remove();
  late.remove();
});
it('uses least-destructive initial focus and keeps confirmation open for caller-owned busy/error/retry', async () => {
  const w = mount(ConfirmDialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Confirm', error: 'Try again' },
  });
  await settle();
  expect(document.activeElement?.textContent).toBe('Anuluj');
  expect(dialog().textContent).toContain('Try again');
  const confirm = dialog().querySelector<globalThis.HTMLButtonElement>(
    '[data-dialog-cancel]',
  )!.nextElementSibling as globalThis.HTMLButtonElement;
  confirm.click();
  await settle();
  expect(w.emitted('confirm')).toHaveLength(1);
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.setProps({ busy: true });
  await key('Escape');
  confirm.click();
  await settle();
  expect(w.emitted('confirm')).toHaveLength(1);
  expect(w.emitted('update:modelValue')).toBeUndefined();
  await w.setProps({ busy: false, error: '' });
  confirm.click();
  await settle();
  expect(w.emitted('confirm')).toHaveLength(2);
});
it('acknowledges an alert with explicit alertdialog semantics', async () => {
  const w = mount(AlertDialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Information', locale: 'en' },
    slots: { default: 'Read this' },
  });
  await settle();
  expect(dialog().getAttribute('role')).toBe('alertdialog');
  expect(document.activeElement?.textContent?.trim()).toBe('Understood');
  (document.activeElement as globalThis.HTMLButtonElement).click();
  await settle();
  expect(w.emitted('acknowledge')).toHaveLength(1);
  expect(w.emitted('update:modelValue')?.at(-1)).toEqual([false]);
});
it('dismisses only the top nested modal and returns focus into the parent', async () => {
  const Harness = defineComponent({
    setup() {
      const parent = ref(true),
        child = ref(false);
      return () =>
        h(
          Dialog,
          {
            modelValue: parent.value,
            title: 'Parent',
            'onUpdate:modelValue': (value: boolean) => {
              parent.value = value;
            },
          },
          {
            default: () => [
              h(
                'button',
                {
                  onClick: () => {
                    child.value = true;
                  },
                },
                'Open child',
              ),
              h(ConfirmDialog, {
                modelValue: child.value,
                title: 'Child',
                'onUpdate:modelValue': (value: boolean) => {
                  child.value = value;
                },
              }),
            ],
          },
        );
    },
  });
  const w = mount(Harness, {
    attachTo: document.body,
    global: { stubs: { transition: false } },
  });
  await settle();
  const opener = dialog().querySelector<globalThis.HTMLButtonElement>(
    '[data-dialog-content] button',
  )!;
  opener.focus();
  opener.click();
  await settle();
  expect(document.querySelectorAll('.dialog-panel')).toHaveLength(2);
  await key('Escape');
  expect(document.activeElement).toBe(opener);
  await vi.waitFor(() =>
    expect(document.querySelectorAll('.dialog-panel')).toHaveLength(1),
  );
  await key('Escape');
  await vi.waitFor(() =>
    expect(document.querySelectorAll('.dialog-panel')).toHaveLength(0),
  );
  w.unmount();
});
it('keeps floating selection inside the modal boundary and gives it Escape ownership', async () => {
  const Harness = defineComponent({
    setup() {
      const value = ref('');
      return () =>
        h(
          Dialog,
          { modelValue: true, title: 'Form' },
          {
            default: () =>
              h(Select, {
                modelValue: value.value,
                label: 'City',
                searchable: true,
                options: [{ value: 'a', label: 'Alpha' }],
                'onUpdate:modelValue': (next: string | string[]) => {
                  if (typeof next === 'string') value.value = next;
                },
              }),
          },
        );
    },
  });
  const w = mount(Harness, {
    attachTo: document.body,
    global: { stubs: { transition: false } },
  });
  await settle();
  const trigger = dialog().querySelector<globalThis.HTMLButtonElement>(
    '[aria-haspopup=dialog]',
  )!;
  trigger.click();
  await settle();
  const popup = document.querySelector('.selection-panel')!;
  expect(document.querySelector('.dialog-backdrop')?.contains(popup)).toBe(
    true,
  );
  expect(popup.closest('[inert]')).toBeNull();
  await key('Escape');
  await vi.waitFor(() =>
    expect(document.querySelector('.selection-panel')).toBeNull(),
  );
  expect(document.querySelector('.dialog-panel')).not.toBeNull();
  expect(document.activeElement).toBe(trigger);
  w.unmount();
});
it('requires a complete backdrop gesture and honors Escape/outside/closable settings', async () => {
  const w = mount(Dialog, {
    attachTo: document.body,
    props: {
      modelValue: true,
      title: 'Modal',
      closeOnEscape: false,
      closeOnOutside: false,
    },
  });
  await settle();
  const root = document.querySelector('.dialog-backdrop')!;
  const pointer = (target: globalThis.Element, type: string) =>
    target.dispatchEvent(new PointerEvent(type, { bubbles: true }));
  await key('Escape');
  pointer(root, 'pointerdown');
  pointer(root, 'pointerup');
  await settle();
  expect(w.emitted('close')).toBeUndefined();
  await w.setProps({ closeOnOutside: true });
  pointer(dialog(), 'pointerdown');
  pointer(root, 'pointerup');
  await settle();
  expect(w.emitted('close')).toBeUndefined();
  pointer(root, 'pointerdown');
  pointer(root, 'pointerup');
  await settle();
  expect(w.emitted('close')?.at(-1)).toEqual(['outside']);
  await w.setProps({ closable: false });
  expect(dialog().querySelector('button')).toBeNull();
});
it('holds focus on a nonclosable busy panel and cleans up rapid opening/unmounting', async () => {
  const w = mount(Dialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Busy', busy: true, closable: false },
    slots: { default: '<input />' },
  });
  await settle();
  expect(document.activeElement).toBe(dialog());
  await key('Tab');
  expect(document.activeElement).toBe(dialog());
  await w.setProps({ modelValue: false });
  await w.setProps({ modelValue: true });
  w.unmount();
  await settle();
  expect(document.querySelector('.dialog-backdrop')).toBeNull();
  expect(document.body.style.overflow).not.toBe('hidden');
});

it('does not dismiss the modal with the same backdrop gesture that dismisses its floating child', async () => {
  const w = mount(Dialog, {
    attachTo: document.body,
    props: { modelValue: true, title: 'Form', closeOnOutside: true },
    slots: {
      default: () =>
        h(Select, {
          modelValue: '',
          label: 'City',
          options: [{ value: 'a', label: 'Alpha' }],
        }),
    },
  });
  await settle();
  dialog()
    .querySelector<globalThis.HTMLButtonElement>('[aria-haspopup=dialog]')!
    .click();
  await settle();
  const backdrop = document.querySelector('.dialog-backdrop')!;
  backdrop.dispatchEvent(new PointerEvent('pointerdown', { bubbles: true }));
  await settle();
  backdrop.dispatchEvent(new PointerEvent('pointerup', { bubbles: true }));
  await settle();
  expect(document.querySelector('.selection-panel')).toBeNull();
  expect(w.emitted('update:modelValue')).toBeUndefined();
});
