import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import type {
  AdminOperationsController,
  AdminOperationData,
} from './useAdminOperations';
import { prepareBrandingImage } from './brandingImages';
import { object } from './decode';

type Value = string | number | boolean | null;
export interface AdminApplicationSetting {
  key: string;
  title: string;
  type: 'string' | 'integer' | 'boolean' | 'enum' | 'timezone';
  defaultValue: Value;
  maxLength: number | undefined;
  choices: string[];
  writable: boolean;
}
/** Uses the existing session-fenced single write lane and dirty navigation owner. */
export function useAdminApplicationSettings(
  operations: AdminOperationsController,
) {
  const revision = ref(0);
  const images = ref<Record<string, string>>({});
  const imageFiles = ref<{ logo: File[]; favicon: File[] }>({
    logo: [],
    favicon: [],
  });
  const imageBusy = ref(false),
    imageError = ref('');
  const imagePreview = ref<{ logo?: string; favicon?: string }>({});
  let imageEpoch = 0;

  const fields = shallowRef<AdminApplicationSetting[]>([]);
  const base = ref<Record<string, Value>>({});
  const draft = ref<Record<string, Value>>({});
  const conflicts = ref(new Set<string>());
  const invalid = ref('');
  const loaded = ref(false),
    saved = ref(false);
  const dirty = computed(
    () =>
      imageBusy.value ||
      imageFiles.value.logo.length > 0 ||
      imageFiles.value.favicon.length > 0 ||
      Object.keys(images.value).length > 0 ||
      fields.value.some(
        (field) => draft.value[field.key] !== base.value[field.key],
      ),
  );
  const valid = computed(
    () =>
      !imageBusy.value &&
      !imageError.value &&
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
    const presentation = response.presentation;
    const nextPreview: { logo?: string; favicon?: string } = {};
    if (
      presentation &&
      typeof presentation === 'object' &&
      !Array.isArray(presentation)
    ) {
      const logo = presentation.logo;
      if (
        logo &&
        typeof logo === 'object' &&
        !Array.isArray(logo) &&
        typeof logo.src === 'string'
      )
        nextPreview.logo = logo.src;
      if (typeof presentation.favicon === 'string')
        nextPreview.favicon = presentation.favicon;
    }
    const nextFields: AdminApplicationSetting[] = [];
    const nextBase: Record<string, Value> = {},
      nextDraft: Record<string, Value> = {};
    const nextConflicts = new Set<string>();
    for (const [key, raw] of Object.entries(definitions)) {
      const definition = object(raw);
      if (
        typeof definition.title !== 'string' ||
        !['string', 'integer', 'boolean', 'enum', 'timezone'].includes(
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
        defaultValue:
          typeof definition.default === 'string' ||
          typeof definition.default === 'number' ||
          typeof definition.default === 'boolean'
            ? definition.default
            : null,
        type: definition.type as AdminApplicationSetting['type'],
        maxLength:
          typeof definition.max_length === 'number'
            ? definition.max_length
            : undefined,
        choices: definition.choices as string[],
        writable,
      });
      nextBase[key] = value;
      const assetEdited =
        (key === 'presentation.logo' && imageFiles.value.logo.length > 0) ||
        (key === 'presentation.favicon' && imageFiles.value.favicon.length > 0);
      const edited =
        preserve &&
        writable &&
        Object.hasOwn(base.value, key) &&
        (draft.value[key] !== base.value[key] || assetEdited);
      nextDraft[key] = edited ? (draft.value[key] ?? null) : value;
      const assetChanged =
        assetEdited &&
        (key === 'presentation.logo'
          ? nextPreview.logo !== imagePreview.value.logo
          : nextPreview.favicon !== imagePreview.value.favicon);
      if (
        assetChanged ||
        (edited && value !== base.value[key] && nextDraft[key] !== value)
      )
        nextConflicts.add(key);
      if (edited && conflicts.value.has(key) && nextDraft[key] !== value)
        nextConflicts.add(key);
    }
    for (const kind of ['logo', 'favicon'] as const) {
      if (
        !nextFields.some(
          (field) => field.key === 'presentation.' + kind && field.writable,
        ) &&
        imageFiles.value[kind].length
      ) {
        imageEpoch++;
        imageBusy.value = false;
        imageError.value = '';
        imageFiles.value[kind] = [];
        delete images.value[kind];
        if (kind === 'logo') delete images.value.logo_icon;
      }
    }
    imagePreview.value = nextPreview;
    revision.value = response.revision;
    base.value = nextBase;
    draft.value = nextDraft;
    const order = [
      'presentation.name',
      'presentation.logo',
      'presentation.favicon',
      'presentation.locale',
      'presentation.theme',
      'presentation.timezone',
    ];
    fields.value = nextFields.sort((a, b) => {
      const first = order.indexOf(a.key),
        second = order.indexOf(b.key);
      return (first < 0 ? 100 : first) - (second < 0 ? 100 : second);
    });
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
    if (
      value !== 'uploaded' &&
      (key === 'presentation.logo' || key === 'presentation.favicon')
    ) {
      imageEpoch++;
      imageBusy.value = false;
      imageError.value = '';
      const kind = key === 'presentation.logo' ? 'logo' : 'favicon';
      delete images.value[kind];
      if (kind === 'logo') delete images.value.logo_icon;
      imageFiles.value[kind] = [];
    }
    saved.value = false;
  }
  function resolve(key: string, keepDraft: boolean) {
    if (!keepDraft) {
      edit(key, base.value[key] ?? null);
      if (key === 'presentation.logo' || key === 'presentation.favicon') {
        imageEpoch++;
        imageBusy.value = false;
        imageError.value = '';
        const kind = key === 'presentation.logo' ? 'logo' : 'favicon';
        delete images.value[kind];
        if (kind === 'logo') delete images.value.logo_icon;
        imageFiles.value[kind] = [];
      }
    }
    conflicts.value.delete(key);
  }
  async function selectImage(kind: 'logo' | 'favicon', files: readonly File[]) {
    const key = 'presentation.' + kind;
    if (
      operations.busy.value ||
      !fields.value.some((field) => field.key === key && field.writable)
    )
      return;
    const own = ++imageEpoch;
    imageError.value = '';
    imageFiles.value[kind] = [...files];
    if (!files[0]) {
      delete images.value[kind];
      if (kind === 'logo') delete images.value.logo_icon;
      draft.value[key] = base.value[key] ?? null;
      imageBusy.value = false;
      return;
    }
    imageBusy.value = true;
    try {
      const prepared = await prepareBrandingImage(files[0], kind);
      if (own !== imageEpoch) return;
      images.value = { ...images.value, ...prepared };
      draft.value[key] = 'uploaded';
      saved.value = false;
    } catch (failure) {
      if (own === imageEpoch)
        imageError.value =
          failure instanceof Error
            ? failure.message
            : 'The image could not be read.';
    } finally {
      if (own === imageEpoch) imageBusy.value = false;
    }
  }
  function previewImage(kind: 'logo' | 'favicon'): string | undefined {
    if (kind === 'favicon') {
      if (draft.value['presentation.favicon'] === 'uploaded')
        return images.value.favicon
          ? 'data:image/png;base64,' + images.value.favicon
          : imagePreview.value.favicon;
      if (images.value.logo_icon)
        return 'data:image/png;base64,' + images.value.logo_icon;
      return draft.value['presentation.logo'] ===
        base.value['presentation.logo']
        ? imagePreview.value.favicon
        : undefined;
    }
    if (images.value.logo) return 'data:image/png;base64,' + images.value.logo;
    return draft.value['presentation.logo'] === base.value['presentation.logo']
      ? imagePreview.value.logo
      : undefined;
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
            field.writable &&
            (draft.value[field.key] !== base.value[field.key] ||
              (field.key === 'presentation.logo' && !!images.value.logo) ||
              (field.key === 'presentation.favicon' && !!images.value.favicon)),
        )
        .map((field) => [field.key, draft.value[field.key] ?? null]),
    );
    const response = await operations.execute('application.update', {
      revision: revision.value,
      patch,
      ...(Object.keys(images.value).length
        ? { images: { ...images.value } }
        : {}),
    });
    if (!response) return;
    // A successful own publication adopts only the exact frozen draft; controls are
    // disabled throughout the single write lane. Subsequent reads reconcile in place.
    images.value = {};
    imageFiles.value = { logo: [], favicon: [] };
    base.value = { ...draft.value };
    operations.dirty.value = false;
    saved.value = true;
    if (typeof response.revision === 'number')
      revision.value = response.revision;
    await refresh();
  }
  watch(operations.contextRevision, () => {
    imageEpoch++;
    images.value = {};
    imageFiles.value = { logo: [], favicon: [] };
    imageBusy.value = false;
    imageError.value = '';
    imagePreview.value = {};
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
    imageEpoch++;
    images.value = {};
    imageFiles.value = { logo: [], favicon: [] };
    imagePreview.value = {};
    imageError.value = '';
    imageBusy.value = false;
    operations.dirty.value = false;
  });
  return {
    operations,
    revision,
    imageFiles,
    imageBusy,
    imageError,
    selectImage,
    previewImage,
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
