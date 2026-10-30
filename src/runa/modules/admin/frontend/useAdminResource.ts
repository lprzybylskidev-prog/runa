import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { RequestCancelledError, request } from '@/runa/http';
import type {
  AdminError,
  AdminPageController,
  AdminReadQuery,
  AdminReadResult,
  AdminResourceController,
  AdminState,
} from './contracts';
import { adminPath, decodeRead } from './decode';
import { adminFailure, refuseAdmin, sessionStamp } from './useAdmin';
import { pageOwners } from './pageContext';

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
  const busy = ref(false),
    status = ref<AdminState>('ready');
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
  function invalidate() {
    ++epoch;
    abort.abort();
    result.value = undefined;
    error.value = undefined;
    busy.value = false;
    status.value = 'ready';
    pending = undefined;
    pendingKey = undefined;
  }
  function read(query: AdminReadQuery): Promise<void> {
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
        if (current()) {
          busy.value = false;
          pending = undefined;
          pendingKey = undefined;
        }
      }
    });
    return pending;
  }
  const stop = watch(
    [
      () => page.page.value,
      () => page.state.value,
      () => admin && sessionStamp(admin.session),
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
      account = nextAccount;
      if (nextPage) pageIdentity = nextPage;
      invalidate();
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
    invalidate();
    lastQuery = undefined;
    stop();
    document.removeEventListener('visibilitychange', visible);
  });
  return {
    state: computed(() =>
      page.state.value !== 'ready'
        ? page.state.value
        : !descriptor.value || descriptor.value.diagnostic
          ? 'unavailable'
          : status.value,
    ),
    result,
    error,
    busy: computed(() => busy.value),
    read,
    async command() {
      // Write coordination is a separate delivery. Do not issue or replay a business write.
      error.value = {
        reason: 'admin_operation_unavailable',
        message: 'This operation is currently unavailable.',
      };
      return undefined;
    },
  };
}
