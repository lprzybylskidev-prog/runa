import {
  request,
  observeRequests,
  ApiError,
  RequestCancelledError,
} from './http';
import type { PreferenceDefinition, PreferenceStore } from './preferenceStore';
import type { PreferenceScope } from '../runa/ui/preferences';

type Entry = { version: number; value: unknown; stored: boolean };
type Status = {
  pending: boolean;
  error: 'conflict' | 'save_failed' | 'load_failed' | null;
};
type Transport = (
  input: Record<string, unknown>,
  signal: AbortSignal,
) => Promise<unknown>;
const transport: Transport = (input, signal) =>
  request('/runa/auth/preferences', {
    method: 'POST',
    signal,
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(input),
  });
function object(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null && !Array.isArray(value);
}
function envelope(value: unknown): Record<string, unknown> {
  if (!object(value) || !object(value.data))
    throw new Error('Invalid preferences response.');
  return value.data;
}
function serverScope(scope: PreferenceScope, control: string) {
  return {
    application: scope.applicationId,
    module: ['display', 'shell'].includes(scope.moduleKey)
      ? 'runa.auth'
      : scope.moduleKey,
    view: scope.viewKey,
    key: scope.moduleKey === 'display' ? control.replace('runa.', '') : control,
  };
}
function id(scope: ReturnType<typeof serverScope>) {
  return [scope.application, scope.module, scope.view, scope.key].join(':');
}

/** Account values are memory-only. A generation fences queued work and late responses. */
export function createAccountPreferenceStore(
  guest: PreferenceStore,
  send: Transport = transport,
) {
  let account: string | null = null;
  let application = 'runa';
  let generation = 0;
  let revision = 0;
  let ready = false;
  let legacyClaimed = true;
  let abort = new AbortController();
  let queue = Promise.resolve();
  const entries = new Map<string, Entry>();
  const listeners = new Set<() => void>();
  const statusListeners = new Set<() => void>();
  let status: Status = { pending: false, error: null };
  let pending = 0;
  const notify = () => listeners.forEach((listener) => listener());
  function updateStatus(error: Status['error'] = null) {
    status = { pending: pending > 0, error };
    statusListeners.forEach((listener) => listener());
  }
  function clear(next: string | null, app: string) {
    generation++;
    abort.abort();
    abort = new AbortController();
    account = next;
    application = app;
    entries.clear();
    ready = false;
    revision = 0;
    legacyClaimed = true;
    pending = 0;
    queue = Promise.resolve();
    updateStatus();
    notify();
  }
  function snapshot(data: Record<string, unknown>) {
    if (
      data.account !== account ||
      !Number.isSafeInteger(data.revision) ||
      !Array.isArray(data.values)
    )
      throw new Error('Invalid account snapshot.');
    const next = new Map<string, Entry>();
    for (const value of data.values) {
      if (
        !object(value) ||
        typeof value.scope !== 'string' ||
        ![`${application}:`, 'runa:'].some((prefix) =>
          String(value.scope).startsWith(prefix),
        ) ||
        !Number.isSafeInteger(value.version) ||
        typeof value.stored !== 'boolean' ||
        !('value' in value)
      )
        throw new Error('Invalid preference entry.');
      next.set(value.scope, {
        version: Number(value.version),
        value: value.value,
        stored: value.stored,
      });
    }
    entries.clear();
    next.forEach((value, key) => entries.set(key, value));
    revision = Number(data.revision);
    legacyClaimed = data.legacy_claimed === true;
    ready = true;
    notify();
  }
  async function connect(next: string, app = 'runa') {
    clear(next, app);
    const epoch = generation,
      signal = abort.signal;
    pending++;
    updateStatus();
    try {
      const data = envelope(
        await send({ operation: 'snapshot', application }, signal),
      );
      if (epoch !== generation) return;
      snapshot(data);
      pending--;
      updateStatus();
    } catch (error) {
      if (epoch !== generation) return;
      pending--;
      updateStatus(
        error instanceof RequestCancelledError ? null : 'load_failed',
      );
      throw error;
    }
  }
  function schedule(input: Record<string, unknown>) {
    const epoch = generation,
      target = account,
      app = application,
      signal = abort.signal;
    pending++;
    updateStatus();
    const task = queue
      .then(async () => {
        if (epoch !== generation || target === null) return;
        if (!ready) throw new Error('Account preferences unavailable.');
        const data = envelope(
          await send({ ...input, application: app, revision }, signal),
        );
        if (epoch !== generation) return;
        if (data.account !== target || !Number.isSafeInteger(data.revision))
          throw new Error('Invalid preference result.');
        revision = Number(data.revision);
        if (input.operation === 'write') {
          if (
            data.scope !== id(input as ReturnType<typeof serverScope>) ||
            !Number.isSafeInteger(data.version)
          )
            throw new Error('Invalid preference value.');
          entries.set(String(data.scope), {
            version: Number(data.version),
            value: data.value,
            stored: true,
          });
          notify();
        } else {
          // Reset/adoption reload authoritative defaults; never reuse a local legacy value.
          const fresh = envelope(
            await send({ operation: 'snapshot', application: app }, signal),
          );
          if (epoch !== generation) return;
          snapshot(fresh);
        }
        pending--;
        updateStatus();
      })
      .catch((error: unknown) => {
        if (epoch !== generation) return;
        // Do not replay a stale write against a newer revision.
        ready = false;
        pending--;
        updateStatus(
          error instanceof RequestCancelledError
            ? null
            : error instanceof ApiError && error.status === 409
              ? 'conflict'
              : 'save_failed',
        );
        throw error;
      });
    queue = task.catch(() => {});
    return task;
  }
  const store: PreferenceStore = {
    read<T>(
      scope: PreferenceScope,
      control: string,
      definition: PreferenceDefinition<T>,
    ): T {
      if (account === null) return guest.read(scope, control, definition);
      const entry = [application, 'runa'].includes(scope.applicationId)
        ? entries.get(id(serverScope(scope, control)))
        : undefined;
      if (!entry) return definition.defaultValue;
      const value =
        entry.version === definition.version
          ? entry.value
          : definition.migrate?.(entry.value, entry.version);
      return definition.validate(value)
        ? structuredClone(value)
        : definition.defaultValue;
    },
    write(scope, control, definition, value) {
      if (!definition.validate(value))
        throw new TypeError('Invalid preference.');
      if (account === null) {
        guest.write(scope, control, definition, value);
        return;
      }
      const entry = entries.get(id(serverScope(scope, control)));
      if (entry && JSON.stringify(entry.value) === JSON.stringify(value))
        return;
      if (![application, 'runa'].includes(scope.applicationId)) {
        updateStatus('save_failed');
        return;
      }
      void schedule({
        operation: 'write',
        ...serverScope(scope, control),
        value,
      }).catch(() => {});
    },
    reset(scope, control) {
      if (account === null) {
        guest.reset(scope, control);
        return;
      }
      void schedule({
        operation: 'reset',
        ...serverScope(scope, control),
      }).catch(() => {});
    },
    subscribe(listener) {
      listeners.add(listener);
      return () => listeners.delete(listener);
    },
  };
  return {
    store,
    connect,
    disconnect: () => clear(null, application),
    hold: (next: string, app = 'runa') => clear(next, app),
    signedIn: () => account !== null,
    currentAccount: () => account,
    application: () => application,
    setApplication: (app: string) => {
      if (application !== app) {
        const previous = account;
        clear(previous, app);
        if (previous) void connect(previous, app).catch(() => {});
      }
    },
    status: () => status,
    reportLoadFailure: () => updateStatus('load_failed'),
    subscribeStatus: (listener: () => void) => {
      statusListeners.add(listener);
      return () => statusListeners.delete(listener);
    },
    subscribe: store.subscribe!,
    flush: () => queue,
    reset: (module: string | null = null) =>
      schedule({ operation: 'reset', module }),
    /** Caller has explicitly confirmed these unclaimed settings belong to this account. */
    adoptGuest: (
      values: readonly {
        module: 'runa.auth';
        view: 'global' | 'navigation';
        key: 'locale' | 'theme' | 'collapsed' | 'expanded';
        value: unknown;
      }[],
    ) => {
      if (account === null || legacyClaimed)
        return Promise.reject(new Error('Guest adoption unavailable.'));
      return schedule({ operation: 'adopt', values });
    },
  };
}

/** Bind once from the shared store; the later Auth screens use the same HTTP lifecycle. */
export function bindPreferenceSession(
  store: ReturnType<typeof createAccountPreferenceStore>,
) {
  let authGeneration = 0;
  observeRequests((event) => {
    const boundary = [
      '/runa/auth/login',
      '/runa/auth/logout',
      '/runa/auth/session',
    ].includes(event.path);
    if (event.phase === 'start' && !boundary) return authGeneration;
    if (!boundary && event.status !== 401) return;
    if (event.phase === 'start' && boundary) {
      authGeneration++;
      const current = store.currentAccount();
      if (event.path === '/runa/auth/logout' && current)
        store.hold(current, store.application());
      else store.disconnect();
      return authGeneration;
    }
    if (event.phase !== 'end') return;
    if (event.context !== authGeneration) return;
    if (
      event.path === '/runa/auth/logout' &&
      event.status !== 200 &&
      event.status !== 401
    ) {
      const current = store.currentAccount();
      if (current)
        void store.connect(current, store.application()).catch(() => {});
      return;
    }
    if (event.status === 401 || event.path === '/runa/auth/logout') {
      store.disconnect();
      return;
    }
    if (
      event.status !== 200 ||
      !object(event.payload) ||
      !object(event.payload.data)
    )
      return;
    const data = event.payload.data;
    if (
      typeof data.public_id === 'string' &&
      Array.isArray(data.restrictions)
    ) {
      if (data.restrictions.length === 0)
        void store.connect(data.public_id, store.application()).catch(() => {});
      else store.hold(data.public_id, store.application());
    }
  });
}
