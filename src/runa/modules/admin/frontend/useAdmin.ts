import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { useApplicationSession } from '@/runa/auth';
import { ApiError, request } from '@/runa/http';
import type {
  ApplicationNavigate,
  ApplicationSessionController,
} from '@/runa/auth';
import type {
  AdminBootstrap,
  AdminController,
  AdminError,
  AdminLocation,
  AdminState,
} from './contracts';
import { adminPath, decodeBootstrap, object } from './decode';

export function adminFailure(error: unknown): {
  state: AdminState;
  error: AdminError;
} {
  let reason = 'admin_unavailable';
  if (error instanceof ApiError) {
    try {
      const value = object(error.payload).reason;
      if (typeof value === 'string' && /^[a-z][a-z0-9_]{0,127}$/.test(value))
        reason = value;
    } catch {
      /* Safe generic error. */
    }
    if (error.status === 403 && reason === 'step_up_required')
      return {
        state: 'proof_required',
        error: {
          reason,
          message: 'Confirm your password to enter administration.',
        },
      };
    if (error.status === 401 || error.status === 403)
      return {
        state: 'forbidden',
        error: { reason, message: 'Administration access is not available.' },
      };
    if (error.status === 404 || error.status === 409 || error.status === 503)
      return {
        state: 'unavailable',
        error: { reason, message: 'Administration is currently unavailable.' },
      };
  }
  return {
    state: 'failed',
    error: {
      reason,
      message: 'The request could not be completed. Try again.',
    },
  };
}
export function sessionStamp(session: ApplicationSessionController): string {
  return JSON.stringify([
    session.session.value?.public_id,
    session.session.value?.session.public_id,
    session.authorityRevision.value,
    session.paused.value,
    session.loading.value,
    session.pending.value,
  ]);
}
export function admitted(session: ApplicationSessionController): boolean {
  return (
    !!session.session.value &&
    session.session.value.restrictions.length === 0 &&
    !session.paused.value &&
    !session.loading.value &&
    !session.pending.value
  );
}
const refusals = new WeakMap<AdminController, (error: unknown) => void>();
export function refuseAdmin(admin: AdminController, error: unknown) {
  refusals.get(admin)?.(error);
}

export function useAdmin(
  options: {
    navigate?: ApplicationNavigate | undefined;
    session?: ApplicationSessionController | undefined;
  } = {},
): AdminController {
  const go =
    options.navigate ?? ((path: string) => window.location.assign(path));
  const session = options.session ?? useApplicationSession(go);
  const bootstrap = shallowRef<AdminBootstrap>();
  const error = shallowRef<AdminError>();
  const status = ref<AdminState>('loading');
  const path = ref(window.location.pathname);
  const password = ref(''),
    busy = ref(false);
  let epoch = 0,
    alive = true,
    proofEpoch = 0;
  let abort = new AbortController(),
    proofAbort = new AbortController();
  const state = computed<AdminState>(() =>
    admitted(session) ? status.value : 'loading',
  );
  const location = computed<AdminLocation | undefined>(() => {
    const match =
      /^\/admin\/(app|(?:app|runa)\.[a-z][a-z0-9_]*)\/([a-z][a-z0-9_]*)$/.exec(
        path.value,
      );
    return match ? { owner: match[1]!, page: match[2]! } : undefined;
  });
  function clear() {
    bootstrap.value = undefined;
    password.value = '';
  }
  function fail(cause: unknown) {
    ++epoch;
    abort.abort();
    clear();
    const failure = adminFailure(cause);
    error.value = failure.error;
    status.value = failure.state;
  }
  async function refresh() {
    const own = ++epoch,
      stamp = sessionStamp(session);
    abort.abort();
    abort = new AbortController();
    bootstrap.value = undefined;
    error.value = undefined;
    status.value = 'loading';
    if (!admitted(session)) return;
    try {
      const result = decodeBootstrap(
        await request('/admin', { signal: abort.signal }),
      );
      if (!alive || own !== epoch || stamp !== sessionStamp(session)) return;
      bootstrap.value = result;
      status.value = 'ready';
    } catch (cause) {
      if (alive && own === epoch && stamp === sessionStamp(session))
        fail(cause);
    }
  }
  async function navigate(next: AdminLocation) {
    const href = adminPath(next);
    if (
      state.value !== 'ready' ||
      !bootstrap.value?.owners.some(
        (o) => o.id === next.owner && o.pages.some((p) => p.key === next.page),
      )
    )
      return false;
    // The supplied application router owns history; dirty guards join here in point 5.
    go(href);
    path.value = href;
    return true;
  }
  async function confirmEntry() {
    if (busy.value || state.value !== 'proof_required' || !password.value)
      return;
    const own = ++proofEpoch,
      stamp = sessionStamp(session);
    busy.value = true;
    error.value = undefined;
    proofAbort.abort();
    proofAbort = new AbortController();
    try {
      const response = object(
        await request('/runa/auth/step-up', {
          method: 'POST',
          signal: proofAbort.signal,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            action: 'runa.admin.enter',
            requirement: 'password',
            current_password: password.value,
          }),
        }),
      );
      if (!alive || own !== proofEpoch || stamp !== sessionStamp(session))
        return;
      if (object(response.data).confirmed !== true)
        throw new TypeError('Entry proof was not confirmed.');
      await refresh();
    } catch (cause) {
      if (alive && own === proofEpoch && stamp === sessionStamp(session)) {
        const failure = adminFailure(cause);
        if (failure.state === 'forbidden' || failure.state === 'unavailable')
          fail(cause);
        else {
          error.value = {
            reason: 'entry_confirmation_failed',
            message: 'Password confirmation failed. Try again.',
          };
          status.value = 'proof_required';
        }
      }
    } finally {
      if (own === proofEpoch) {
        password.value = '';
        busy.value = false;
      }
    }
  }
  const controller: AdminController = {
    session,
    state,
    bootstrap,
    location,
    error,
    entryProof: { password, busy: computed(() => busy.value) },
    refresh,
    navigate,
    confirmEntry,
  };
  refusals.set(controller, fail);
  const stop = watch(
    () => sessionStamp(session),
    () => {
      ++proofEpoch;
      proofAbort.abort();
      busy.value = false;
      clear();
      void refresh();
    },
    { immediate: true, flush: 'sync' },
  );
  function popstate() {
    path.value = window.location.pathname;
  }
  window.addEventListener('popstate', popstate);
  onScopeDispose(() => {
    alive = false;
    ++epoch;
    ++proofEpoch;
    abort.abort();
    proofAbort.abort();
    clear();
    stop();
    refusals.delete(controller);
    window.removeEventListener('popstate', popstate);
  });
  return controller;
}
