import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import type {
  AdminOperationsController,
  AdminOperationData,
} from './useAdminOperations';
import { object } from './decode';

type Value = string | number | boolean | null;
export interface AdminApplicationSetting {
  key: string;
  title: string;
  type: 'string' | 'integer' | 'boolean' | 'enum';
  maxLength: number | undefined;
  choices: string[];
  writable: boolean;
}
/** Uses the existing session-fenced single write lane and dirty navigation owner. */
export function useAdminApplicationSettings(
  operations: AdminOperationsController,
) {
  const revision = ref(0);
  const fields = shallowRef<AdminApplicationSetting[]>([]);
  const base = ref<Record<string, Value>>({});
  const draft = ref<Record<string, Value>>({});
  const conflicts = ref(new Set<string>());
  const invalid = ref('');
  const loaded = ref(false),
    saved = ref(false);
  const dirty = computed(() =>
    fields.value.some(
      (field) => draft.value[field.key] !== base.value[field.key],
    ),
  );
  const valid = computed(() =>
    fields.value.every((field) => {
      const value = draft.value[field.key];
      if (value === null) return true;
      if (field.type === 'string')
        return (
          typeof value === 'string' &&
          value.trim().length > 0 &&
          [...value].length <= (field.maxLength ?? 4096) &&
          !/[\u0000-\u001f\u007f]/u.test(value)
        );
      if (field.type === 'integer')
        return typeof value === 'number' && Number.isSafeInteger(value);
      if (field.type === 'boolean') return typeof value === 'boolean';
      return typeof value === 'string' && field.choices.includes(value);
    }),
  );
  watch(
    dirty,
    (value) => {
      operations.dirty.value = value;
    },
    { flush: 'sync' },
  );

  function apply(response: AdminOperationData, preserve: boolean) {
    const definitions = object(
      Array.isArray(response.definitions) && !response.definitions.length
        ? {}
        : response.definitions,
    );
    const values = object(
      Array.isArray(response.values) && !response.values.length
        ? {}
        : response.values,
    );
    if (
      typeof response.revision !== 'number' ||
      !Number.isSafeInteger(response.revision) ||
      response.revision < 1
    )
      throw new TypeError();
    const nextFields: AdminApplicationSetting[] = [];
    const nextBase: Record<string, Value> = {},
      nextDraft: Record<string, Value> = {};
    const nextConflicts = new Set<string>();
    for (const [key, raw] of Object.entries(definitions)) {
      const definition = object(raw);
      if (
        typeof definition.title !== 'string' ||
        !['string', 'integer', 'boolean', 'enum'].includes(
          String(definition.type),
        ) ||
        !Array.isArray(definition.choices) ||
        !definition.choices.every((item) => typeof item === 'string')
      )
        throw new TypeError();
      const value = values[key];
      if (
        value !== null &&
        typeof value !== 'string' &&
        typeof value !== 'number' &&
        typeof value !== 'boolean'
      )
        throw new TypeError();
      const writable = definition.writable === true;
      nextFields.push({
        key,
        title: definition.title,
        type: definition.type as AdminApplicationSetting['type'],
        maxLength:
          typeof definition.max_length === 'number'
            ? definition.max_length
            : undefined,
        choices: definition.choices as string[],
        writable,
      });
      nextBase[key] = value;
      const edited =
        preserve &&
        writable &&
        Object.hasOwn(base.value, key) &&
        draft.value[key] !== base.value[key];
      nextDraft[key] = edited ? (draft.value[key] ?? null) : value;
      if (edited && value !== base.value[key] && nextDraft[key] !== value)
        nextConflicts.add(key);
      if (edited && conflicts.value.has(key) && nextDraft[key] !== value)
        nextConflicts.add(key);
    }
    revision.value = response.revision;
    base.value = nextBase;
    draft.value = nextDraft;
    fields.value = nextFields;
    conflicts.value = nextConflicts;
    loaded.value = true;
  }
  async function refresh() {
    const response = await operations.read('application');
    if (!response) return;
    try {
      apply(response, true);
      invalid.value = '';
    } catch {
      invalid.value = 'Application settings could not be read.';
    }
  }
  function edit(key: string, value: Value) {
    if (
      operations.busy.value ||
      !fields.value.some((field) => field.key === key && field.writable)
    )
      return;
    draft.value[key] = value;
    saved.value = false;
  }
  function resolve(key: string, keepDraft: boolean) {
    if (!keepDraft) draft.value[key] = base.value[key] ?? null;
    conflicts.value.delete(key);
  }
  async function save() {
    if (
      !dirty.value ||
      !valid.value ||
      conflicts.value.size ||
      operations.busy.value
    )
      return;
    const patch = Object.fromEntries(
      fields.value
        .filter(
          (field) =>
            field.writable && draft.value[field.key] !== base.value[field.key],
        )
        .map((field) => [field.key, draft.value[field.key] ?? null]),
    );
    const response = await operations.execute('application.update', {
      revision: revision.value,
      patch,
    });
    if (!response) return;
    // A successful own publication adopts only the exact frozen draft; controls are
    // disabled throughout the single write lane. Subsequent reads reconcile in place.
    base.value = { ...draft.value };
    operations.dirty.value = false;
    saved.value = true;
    if (typeof response.revision === 'number')
      revision.value = response.revision;
    await refresh();
  }
  watch(operations.contextRevision, () => {
    fields.value = [];
    base.value = {};
    draft.value = {};
    revision.value = 0;
    conflicts.value = new Set();
    loaded.value = false;
    saved.value = false;
    invalid.value = '';
  });
  onScopeDispose(() => {
    operations.dirty.value = false;
  });
  return {
    operations,
    revision,
    fields,
    base,
    draft,
    dirty,
    valid,
    conflicts,
    invalid,
    loaded,
    saved,
    refresh,
    edit,
    resolve,
    save,
  };
}
export type AdminApplicationSettingsController = ReturnType<
  typeof useAdminApplicationSettings
>;
