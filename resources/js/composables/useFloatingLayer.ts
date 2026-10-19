import {
  computed,
  inject,
  nextTick,
  onScopeDispose,
  ref,
  watch,
  type Ref,
} from 'vue';

import { addLayer, removeLayer, isTopLayer, modalPortal } from '../lib/layers';
export function useFloatingLayer(options: {
  open: Ref<boolean>;
  anchor: Ref<globalThis.HTMLElement | undefined>;
  panel: Ref<globalThis.HTMLElement | undefined>;
  placement: () => 'bottom' | 'right';
  align?: () => 'start' | 'end';
  dismiss: (restoreFocus: boolean) => void;
  hideOnScroll?: boolean;
}) {
  const key = Symbol('floating-layer');
  const portal = inject(modalPortal, undefined);
  const teleportTarget = computed(() => portal?.value ?? 'body');
  const position = ref({ left: '8px', top: '8px' });
  const ready = ref(false);
  function top() {
    return isTopLayer(key);
  }
  function contains(target: globalThis.EventTarget | null) {
    return (
      target instanceof globalThis.Node &&
      (options.anchor.value?.contains(target) ||
        options.panel.value?.contains(target))
    );
  }
  function place() {
    const anchor = options.anchor.value?.getBoundingClientRect();
    const panel = options.panel.value?.getBoundingClientRect();
    if (!anchor || !panel) return;
    let left =
      options.align?.() === 'end' ? anchor.right - panel.width : anchor.left;
    let y = anchor.bottom + 8;
    if (options.placement() === 'right') {
      left = anchor.right + 8;
      if (left + panel.width > window.innerWidth - 8)
        left = anchor.left - panel.width - 8;
      y = anchor.top;
    } else if (
      y + panel.height > window.innerHeight - 8 &&
      anchor.top > panel.height + 8
    )
      y = anchor.top - panel.height - 8;
    position.value = {
      left: `${Math.max(8, Math.min(left, window.innerWidth - panel.width - 8))}px`,
      top: `${Math.max(8, Math.min(y, window.innerHeight - panel.height - 8))}px`,
    };
    ready.value = true;
  }
  function escape(event: globalThis.KeyboardEvent) {
    if (event.key === 'Escape' && top()) {
      event.preventDefault();
      event.stopPropagation();
      options.dismiss(true);
    }
  }
  function outside(event: globalThis.PointerEvent) {
    if (top() && !contains(event.target)) options.dismiss(false);
  }
  function scroll(event: globalThis.Event) {
    if (options.panel.value?.contains(event.target as globalThis.Node)) return;
    if (options.hideOnScroll) options.dismiss(false);
    else place();
  }
  let generation = 0;
  let observer: globalThis.ResizeObserver | undefined;
  function detach() {
    generation++;
    removeLayer(key);
    document.removeEventListener('keydown', escape, true);
    document.removeEventListener('pointerdown', outside);
    document.removeEventListener('scroll', scroll, true);
    window.removeEventListener('resize', place);
    observer?.disconnect();
    observer = undefined;
  }
  watch(options.open, async (open) => {
    if (!open) {
      detach();
      ready.value = false;
      return;
    }
    const request = ++generation;
    addLayer(key);
    document.addEventListener('keydown', escape, true);
    document.addEventListener('pointerdown', outside);
    document.addEventListener('scroll', scroll, true);
    window.addEventListener('resize', place);
    await nextTick();
    if (!options.open.value || request !== generation) return;
    place();
    observer = new ResizeObserver(place);
    if (options.panel.value) observer.observe(options.panel.value);
    if (options.anchor.value) observer.observe(options.anchor.value);
  });
  onScopeDispose(detach);
  return { position, ready, place, teleportTarget };
}
