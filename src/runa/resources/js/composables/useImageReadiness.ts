import { computed, onScopeDispose, ref, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';

/** Per-owner image lifecycle. No global/private image cache and no identity authority. */
export function useImageReadiness(
  source: MaybeRefOrGetter<string>,
  identity: MaybeRefOrGetter<string | null> = 'image',
  timeoutMs = 2500,
) {
  const src = ref('');
  const state = ref<'loading' | 'ready' | 'fallback'>('fallback');
  let previous: string | null = null;
  watch(
    [() => toValue(source), () => toValue(identity)],
    ([url, owner], _, cleanup) => {
      if (owner !== previous || owner === null) src.value = '';
      previous = owner;
      if (!url || owner === null) {
        src.value = '';
        state.value = 'fallback';
        return;
      }
      let active = true;
      const image = new Image();
      state.value = 'loading';
      function settle(ok: boolean) {
        if (!active) return;
        active = false;
        clearTimeout(timer);
        image.onload = image.onerror = null;
        src.value = ok ? url : '';
        state.value = ok ? 'ready' : 'fallback';
      }
      const timer = setTimeout(() => settle(false), timeoutMs);
      image.onload = () => {
        if (image.decode)
          void image.decode().then(
            () => settle(true),
            () => settle(false),
          );
        else settle(image.naturalWidth > 0);
      };
      image.onerror = () => settle(false);
      image.decoding = 'async';
      image.src = url;
      cleanup(() => {
        active = false;
        clearTimeout(timer);
        image.onload = image.onerror = null;
        image.src = '';
      });
    },
    { immediate: true, flush: 'sync' },
  );
  onScopeDispose(() => {
    src.value = '';
  });
  return {
    src: computed(() => src.value),
    state: computed(() => state.value),
    settled: computed(() => state.value !== 'loading'),
  };
}
export type ImageReadiness = ReturnType<typeof useImageReadiness>;
