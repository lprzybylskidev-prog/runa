import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { useApplicationSession, useOperationProof } from '@/runa/auth';
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
import { navigateWithGuards } from './runtime';

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
          message: 'Confirm your password to enter the administrator panel.',
        },
      };
    if (error.status === 401 || error.status === 403)
      return {
        state: 'forbidden',
        error: {
          reason,
          message: 'Administrator panel access is not available.',
        },
      };
    if (error.status === 404 || error.status === 409 || error.status === 503)
      return {
        state: 'unavailable',
        error: {
          reason,
          message: 'The administrator panel is currently unavailable.',
        },
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
    session.session.value?.restrictions,
    session.authorityRevision.value,
    session.paused.value,
    session.loading.value,
    session.pending.value,
  ]);
}
/** Stable presentation identity; authorityRevision still fences every request. */
export function adminPresentationIdentity(
  session: ApplicationSessionController,
): string | null {
  return admitted(session)
    ? JSON.stringify([
        session.session.value?.public_id,
        session.session.value?.session.public_id,
      ])
    : null;
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
  const proofIdentity = () =>
    admitted(session)
      ? JSON.stringify([
          session.session.value?.public_id,
          session.session.value?.session.public_id,
        ])
      : undefined;
  const entryProof = useOperationProof({
    identity: proofIdentity,
    action: () => 'runa.admin.enter',
    requirement: () => 'password',
  });
  let epoch = 0,
    alive = true;
  let abort = new AbortController();
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
    entryProof.reset();
  }
  function fail(cause: unknown) {
    ++epoch;
    abort.abort();
    clear();
    const failure = adminFailure(cause);
    error.value = failure.error;
    status.value = failure.state;
    if (failure.state === 'proof_required') void entryProof.begin();
  }
  async function refresh() {
    await readBootstrap(status.value === 'ready' && admitted(session));
  }
  async function readBootstrap(retainPresentation: boolean) {
    const own = ++epoch,
      stamp = sessionStamp(session);
    abort.abort();
    abort = new AbortController();
    if (!retainPresentation) {
      bootstrap.value = undefined;
      error.value = undefined;
      status.value = 'loading';
    }
    if (!admitted(session)) return;
    try {
      const result = decodeBootstrap(
        await request('/admin', { signal: abort.signal }),
      );
      if (!alive || own !== epoch || stamp !== sessionStamp(session)) return;
      entryProof.reset();
      if (JSON.stringify(bootstrap.value) !== JSON.stringify(result))
        bootstrap.value = result;
      status.value = 'ready';
    } catch (cause) {
      if (alive && own === epoch && stamp === sessionStamp(session)) {
        if (
          retainPresentation &&
          status.value === 'proof_required' &&
          adminFailure(cause).state === 'proof_required'
        )
          return;
        fail(cause);
      }
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
    return navigateWithGuards(controller, () => {
      go(href);
      path.value = href;
    });
  }
  async function confirmEntry() {
    if (state.value !== 'proof_required') return;
    await entryProof.confirm();
    if (entryProof.confirmed.value) await refresh();
  }
  function cancelEntry() {
    entryProof.reset();
    go('/app');
  }
  const controller: AdminController = {
    session,
    state,
    bootstrap,
    location,
    error,
    entryProof,
    refresh,
    navigate,
    confirmEntry,
    cancelEntry,
  };
  refusals.set(controller, fail);
  let observedIdentity: string | undefined;
  const stop = watch(
    () => sessionStamp(session),
    () => {
      const currentIdentity = proofIdentity();
      const retainPresentation =
        !!currentIdentity &&
        currentIdentity === observedIdentity &&
        (status.value === 'proof_required' || status.value === 'ready');
      observedIdentity = currentIdentity;
      if (!retainPresentation) clear();
      void readBootstrap(retainPresentation);
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
    abort.abort();
    clear();
    stop();
    refusals.delete(controller);
    window.removeEventListener('popstate', popstate);
  });
  return controller;
}
