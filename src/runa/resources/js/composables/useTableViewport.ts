import { ref, watch } from 'vue';
import type { Ref } from 'vue';

/** Keep the original semantic header and its controls in the scrolling table. */
export function useTableViewport(table: Ref<HTMLTableElement | undefined>) {
  const region = ref<HTMLDivElement>();
  const scrollbar = ref<HTMLDivElement>();
  const overflow = ref(false);
  const width = ref(0);
  const offset = ref(0);
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
      function measure() {
        frame = 0;
        const content = region.value;
        if (!content || !element?.tHead) return;
        const bounds = content.getBoundingClientRect();
        const head = element.tHead.getBoundingClientRect();
        width.value = element.getBoundingClientRect().width;
        overflow.value = content.scrollWidth > content.clientWidth + 1;
        headerHeight.value = head.height;
        // The shell owns its sticky header; scrollable dialogs own their viewport.
        let top = 0;
        if (!content.closest('[role="dialog"]')) {
          const shell = content
            .closest('.application-page')
            ?.querySelector('.shell-header');
          if (shell) top = Math.max(0, shell.getBoundingClientRect().bottom);
        }
        for (
          let parent = content.parentElement;
          parent;
          parent = parent.parentElement
        ) {
          if (
            /(auto|scroll)/.test(getComputedStyle(parent).overflowY) &&
            parent.scrollHeight > parent.clientHeight
          ) {
            top = Math.max(
              top,
              parent.getBoundingClientRect().top + parent.clientTop,
            );
            break;
          }
        }
        offset.value = Math.max(
          0,
          Math.min(top - bounds.top, bounds.height - head.height),
        );
        synchronize('table');
      }
      function schedule() {
        if (!frame) frame = requestAnimationFrame(measure);
      }
      const observer = new ResizeObserver(schedule);
      observer.observe(element);
      if (element.tHead) observer.observe(element.tHead);
      if (region.value) observer.observe(region.value);
      window.addEventListener('scroll', schedule, true);
      window.addEventListener('resize', schedule);
      schedule();
      cleanup(() => {
        observer.disconnect();
        cancelAnimationFrame(frame);
        window.removeEventListener('scroll', schedule, true);
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
    offset,
    headerHeight,
    railHeight,
    synchronize,
  };
}
