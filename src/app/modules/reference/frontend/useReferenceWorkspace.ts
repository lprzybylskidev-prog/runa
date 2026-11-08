import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { ApiError, RequestCancelledError, request } from '@/runa/http';
import { useOperationProof } from '@/runa/auth';
import { useDirtyGuard } from '@/runa/ui';
import * as wire from './contracts';
import type {
  ReferenceAccess,
  ReferenceView,
  Sample,
  Settings,
  Snapshot,
  HistoryEntry,
  WorkEntry,
  SettingsPatch,
} from './contracts';
import { referenceCopy } from './copy';

/** One owning view/read/write lane. No session owner, passive reads or write replay. */
export function useReferenceWorkspace(
  access: () => ReferenceAccess,
  view: () => ReferenceView,
  locale: () => 'pl' | 'en',
) {
  const copy = computed(() => referenceCopy(locale()));
  const rows = shallowRef<Sample[]>([]),
    state = shallowRef<Settings>();
  const snapshotRows = shallowRef<Snapshot[]>([]),
    historyRows = shallowRef<HistoryEntry[]>([]),
    workRows = shallowRef<WorkEntry[]>([]);
  const selected = shallowRef<Sample>(),
    sampleConflict = shallowRef<Sample | null>();
  const settingsConflict = shallowRef<Settings>();
  const code = ref(''),
    label = ref(''),
    credential = ref('');
  const secretMode = ref<'keep' | 'replace' | 'clear'>('keep');
  const snapshotName = ref(''),
    next = ref<string | null>(null);
  const review = shallowRef<{
    token: string;
    input: string;
    patch: SettingsPatch;
    revision: number;
    reviewId: string;
  }>();
  const writing = ref(false);
  const busy = ref(false),
    ready = ref(false),
    denied = ref(false);
  const errorKey = ref<
    | ''
    | 'failed'
    | 'denied'
    | 'stale'
    | 'proof'
    | 'invalid'
    | 'snapshotDuplicate'
    | 'missing'
  >('');
  const successKey = ref<'' | 'saved' | 'admitted'>(''),
    attempt = ref(0);
  const fieldErrors = ref<Record<string, string>>({});
  const sampleDirty = computed(
    () => !!selected.value && code.value !== selected.value.code,
  );
  const settingsDirty = computed(
    () =>
      !!state.value &&
      (label.value !== state.value.label || secretMode.value !== 'keep'),
  );
  const dirty = computed(
    () => sampleDirty.value || settingsDirty.value || !!snapshotName.value,
  );
  let epoch = 0,
    alive = true,
    abort = new AbortController();
  const action = ref('app.reference.settings.publish');
  const proof = useOperationProof({
    identity: () =>
      access().identity && access().read && !denied.value
        ? access().identity
        : undefined,
    action: () => action.value,
    requirement: () => 'password',
    locale,
  });
  const allowed = computed(
    () => !!access().identity && access().read && !denied.value,
  );
  const writable = computed(() => allowed.value && access().write);
  const canEditCode = computed(
    () => writable.value && access().codeRead && access().codeWrite,
  );
  const leaveOpen = ref(false);
  let leaveResult: ((answer: boolean) => void) | undefined;
  const guard = useDirtyGuard({
    dirty,
    confirmLeave: () =>
      new Promise<boolean>((resolve) => {
        leaveResult = resolve;
        leaveOpen.value = true;
      }),
  });
  function answerLeave(answer: boolean) {
    leaveOpen.value = false;
    leaveResult?.(answer);
    leaveResult = undefined;
  }
  function clear() {
    ++epoch;
    abort.abort();
    abort = new AbortController();
    proof.reset();
    answerLeave(false);
    rows.value = [];
    state.value = undefined;
    snapshotRows.value = [];
    historyRows.value = [];
    workRows.value = [];
    selected.value = undefined;
    sampleConflict.value = undefined;
    settingsConflict.value = undefined;
    code.value = '';
    label.value = '';
    credential.value = '';
    secretMode.value = 'keep';
    snapshotName.value = '';
    review.value = undefined;
    next.value = null;
    ready.value = false;
    busy.value = false;
    writing.value = false;
    errorKey.value = '';
    successKey.value = '';
    fieldErrors.value = {};
  }
  async function api(
    path: string,
    method = 'GET',
    body?: object,
    query: Record<string, string | null> = {},
  ) {
    const own = epoch;
    const response = wire.object(
      wire.object(
        await request(
          `/app/reference/${path}`,
          {
            method,
            signal: abort.signal,
            ...(body
              ? {
                  headers: { 'Content-Type': 'application/json' },
                  body: JSON.stringify(body),
                }
              : {}),
          },
          query,
        ),
      ).data,
    );
    if (!alive || own !== epoch || abort.signal.aborted)
      throw new RequestCancelledError();
    return response;
  }
  function fail(cause: unknown): boolean {
    if (cause instanceof RequestCancelledError) return false;
    const payload =
      cause instanceof ApiError &&
      cause.payload &&
      typeof cause.payload === 'object' &&
      !Array.isArray(cause.payload)
        ? wire.object(cause.payload)
        : {};
    const nested =
      payload.error &&
      typeof payload.error === 'object' &&
      !Array.isArray(payload.error)
        ? wire.object(payload.error)
        : {};
    const reason =
      typeof nested.code === 'string'
        ? nested.code
        : typeof payload.reason === 'string'
          ? payload.reason
          : typeof payload.code === 'string'
            ? payload.code
            : '';
    // Only reviewed stale-state reasons trigger reconciliation, never every business 409.
    const stale = [
      'reference_sample_stale',
      'reference_settings_stale',
      'reference_review_stale',
      'settings_snapshot_stale',
      'settings_revision_stale',
      'settings_review_stale',
    ].includes(reason);
    const missing = [
      'reference_sample_missing',
      'settings_snapshot_missing',
    ].includes(reason);
    if (
      cause instanceof ApiError &&
      (cause.status === 401 ||
        (cause.status === 403 && reason !== 'step_up_required') ||
        ['module_unavailable', 'admin_binding_unavailable'].includes(reason))
    ) {
      clear();
      denied.value = true;
      errorKey.value = 'denied';
    } else {
      errorKey.value =
        reason === 'step_up_required'
          ? 'proof'
          : missing
            ? 'missing'
            : stale
              ? 'stale'
              : reason === 'settings_snapshot_name_or_limit'
                ? 'snapshotDuplicate'
                : 'failed';
      if (
        cause instanceof ApiError &&
        cause.status === 422 &&
        payload.errors &&
        typeof payload.errors === 'object' &&
        !Array.isArray(payload.errors)
      ) {
        const errors = wire.object(payload.errors);
        for (const key of Object.keys(errors)) {
          if (
            ['code', 'values.label', 'values.credential', 'name'].includes(key)
          )
            fieldErrors.value[key] = key;
        }
        errorKey.value = 'invalid';
      }
    }
    return stale || missing;
  }
  function reconcileSample(items: Sample[]) {
    rows.value = items;
    if (!selected.value) return;
    const current = items.find(
      (row) => row.public_id === selected.value?.public_id,
    );
    if (!current || current.code === undefined) {
      selected.value = undefined;
      code.value = '';
      sampleConflict.value = undefined;
      return;
    }
    if (
      sampleDirty.value &&
      current.code !== selected.value.code &&
      current.code !== code.value
    )
      sampleConflict.value = current;
    else {
      if (!sampleDirty.value) code.value = current.code;
      selected.value = current;
      sampleConflict.value = undefined;
    }
  }
  function reconcileSettings(current: Settings) {
    const previous = state.value;
    const labelDirty = !!previous && label.value !== previous.label;
    const overlap =
      previous &&
      labelDirty &&
      current.label !== previous.label &&
      current.label !== label.value;
    const unknownSecret =
      previous &&
      secretMode.value !== 'keep' &&
      current.review_id !== previous.review_id;
    if (overlap || unknownSecret) settingsConflict.value = current;
    else {
      if (!labelDirty) label.value = current.label;
      state.value = current;
      settingsConflict.value = undefined;
    }
  }
  async function read(older = false) {
    if (view() === 'samples')
      reconcileSample(wire.samples(await api('samples')));
    if (view() === 'settings') {
      const current = wire.settings(await api('settings'));
      const catalog = wire.snapshots(await api('snapshots'));
      reconcileSettings(current);
      snapshotRows.value = catalog;
    }
    if (view() === 'history') {
      const page = wire.history(
        await api('history', 'GET', undefined, {
          before: older ? next.value : null,
        }),
      );
      historyRows.value = older
        ? [
            ...historyRows.value,
            ...page.items.filter(
              (item) =>
                !historyRows.value.some(
                  (existing) => existing.public_id === item.public_id,
                ),
            ),
          ]
        : page.items;
      next.value = page.next;
    }
    if (view() === 'work') workRows.value = wire.work(await api('work'));
    ready.value = true;
  }
  async function perform(task: () => Promise<void>, mutation = false) {
    if (!allowed.value || busy.value) return;
    const own = epoch;
    busy.value = true;
    writing.value = mutation;
    errorKey.value = '';
    successKey.value = '';
    fieldErrors.value = {};
    attempt.value++;
    try {
      await task();
    } catch (cause) {
      if (alive && own === epoch) {
        const stale = fail(cause);
        if (stale && mutation && allowed.value) {
          review.value = undefined;
          try {
            await read();
          } catch (readFailure) {
            if (own === epoch) fail(readFailure);
          }
        }
      }
    } finally {
      if (alive && own === epoch) {
        busy.value = false;
        writing.value = false;
      }
    }
  }
  const refresh = (older = false) => perform(() => read(older));
  async function selectSample(row: Sample) {
    if (busy.value || !canEditCode.value || row.code === undefined) return;
    if (selected.value?.public_id === row.public_id) return;
    if (!(await guard.beforeLeave()) || !allowed.value) return;
    selected.value = row;
    code.value = row.code;
    sampleConflict.value = undefined;
  }
  function resolveSample(keep: boolean) {
    const current = sampleConflict.value;
    if (!current) return;
    if (!keep) code.value = current.code ?? '';
    selected.value = current;
    sampleConflict.value = undefined;
  }
  function resolveSettings(keep: boolean) {
    if (!settingsConflict.value) return;
    if (!keep) {
      label.value = settingsConflict.value.label;
      secretMode.value = 'keep';
      credential.value = '';
    }
    state.value = settingsConflict.value;
    settingsConflict.value = undefined;
    review.value = undefined;
  }
  async function saveSample() {
    if (
      !canEditCode.value ||
      !selected.value ||
      sampleConflict.value ||
      !sampleDirty.value
    )
      return;
    if (!/^[a-z][a-z0-9_-]{0,39}$/.test(code.value)) {
      fieldErrors.value = { code: 'code' };
      errorKey.value = 'invalid';
      attempt.value++;
      return;
    }
    const input = {
      public_id: selected.value.public_id,
      revision: selected.value.revision,
      code: code.value,
    };
    await perform(async () => {
      const result = await api('samples', 'PATCH', input);
      if (result.public_id !== input.public_id || result.code !== input.code)
        throw new TypeError('Invalid committed sample.');
      selected.value = { ...input, revision: wire.text(result.revision) };
      code.value = input.code;
      successKey.value = 'saved';
      await read();
    }, true);
  }
  function patch(): SettingsPatch {
    return {
      ...(label.value !== state.value?.label ? { label: label.value } : {}),
      ...(secretMode.value === 'keep'
        ? {}
        : {
            credential: secretMode.value === 'clear' ? null : credential.value,
          }),
    };
  }
  function settingsInput() {
    return JSON.stringify([
      patch(),
      state.value?.revision,
      state.value?.review_id,
    ]);
  }
  watch(settingsInput, () => {
    review.value = undefined;
  });
  watch(secretMode, (mode) => {
    if (mode !== 'replace') credential.value = '';
  });
  async function reviewSettings() {
    if (
      !writable.value ||
      !state.value ||
      settingsConflict.value ||
      !settingsDirty.value
    )
      return;
    fieldErrors.value = {};
    if (!label.value.trim() || label.value.length > 40)
      fieldErrors.value['values.label'] = 'values.label';
    if (
      secretMode.value === 'replace' &&
      (!credential.value || credential.value.length > 512)
    )
      fieldErrors.value['values.credential'] = 'values.credential';
    if (Object.keys(fieldErrors.value).length) {
      errorKey.value = 'invalid';
      attempt.value++;
      return;
    }
    const values = patch(),
      original = settingsInput(),
      current = state.value;
    await perform(async () => {
      const result = await api('settings/review', 'POST', {
        values,
        revision: current.revision,
        review_id: current.review_id,
      });
      const token = wire.text(result.review);
      wire.object(result.changes);
      if (original === settingsInput())
        review.value = {
          token,
          input: original,
          patch: values,
          revision: current.revision,
          reviewId: current.review_id,
        };
    }, true);
  }
  async function protectedAction(
    key: string,
    task: () => Promise<void>,
    unchanged: () => boolean = () => true,
  ) {
    if (!writable.value) return;
    await perform(async () => {
      action.value = key;
      const own = epoch;
      if (
        !(await proof.request()) ||
        own !== epoch ||
        !writable.value ||
        !unchanged()
      )
        return;
      await task();
    }, true);
  }
  async function publish() {
    const current = review.value;
    if (!current || settingsConflict.value || current.input !== settingsInput())
      return;
    await protectedAction(
      'app.reference.settings.publish',
      async () => {
        const result = await api('settings/publish', 'POST', {
          values: current.patch,
          revision: current.revision,
          review_id: current.reviewId,
          review: current.token,
        });
        wire.text(result.operation);
        credential.value = '';
        secretMode.value = 'keep';
        review.value = undefined;
        if (result.status !== 'succeeded') {
          errorKey.value = 'failed';
          await read();
          return;
        }
        // Only this submitted draft becomes clean; later edits are blocked by the owning lane.
        state.value = undefined;
        settingsConflict.value = undefined;
        successKey.value = 'saved';
        await read();
      },
      () => current.input === settingsInput() && review.value === current,
    );
  }
  async function capture() {
    if (!writable.value || !state.value || settingsConflict.value) return;
    const name = snapshotName.value.trim(),
      current = state.value;
    if (!name || name.length > 80 || /\p{C}/u.test(name)) {
      fieldErrors.value = { name: 'name' };
      errorKey.value = 'invalid';
      attempt.value++;
      return;
    }
    await perform(async () => {
      const result = await api('snapshots', 'POST', {
        name,
        revision: current.revision,
        review_id: current.review_id,
      });
      if (result.status !== 'committed')
        throw new TypeError('Snapshot was not committed.');
      snapshotName.value = '';
      successKey.value = 'saved';
      await read();
    }, true);
  }
  async function loadSnapshot(row: Snapshot) {
    if (
      !writable.value ||
      busy.value ||
      !(await guard.beforeLeave()) ||
      !writable.value
    )
      return;
    await perform(async () => {
      const result = await api('snapshots', 'GET', undefined, {
        public_id: row.public_id,
      });
      label.value = wire.text(wire.object(result.values).label);
      review.value = undefined;
    }, true);
  }
  async function deleteSnapshot(row: Snapshot) {
    const current = state.value;
    if (!current) return;
    await protectedAction(
      'app.reference.settings.snapshot_delete',
      async () => {
        const result = await api('snapshots', 'DELETE', {
          public_id: row.public_id,
          revision: current.revision,
          review_id: current.review_id,
        });
        if (result.status !== 'committed')
          throw new TypeError('Snapshot was not deleted.');
        successKey.value = 'saved';
        await read();
      },
      () => state.value === current,
    );
  }
  const dispatch = () =>
    protectedAction('app.reference.work.dispatch', async () => {
      const result = await api('work', 'POST');
      if (result.status !== 'admitted')
        throw new TypeError('Work was not admitted.');
      wire.text(result.work_id);
      successKey.value = 'admitted';
      await read();
    });
  watch(
    () => JSON.stringify([access(), view()]),
    () => {
      clear();
      denied.value = false;
      if (allowed.value) void refresh();
    },
    { immediate: true, flush: 'sync' },
  );
  onScopeDispose(() => {
    alive = false;
    clear();
  });
  return {
    rows,
    state,
    snapshotRows,
    historyRows,
    workRows,
    selected,
    code,
    label,
    credential,
    secretMode,
    snapshotName,
    next,
    sampleConflict,
    settingsConflict,
    review,
    busy,
    writing,
    ready,
    allowed,
    writable,
    canEditCode,
    dirty,
    sampleDirty,
    settingsDirty,
    error: computed(() => (errorKey.value ? copy.value[errorKey.value] : '')),
    success: computed(() =>
      successKey.value ? copy.value[successKey.value] : '',
    ),
    fieldErrors,
    attempt,
    leaveOpen,
    answerLeave,
    beforeLeave: () =>
      writing.value ? Promise.resolve(false) : guard.beforeLeave(),
    refresh,
    selectSample,
    resolveSample,
    resolveSettings,
    saveSample,
    reviewSettings,
    publish,
    capture,
    loadSnapshot,
    deleteSnapshot,
    dispatch,
  };
}
export type ReferenceWorkspaceController = ReturnType<
  typeof useReferenceWorkspace
>;
