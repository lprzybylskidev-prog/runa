import { onScopeDispose, reactive, watch, type Ref } from 'vue';
import type { DependentField } from '../runa/ui/advancedForms';
import type { SelectOption } from '../runa/ui/selection';
/** Bounded option domains only. Large remote searches belong to SelectionProvider. */
export function useDependentFields(
  fields: readonly DependentField[],
  values: Ref<Record<string, string>>,
) {
  const definitions = new Map(fields.map((field) => [field.key, field]));
  if (definitions.size !== fields.length)
    throw new TypeError('Dependent field keys must be unique.');
  const ordered: DependentField[] = [];
  const visiting = new Set<string>();
  const visited = new Set<string>();
  function visit(key: string) {
    if (visiting.has(key))
      throw new TypeError('Dependent field graph contains a cycle.');
    if (visited.has(key)) return;
    const field = definitions.get(key);
    if (!field) throw new TypeError('Unknown dependent field.');
    visiting.add(key);
    for (const parent of field.dependsOn ?? []) visit(parent);
    visiting.delete(key);
    visited.add(key);
    ordered.push(field);
  }
  for (const field of fields) visit(field.key);
  const state = reactive<
    Record<
      string,
      {
        options: SelectOption[];
        loading: boolean;
        error: boolean;
        ready: boolean;
      }
    >
  >({});
  const controllers = new Map<string, globalThis.AbortController>();
  const versions = new Map<string, number>();
  let disposed = false;
  for (const field of ordered)
    state[field.key] = {
      options: [],
      loading: false,
      error: false,
      ready: false,
    };
  function invalidate(key: string, clear: boolean) {
    controllers.get(key)?.abort();
    versions.set(key, (versions.get(key) ?? 0) + 1);
    Object.assign(state[key]!, {
      options: [],
      loading: false,
      error: false,
      ready: false,
    });
    if (clear && values.value[key]) values.value[key] = '';
  }
  function descendants(key: string): string[] {
    const result = new Set<string>();
    function find(parent: string) {
      for (const field of ordered)
        if (field.dependsOn?.includes(parent) && !result.has(field.key)) {
          result.add(field.key);
          find(field.key);
        }
    }
    find(key);
    return [...result];
  }
  async function refresh(key: string) {
    const field = definitions.get(key)!;
    invalidate(key, false);
    const own = state[key]!;
    const parents = Object.fromEntries(
      (field.dependsOn ?? []).map((parent) => [
        parent,
        values.value[parent] ?? '',
      ]),
    );
    if (
      Object.entries(parents).some(
        ([parent, value]) =>
          !value ||
          !state[parent]!.ready ||
          !state[parent]!.options.some(
            (option) => option.value === value && !option.disabled,
          ),
      )
    )
      return;
    const version = versions.get(key);
    const controller = new AbortController();
    controllers.set(key, controller);
    own.loading = true;
    try {
      const options = field.load
        ? await field.load({ parents, signal: controller.signal })
        : (field.options ?? []);
      if (disposed || version !== versions.get(key)) return;
      own.options = [...options];
      own.ready = true;
      if (
        values.value[key] &&
        !options.some(
          (option) => option.value === values.value[key] && !option.disabled,
        )
      )
        values.value[key] = '';
    } catch {
      if (!disposed && version === versions.get(key)) own.error = true;
    } finally {
      if (!disposed && version === versions.get(key)) own.loading = false;
    }
  }
  for (const field of ordered) {
    watch(
      () => values.value[field.key],
      () => {
        for (const key of descendants(field.key)) invalidate(key, true);
        for (const child of ordered)
          if (child.dependsOn?.includes(field.key)) void refresh(child.key);
      },
      { flush: 'sync' },
    );
    watch(
      () => (field.dependsOn ?? []).map((parent) => state[parent]!.ready),
      () => {
        if (field.dependsOn?.length) void refresh(field.key);
      },
    );
  }
  for (const field of ordered) void refresh(field.key);
  onScopeDispose(() => {
    disposed = true;
    for (const controller of controllers.values()) controller.abort();
  });
  return {
    state,
    retry: (key: string) => {
      if (!definitions.has(key))
        throw new TypeError('Unknown dependent field.');
      return refresh(key);
    },
    cancel: (key: string) => {
      if (!definitions.has(key))
        throw new TypeError('Unknown dependent field.');
      invalidate(key, false);
      for (const child of descendants(key)) invalidate(child, true);
    },
  };
}
