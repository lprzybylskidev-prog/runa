import {
  computed,
  inject,
  provide,
  toValue,
  type InjectionKey,
  type MaybeRefOrGetter,
  type ComputedRef,
} from 'vue';
const visibility: InjectionKey<ComputedRef<boolean>> = Symbol(
  'presentation-visibility',
);
/** Suspend retained presentation and its modal portals while an owning full page takes focus. */
export function providePresentationVisibility(
  visible: MaybeRefOrGetter<boolean>,
): void {
  const parent = inject(
    visibility,
    computed(() => true),
  );
  provide(
    visibility,
    computed(() => parent.value && toValue(visible)),
  );
}
export function usePresentationVisibility() {
  return inject(
    visibility,
    computed(() => true),
  );
}
