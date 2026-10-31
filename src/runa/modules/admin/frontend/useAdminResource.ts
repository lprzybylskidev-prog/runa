import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { ApiError, RequestCancelledError, request } from '@/runa/http';
import type {
  AdminError,
  AdminPageController,
  AdminReadQuery,
  AdminReadResult,
  AdminResourceController,
  AdminState,
  AdminWrite,
} from './contracts';
import {
  adminPath,
  decodeRead,
  decodeCommandResult,
  decodeCommandReview,
  object,
} from './decode';
import { adminFailure, refuseAdmin, sessionStamp } from './useAdmin';
import { pageOwners } from './pageContext';
import { pageRuntime } from './runtime';
import { useAdminInitialReadiness } from './initialReadiness';
import { writeFailure } from './writeFailure';

function readQuery(query: AdminReadQuery) {
  const params: Record<string, string | number | boolean | null | undefined> = {
    kind: query.kind,
    public_id: query.public_id,
    cursor: query.cursor,
    limit: query.limit,
  };
  if (query.kind === 'options') params.field = query.field;
  if (query.filters) params.filters = JSON.stringify(query.filters);
  if (query.kind !== 'options')
    query.sort?.forEach((sort, index) => {
      params[`sort[${index}][key]`] = sort.key;
      params[`sort[${index}][direction]`] = sort.direction;
    });
  return params;
}

export function useAdminResource(
  page: AdminPageController,
  section: string,
): AdminResourceController {
  const result = shallowRef<AdminReadResult>();
  const error = shallowRef<AdminError>();
  const writeError = shallowRef<AdminError>();
  const runtime = pageRuntime(page);
  let writing = false,
    writeEpoch = 0;
  let writeAbort = new AbortController();
  const busy = ref(false),
    status = ref<AdminState>('ready');
  useAdminInitialReadiness(() => !busy.value);
  const admin = pageOwners.get(page);
  const descriptor = computed(() =>
    page.page.value?.sections.find((s) => s.key === section),
  );
  let alive = true,
    epoch = 0,
    abort = new AbortController();
  let lastQuery: AdminReadQuery | undefined, pending: Promise<void> | undefined;
  let pendingKey: string | undefined;
  const accountIdentity = () =>
    admin &&
    JSON.stringify([
      admin.session.session.value?.public_id,
      admin.session.session.value?.session.public_id,
    ]);
  let account = accountIdentity();
  let pageIdentity: string | undefined;
  function identity() {
    const data = page.page.value;
    return JSON.stringify([
      data?.owner,
      data?.key,
      data?.generation,
      data?.revision,
      page.state.value,
      admin && sessionStamp(admin.session),
    ]);
  }
  function invalidate(retain = false) {
    ++epoch;
    abort.abort();
    if (!retain) result.value = undefined;
    error.value = undefined;
    busy.value = false;
    status.value = 'ready';
    pending = undefined;
    pendingKey = undefined;
  }
  function read(
    query: AdminReadQuery,
    options: { signal?: AbortSignal } = {},
  ): Promise<void> {
    if (options.signal?.aborted) return Promise.resolve();
    // Own the query snapshot so caller edits cannot change the meaning of a late response.
    const snapshot: AdminReadQuery = {
      ...query,
      ...(query.filters ? { filters: { ...query.filters } } : {}),
      ...(query.kind !== 'options' && query.sort
        ? { sort: query.sort.map((s) => ({ ...s })) }
        : {}),
    };
    const key = JSON.stringify(snapshot);
    if (pending && key === pendingKey) return pending;
    const data = page.page.value,
      projection = descriptor.value;
    const stamp = identity(),
      own = ++epoch;
    abort.abort();
    abort = new AbortController();
    const signal = abort.signal;
    const externalAbort = () => {
      if (own === epoch) {
        lastQuery = undefined;
        invalidate();
      }
    };
    error.value = undefined;
    status.value = 'ready';
    if (
      !alive ||
      page.state.value !== 'ready' ||
      !data ||
      !projection ||
      projection.diagnostic
    ) {
      result.value = undefined;
      busy.value = false;
      status.value = 'unavailable';
      return Promise.resolve();
    }
    lastQuery = snapshot;
    pageIdentity = data.owner + ':' + data.key;
    pendingKey = key;
    busy.value = true;
    options.signal?.addEventListener('abort', externalAbort, { once: true });
    const current = () => alive && own === epoch && stamp === identity();
    pending = Promise.resolve().then(async () => {
      try {
        if (!current()) return;
        if (
          snapshot.kind === 'options' &&
          !projection.fields.some((f) => f.key === snapshot.field && f.lookup)
        )
          throw new TypeError('Admin lookup unavailable.');
        const response = await request(
          adminPath({ owner: data.owner, page: data.key }) +
            '/sections/' +
            encodeURIComponent(section),
          { signal },
          readQuery(snapshot),
        );
        if (!current()) return;
        result.value = decodeRead(response, snapshot, projection);
        status.value = 'ready';
      } catch (cause) {
        if (
          !current() ||
          signal.aborted ||
          cause instanceof RequestCancelledError
        )
          return;
        result.value = undefined;
        const failure = adminFailure(cause);
        error.value = failure.error;
        status.value = failure.state;
        if (
          admin &&
          (failure.state === 'proof_required' || failure.state === 'forbidden')
        )
          refuseAdmin(admin, cause);
      } finally {
        options.signal?.removeEventListener('abort', externalAbort);
        if (current()) {
          busy.value = false;
          pending = undefined;
          pendingKey = undefined;
        }
      }
    });
    return pending;
  }
  async function send<T>(
    input: AdminWrite,
    preview: boolean,
    decode: (value: unknown) => T,
  ): Promise<T | undefined> {
    const projection = descriptor.value,
      data = page.page.value;
    const command = projection?.commands.find(
      (command) => command.key === input.command,
    );
    if (
      !alive ||
      !data ||
      page.state.value !== 'ready' ||
      runtime.busy.value ||
      !projection ||
      projection.readonly ||
      projection.diagnostic ||
      !command?.available ||
      command.mode !== 'independent' ||
      Object.keys(input.values).some(
        (key) =>
          !command.inputs.includes(key) ||
          !projection.fields.some(
            (field) => field.key === key && !field.readonly,
          ),
      ) ||
      (!preview && command.destructive && !input.confirmed) ||
      (!preview && command.preview_required && !input.review)
    ) {
      writeError.value = {
        reason: 'admin_operation_unavailable',
        message: 'This operation is currently unavailable.',
      };
      return undefined;
    }
    const own = ++writeEpoch,
      stamp = identity();
    const current = () => alive && own === writeEpoch && stamp === identity();
    writeAbort.abort();
    writeAbort = new AbortController();
    runtime.busy.value = writing = true;
    writeError.value = undefined;
    try {
      const body = JSON.stringify(input);
      if (new TextEncoder().encode(body).length > 262144)
        throw new TypeError('Command exceeds limit.');
      const response = await request(
        adminPath({ owner: data.owner, page: data.key }) +
          '/sections/' +
          encodeURIComponent(section) +
          (preview ? '/preview' : '/commands'),
        {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body,
          signal: writeAbort.signal,
        },
      );
      if (!current()) return undefined;
      const outcome = decode(response);
      if (!preview) runtime.changed.value++;
      return outcome;
    } catch (cause) {
      if (!current()) return undefined;
      writeError.value = writeFailure(cause, command.inputs);
      if (cause instanceof ApiError) {
        let entry = false;
        try {
          entry = object(cause.payload).scope === 'entry';
        } catch {
          /* No payload trust. */
        }
        if (admin && (cause.status === 401 || entry)) refuseAdmin(admin, cause);
        if (cause.status === 409) runtime.changed.value++;
      }
      return undefined;
    } finally {
      if (own === writeEpoch) {
        runtime.busy.value = false;
        writing = false;
      }
    }
  }
  let previousProjection = page.page.value;
  const stop = watch(
    [
      () => page.page.value,
      () => page.state.value,
      () => admin && sessionStamp(admin.session),
      runtime.changed,
    ],
    () => {
      const nextAccount = accountIdentity(),
        data = page.page.value;
      const nextPage = data && data.owner + ':' + data.key;
      // Queries can themselves contain protected subjects/filters. Never carry them
      // into a different account/session or a different owning page.
      if (
        nextAccount !== account ||
        (nextPage && pageIdentity && nextPage !== pageIdentity)
      )
        lastQuery = undefined;
      if (
        nextAccount !== account ||
        page.state.value !== 'ready' ||
        (nextPage && pageIdentity && nextPage !== pageIdentity)
      ) {
        ++writeEpoch;
        writeAbort.abort();
        writeError.value = undefined;
        if (writing) {
          writing = false;
          runtime.busy.value = false;
        }
      }
      const retain =
        nextAccount === account &&
        page.state.value === 'ready' &&
        data === previousProjection;
      previousProjection = data;
      account = nextAccount;
      if (nextPage) pageIdentity = nextPage;
      invalidate(retain);
      if (lastQuery && page.state.value === 'ready') void read(lastQuery);
    },
    { flush: 'sync' },
  );
  function visible() {
    if (
      document.visibilityState === 'visible' &&
      lastQuery &&
      page.state.value === 'ready'
    )
      void read(lastQuery);
  }
  document.addEventListener('visibilitychange', visible);
  onScopeDispose(() => {
    alive = false;
    ++writeEpoch;
    writeAbort.abort();
    if (writing) runtime.busy.value = false;
    invalidate();
    lastQuery = undefined;
    stop();
    document.removeEventListener('visibilitychange', visible);
  });
  return {
    clear: () => {
      lastQuery = undefined;
      invalidate();
    },
    state: computed(() =>
      page.state.value !== 'ready'
        ? page.state.value
        : !descriptor.value || descriptor.value.diagnostic
          ? 'unavailable'
          : status.value,
    ),
    result,
    error: computed(() => writeError.value ?? error.value),
    busy: computed(() => busy.value || runtime.busy.value),
    read,
    command: (input) =>
      send(input, false, (response) => {
        const outcome = decodeCommandResult(response, descriptor.value!);
        if (
          input.public_id &&
          outcome.record &&
          outcome.record.public_id !== input.public_id
        )
          throw new TypeError('Command subject changed.');
        return outcome;
      }),
    preview: (input) =>
      send(input, true, (response) =>
        decodeCommandReview(response, descriptor.value!),
      ),
  };
}
