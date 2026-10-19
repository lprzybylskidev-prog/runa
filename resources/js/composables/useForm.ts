import { computed, ref, type Ref } from 'vue';
import { ApiError } from '../lib/http';
import type { FieldErrors, FormFailure, FormValue } from '../runa/ui/forms';
function stable(value: FormValue, seen = new Set<object>()): string {
  if (value !== null && typeof value === 'object') {
    if (
      seen.has(value) ||
      (!Array.isArray(value) &&
        ![Object.prototype, null].includes(Object.getPrototypeOf(value)))
    )
      throw new TypeError('Form values must be finite JSON data.');
    seen.add(value);
    const result = Array.isArray(value)
      ? `[${value.map((item) => stable(item, seen)).join(',')}]`
      : `{${Object.keys(value)
          .sort()
          .map((key) => `${JSON.stringify(key)}:${stable(value[key]!, seen)}`)
          .join(',')}}`;
    seen.delete(value);
    return result;
  }
  if (
    (typeof value === 'number' && !Number.isFinite(value)) ||
    !['string', 'number', 'boolean', 'object'].includes(typeof value)
  )
    throw new TypeError('Form values must be finite JSON data.');
  return JSON.stringify(value);
}
function clone<T extends FormValue>(value: T): T {
  return JSON.parse(stable(value)) as T;
}
export function laravelFieldErrors(payload: unknown): FieldErrors {
  const result: FieldErrors = Object.create(null) as FieldErrors;
  if (
    typeof payload !== 'object' ||
    payload === null ||
    !('errors' in payload) ||
    typeof payload.errors !== 'object' ||
    payload.errors === null ||
    Array.isArray(payload.errors)
  )
    return result;
  for (const [key, messages] of Object.entries(payload.errors)) {
    if (
      Array.isArray(messages) &&
      messages.every((message: unknown) => typeof message === 'string') &&
      messages.length
    )
      result[key] = messages;
  }
  return result;
}
export function useForm<T extends Record<string, FormValue>>(initial: T) {
  const defaults = clone(initial);
  const values = ref(clone(defaults)) as Ref<T>;
  let cleanValues = clone(defaults);
  const baseline = ref(stable(cleanValues));
  const errors = ref<FieldErrors>({});
  const busy = ref(false);
  const failure = ref<FormFailure>(null);
  const dirty = computed(() => stable(values.value) !== baseline.value);
  const valid = computed(
    () => !Object.values(errors.value).some((messages) => messages.length),
  );
  function markClean(snapshot: T = values.value) {
    cleanValues = clone(snapshot);
    baseline.value = stable(cleanValues);
  }
  function clearErrors(name?: string) {
    if (name) delete errors.value[name];
    else errors.value = {};
  }
  function reset(snapshot: T = cleanValues) {
    values.value = clone(snapshot);
    markClean();
    clearErrors();
    failure.value = null;
  }
  function setError(name: string, messages: string | readonly string[]) {
    errors.value[name] =
      typeof messages === 'string' ? [messages] : [...messages];
  }
  function applyError(error: unknown) {
    if (error instanceof ApiError && error.status === 422) {
      errors.value = laravelFieldErrors(error.payload);
      failure.value = 'validation';
    } else if (error instanceof ApiError && error.status === 409)
      failure.value = 'conflict';
    else failure.value = 'unexpected';
  }
  async function submit(
    action: (snapshot: T) => Promise<void>,
    validate?: (snapshot: T) => FieldErrors,
  ): Promise<boolean> {
    if (busy.value) return false;
    if (validate) errors.value = validate(values.value);
    if (!valid.value) {
      failure.value = 'validation';
      return false;
    }
    const snapshot = clone(values.value);
    busy.value = true;
    failure.value = null;
    try {
      await action(snapshot);
      markClean(snapshot);
      clearErrors();
      return true;
    } catch (error: unknown) {
      applyError(error);
      return false;
    } finally {
      busy.value = false;
    }
  }
  return {
    values,
    errors,
    busy,
    failure,
    dirty,
    valid,
    markClean,
    reset,
    clearErrors,
    setError,
    applyError,
    submit,
  };
}
