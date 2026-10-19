import {
  computed,
  onScopeDispose,
  ref,
  toValue,
  type MaybeRefOrGetter,
} from 'vue';
import type { WizardStep } from '../runa/ui/advancedForms';
/** Values stay in the caller's form; validation/submit never imply a server write. */
export function useWizard(
  steps: readonly WizardStep[],
  options: {
    validate: (key: string) => boolean | Promise<boolean>;
    submit: () => boolean | Promise<boolean>;
    disabled?: MaybeRefOrGetter<boolean>;
  },
) {
  if (
    !steps.length ||
    new Set(steps.map((step) => step.key)).size !== steps.length ||
    steps.some((step) => !step.key)
  )
    throw new TypeError('Wizard steps require unique nonempty keys.');
  let disposed = false;
  onScopeDispose(() => {
    disposed = true;
  });
  const index = ref(0);
  const busy = ref(false);
  const failed = ref(false);
  const complete = ref(false);
  const current = computed(() => steps[index.value]!);
  const blocked = () =>
    disposed || busy.value || toValue(options.disabled ?? false);
  async function go(target: number): Promise<boolean> {
    if (
      blocked() ||
      !Number.isInteger(target) ||
      target < 0 ||
      target >= steps.length
    )
      return false;
    if (target <= index.value) {
      if (disposed) return false;
      index.value = target;
      complete.value = false;
      failed.value = false;
      return true;
    }
    busy.value = true;
    failed.value = false;
    try {
      for (let i = index.value; i < target; i++) {
        if (disposed) return false;
        if (!(await options.validate(steps[i]!.key))) {
          index.value = i;
          failed.value = true;
          return false;
        }
      }
      if (disposed) return false;
      index.value = target;
      complete.value = false;
      return true;
    } catch {
      failed.value = true;
      return false;
    } finally {
      busy.value = false;
    }
  }
  async function finish(): Promise<boolean> {
    if (blocked() || index.value !== steps.length - 1) return false;
    busy.value = true;
    failed.value = false;
    complete.value = false;
    try {
      // Revalidate earlier steps: dependent or external edits can invalidate them.
      for (let i = 0; i < steps.length; i++) {
        if (disposed) return false;
        if (!(await options.validate(steps[i]!.key))) {
          index.value = i;
          failed.value = true;
          return false;
        }
      }
      if (disposed) return false;
      complete.value = Boolean(await options.submit()) && !disposed;
      failed.value = !complete.value;
      return complete.value;
    } catch {
      failed.value = true;
      return false;
    } finally {
      busy.value = false;
    }
  }
  return {
    index,
    current,
    busy,
    failed,
    complete,
    go,
    next: () => go(index.value + 1),
    previous: () => go(index.value - 1),
    finish,
  };
}
