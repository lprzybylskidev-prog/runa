import { ref, watch } from 'vue';
import type { Ref } from 'vue';

/** Internal owner for the supplied horizontal viewports, not vertical overlays. */
export function useHorizontalScroll(viewport: Ref<HTMLElement | undefined>) {
  const edges = ref({ left: false, right: false });
  function measure() {
    const element = viewport.value;
    const left = !!element && element.scrollLeft > 1;
    const right =
      !!element &&
      element.scrollWidth - element.clientWidth - element.scrollLeft > 1;
    if (left !== edges.value.left || right !== edges.value.right)
      edges.value = { left, right };
  }
  function wheel(event: WheelEvent) {
    const element = viewport.value;
    if (
      event.ctrlKey ||
      !element ||
      element.scrollWidth <= element.clientWidth + 1
    )
      return;
    const delta =
      Math.abs(event.deltaX) > Math.abs(event.deltaY)
        ? event.deltaX
        : event.deltaY;
    if (!delta) return;
    event.preventDefault();
    const unit =
      event.deltaMode === 1
        ? 16
        : event.deltaMode === 2
          ? element.clientWidth
          : 1;
    element.scrollLeft += delta * unit;
    measure();
  }
  watch(
    viewport,
    (element, _previous, cleanup) => {
      if (!element) {
        measure();
        return;
      }
      const resize = new ResizeObserver(measure);
      function observe() {
        resize.disconnect();
        resize.observe(element!);
        for (const child of element!.children) resize.observe(child);
        measure();
      }
      const mutations = new MutationObserver(observe);
      mutations.observe(element, {
        childList: true,
        characterData: true,
        subtree: true,
      });
      element.addEventListener('scroll', measure, { passive: true });
      observe();
      cleanup(() => {
        resize.disconnect();
        mutations.disconnect();
        element.removeEventListener('scroll', measure);
      });
    },
    { flush: 'post' },
  );
  return { edges, measure, wheel };
}
