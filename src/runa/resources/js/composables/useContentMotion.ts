import { onScopeDispose, watch, type Ref } from 'vue';
/** Replaces no DOM: drafts, focus and state updates remain immediate. */
export function useContentMotion(
  target: Ref<HTMLElement | undefined>,
  state: () => unknown,
) {
  let animation: Animation | undefined;
  const reduced =
    typeof window === 'undefined'
      ? undefined
      : window.matchMedia('(prefers-reduced-motion: reduce)');
  const printing =
    typeof window === 'undefined' ? undefined : window.matchMedia('print');
  function cancel() {
    animation?.cancel();
    animation = undefined;
  }
  reduced?.addEventListener('change', cancel);
  printing?.addEventListener('change', cancel);
  watch(
    state,
    () => {
      cancel();
      if (!target.value || reduced?.matches || printing?.matches) return;
      animation = target.value.animate(
        [
          { opacity: 0.55, translate: '0 2px' },
          { opacity: 1, translate: '0 0' },
        ],
        { duration: 140, easing: 'ease-out' },
      );
    },
    { flush: 'post' },
  );
  onScopeDispose(() => {
    cancel();
    reduced?.removeEventListener('change', cancel);
    printing?.removeEventListener('change', cancel);
  });
}
