import { onMounted, onScopeDispose, watch } from 'vue';

/** Private visual scheduler; no work for hidden, reduced-motion or static output. */
export function useDecorativeMotion(
  enabled: () => boolean,
  play: () => globalThis.Animation[],
  duration: number,
  quiet: readonly [number, number],
) {
  let dispose = () => {};
  onMounted(() => {
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const print = window.matchMedia('print');
    let timer: ReturnType<typeof globalThis.setTimeout> | undefined;
    let animations: globalThis.Animation[] = [];
    const permitted = () =>
      enabled() &&
      !reduced.matches &&
      !print.matches &&
      document.visibilityState === 'visible';
    function stop() {
      globalThis.clearTimeout(timer);
      animations.forEach((animation) => animation.cancel());
      animations = [];
    }
    function schedule() {
      stop();
      if (!permitted()) return;
      timer = globalThis.setTimeout(
        () => {
          if (!permitted()) return;
          animations = play();
          timer = globalThis.setTimeout(schedule, duration);
        },
        quiet[0] + Math.random() * (quiet[1] - quiet[0]),
      );
    }
    reduced.addEventListener('change', schedule);
    print.addEventListener('change', schedule);
    document.addEventListener('visibilitychange', schedule);
    const unwatch = watch(enabled, schedule, { immediate: true });
    dispose = () => {
      stop();
      unwatch();
      reduced.removeEventListener('change', schedule);
      print.removeEventListener('change', schedule);
      document.removeEventListener('visibilitychange', schedule);
    };
  });
  onScopeDispose(() => dispose());
}
