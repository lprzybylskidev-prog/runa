import { nextTick, onScopeDispose, watch, type Ref } from 'vue';
import { lockDocumentScroll } from '../lib/scrollLock';
import { addLayer, isTopLayer, removeLayer } from '../lib/layers';
interface ModalEntry {
  key: symbol;
  root: globalThis.HTMLElement;
  previous: globalThis.HTMLElement | undefined;
}
const modals: ModalEntry[] = [];
const inertState = new Map<globalThis.HTMLElement, boolean>();
let observer: globalThis.MutationObserver | undefined;
let unlockScroll: (() => void) | undefined;
function synchronize() {
  const root = modals.at(-1)?.root;
  if (!root) {
    observer?.disconnect();
    observer = undefined;
    for (const [element, inert] of inertState) element.inert = inert;
    inertState.clear();
    unlockScroll?.();
    unlockScroll = undefined;
    return;
  }
  for (const child of document.body.children)
    if (child instanceof globalThis.HTMLElement) {
      if (!inertState.has(child)) inertState.set(child, child.inert);
      child.inert = child.contains(root) ? false : true;
    }
  if (!unlockScroll) {
    unlockScroll = lockDocumentScroll();
    observer = new MutationObserver(synchronize);
    observer.observe(document.body, { childList: true });
  }
}
function focusables(root: globalThis.HTMLElement) {
  return Array.from(
    root.querySelectorAll<globalThis.HTMLElement>(
      'button, a[href], input, select, textarea, [tabindex], [contenteditable="true"]',
    ),
  ).filter(
    (element) =>
      element.tabIndex >= 0 &&
      !element.matches(':disabled') &&
      !element.closest('[inert], [hidden]') &&
      element.getClientRects().length &&
      getComputedStyle(element).visibility !== 'hidden',
  );
}
export function useModalLayer(options: {
  key: symbol;
  open: Readonly<Ref<boolean>>;
  root: Ref<globalThis.HTMLElement | undefined>;
  panel: Ref<globalThis.HTMLElement | undefined>;
  initialFocus: () => string;
  escape: () => void;
}) {
  let previous: globalThis.HTMLElement | undefined;
  let registered = false;
  let generation = 0;
  function topModal() {
    return modals.at(-1)?.key === options.key;
  }
  function focus() {
    const panel = options.panel.value;
    if (!panel) return;
    let target: globalThis.HTMLElement | null | undefined;
    try {
      target = options.initialFocus()
        ? panel.querySelector<globalThis.HTMLElement>(options.initialFocus())
        : undefined;
    } catch {
      /* Fall back when the caller supplied an invalid selector. */
    }
    if (
      !target ||
      target.matches(':disabled') ||
      target.closest('[hidden], [inert]')
    )
      target =
        panel.querySelector<globalThis.HTMLElement>('[autofocus]') ??
        focusables(
          panel.querySelector<globalThis.HTMLElement>(
            '[data-dialog-content]',
          ) ?? panel,
        )[0] ??
        panel;
    target.focus({ preventScroll: true });
  }
  function keyboard(event: globalThis.KeyboardEvent) {
    if (event.key === 'Escape' && isTopLayer(options.key)) {
      event.preventDefault();
      event.stopPropagation();
      options.escape();
    }
  }
  function tab(event: globalThis.KeyboardEvent) {
    if (
      event.key !== 'Tab' ||
      event.defaultPrevented ||
      !topModal() ||
      !options.root.value
    )
      return;
    const items = focusables(options.root.value);
    const active = document.activeElement;
    if (!items.length) {
      event.preventDefault();
      options.panel.value?.focus();
    } else if (
      event.shiftKey &&
      (active === items[0] || !items.includes(active as globalThis.HTMLElement))
    ) {
      event.preventDefault();
      items.at(-1)?.focus();
    } else if (
      !event.shiftKey &&
      (active === items.at(-1) ||
        !items.includes(active as globalThis.HTMLElement))
    ) {
      event.preventDefault();
      items[0]?.focus();
    }
  }
  function contain(event: globalThis.FocusEvent) {
    if (
      topModal() &&
      event.target instanceof globalThis.Node &&
      !options.root.value?.contains(event.target)
    )
      focus();
  }
  function detach(restore = true) {
    generation++;
    if (!registered) return;
    registered = false;
    const wasTop = topModal();
    const index = modals.findIndex((entry) => entry.key === options.key);
    const entry = modals[index];
    const returnTarget = entry?.previous ?? previous;
    if (entry)
      for (const modal of modals)
        if (modal.previous && entry.root.contains(modal.previous))
          modal.previous = returnTarget;
    if (index !== -1) modals.splice(index, 1);
    removeLayer(options.key);
    synchronize();
    document.removeEventListener('keydown', keyboard, true);
    document.removeEventListener('keydown', tab);
    document.removeEventListener('focusin', contain);
    if (
      restore &&
      wasTop &&
      returnTarget?.isConnected &&
      !returnTarget.closest('[inert]') &&
      !returnTarget.matches(':disabled')
    )
      returnTarget.focus({ preventScroll: true });
    else if (restore && wasTop)
      modals
        .at(-1)
        ?.root.querySelector<globalThis.HTMLElement>('[aria-modal="true"]')
        ?.focus({ preventScroll: true });
  }
  watch(
    options.open,
    async (open) => {
      if (!open) {
        detach();
        return;
      }
      previous =
        document.activeElement instanceof globalThis.HTMLElement
          ? document.activeElement
          : undefined;
      const request = ++generation;
      await nextTick();
      if (!options.open.value || request !== generation || !options.root.value)
        return;
      registered = true;
      addLayer(options.key);
      modals.push({ key: options.key, root: options.root.value, previous });
      synchronize();
      document.addEventListener('keydown', keyboard, true);
      document.addEventListener('keydown', tab);
      document.addEventListener('focusin', contain);
      focus();
    },
    { immediate: true, flush: 'post' },
  );
  onScopeDispose(() => detach());
  return { isTop: () => isTopLayer(options.key), focus };
}
