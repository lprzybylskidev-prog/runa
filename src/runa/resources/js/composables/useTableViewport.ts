import { ref, watch } from 'vue';
import type { Ref } from 'vue';

/** Keep the original semantic header and its controls in the scrolling table. */
export function useTableViewport(table: Ref<HTMLTableElement | undefined>) {
  const region = ref<HTMLDivElement>();
  const scrollbar = ref<HTMLDivElement>();
  const overflow = ref(false);
  const width = ref(0);
  const headerHeight = ref(0);
  const railHeight = 16;

  function synchronize(source: 'table' | 'rail') {
    const content = region.value,
      rail = scrollbar.value;
    if (!content || !rail) return;
    const target = source === 'table' ? rail : content;
    const left = source === 'table' ? content.scrollLeft : rail.scrollLeft;
    if (Math.abs(target.scrollLeft - left) > 0.5) target.scrollLeft = left;
  }

  watch(
    table,
    (element, _previous, cleanup) => {
      if (!element) return;
      let frame = 0;
      let scrollParent: HTMLElement | undefined;
      let shell: Element | null | undefined;
      let previousOffset = Number.NaN;
      function pin(event?: Event) {
        const content = region.value;
        const head = element?.tHead;
        const rail = scrollbar.value;
        // Horizontal scrolling already moves the original header with its table.
        if (
          !content ||
          !head ||
          !rail ||
          event?.target === content ||
          event?.target === rail
        )
          return;
        const bounds = content.getBoundingClientRect();
        const top = Math.max(
          0,
          shell?.getBoundingClientRect().bottom ?? 0,
          scrollParent
            ? scrollParent.getBoundingClientRect().top + scrollParent.clientTop
            : 0,
        );
        const offset = Math.max(
          0,
          Math.min(top - bounds.top, bounds.height - headerHeight.value),
        );
        if (offset === previousOffset) return;
        previousOffset = offset;
        // Apply in the scroll event itself: an extra animation frame followed by
        // a Vue render leaves the header visibly behind compositor scrolling.
        const transform = `translate3d(0, ${offset}px, 0)`;
        head.style.transform = transform;
        rail.style.transform = transform;
      }
      function measure() {
        frame = 0;
        const content = region.value;
        if (!content || !element?.tHead) return;
        const head = element.tHead.getBoundingClientRect();
        width.value = element.getBoundingClientRect().width;
        overflow.value = content.scrollWidth > content.clientWidth + 1;
        headerHeight.value = head.height;
        // The shell owns its sticky header; scrollable dialogs own their viewport.
        shell = !content.closest('[role="dialog"]')
          ? content.closest('.application-page')?.querySelector('.shell-header')
          : undefined;
        scrollParent = undefined;
        for (
          let parent = content.parentElement;
          parent;
          parent = parent.parentElement
        ) {
          if (
            /(auto|scroll)/.test(getComputedStyle(parent).overflowY) &&
            parent.scrollHeight > parent.clientHeight
          ) {
            scrollParent = parent;
            break;
          }
        }
        pin();
        synchronize('table');
      }
      function schedule() {
        if (!frame) frame = requestAnimationFrame(measure);
      }
      const observer = new ResizeObserver(schedule);
      observer.observe(element);
      if (element.tHead) observer.observe(element.tHead);
      if (region.value) observer.observe(region.value);
      window.addEventListener('scroll', pin, { capture: true, passive: true });
      window.addEventListener('resize', schedule);
      schedule();
      cleanup(() => {
        observer.disconnect();
        cancelAnimationFrame(frame);
        window.removeEventListener('scroll', pin, true);
        element.tHead?.style.removeProperty('transform');
        scrollbar.value?.style.removeProperty('transform');
        window.removeEventListener('resize', schedule);
      });
    },
    { flush: 'post' },
  );

  return {
    setRegion: (element: unknown) => {
      region.value =
        element instanceof globalThis.HTMLDivElement ? element : undefined;
    },
    setScrollbar: (element: unknown) => {
      scrollbar.value =
        element instanceof globalThis.HTMLDivElement ? element : undefined;
    },
    overflow,
    width,
    headerHeight,
    railHeight,
    synchronize,
  };
}
