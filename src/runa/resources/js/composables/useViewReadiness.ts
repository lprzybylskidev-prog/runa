import { computed, ref, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';

export interface ViewReadinessOptions {
  /** Null immediately withdraws the view. Include account/session/page identity. */
  identity: MaybeRefOrGetter<string | null>;
  required: MaybeRefOrGetter<boolean>;
  refreshing?: MaybeRefOrGetter<boolean>;
  pending?: MaybeRefOrGetter<boolean>;
  failed?: MaybeRefOrGetter<boolean>;
}
/** Presentation only: the owning controller supplies authorization and request fences. */
export function useViewReadiness(options: ViewReadinessOptions) {
  const ready = ref(false);
  let previous: string | null = null;
  watch(
    [() => toValue(options.identity), () => toValue(options.required)],
    ([identity, required]) => {
      if (identity !== previous || identity === null) ready.value = false;
      previous = identity;
      if (identity !== null && required) ready.value = true;
    },
    { immediate: true, flush: 'sync' },
  );
  return {
    ready: computed(() => ready.value),
    phase: computed(() => {
      if (!ready.value) return toValue(options.failed) ? 'error' : 'loading';
      if (toValue(options.pending)) return 'mutating';
      if (toValue(options.refreshing)) return 'refreshing';
      return 'ready';
    }),
  };
}
export type ViewReadiness = ReturnType<typeof useViewReadiness>;
