import { computed, onScopeDispose, ref, shallowRef, watch } from 'vue';
import { ApiError, request } from '@/runa/http';
import { useDirtyGuard } from '@/runa/ui';
import type { AdminController, AdminJson } from './contracts';
import { object } from './decode';
import {
  refuseAdmin,
  sessionStamp,
  adminPresentationIdentity,
} from './useAdmin';
import { navigationGuards } from './runtime';

export type AdminOperationView =
  | 'application'
  | 'capabilities'
  | 'diagnostics'
  | 'module'
  | 'settings'
  | 'integrations'
  | 'work'
  | 'work.status'
  | 'modules.status'
  | 'settings.status';
export type AdminOperationCommand =
  | 'application.update'
  | 'health.diagnose'
  | 'health.configure'
  | 'modules.preview'
  | 'modules.apply'
  | 'modules.resume'
  | 'modules.cancel'
  | 'settings.update'
  | 'settings.recover'
  | 'integrations.configure'
  | 'work.cancel';
export type AdminOperationData = Record<string, AdminJson>;

/** One write lane, explicit retries only, session fencing and guarded draft navigation. */
export function useAdminOperations(admin: AdminController) {
  const refreshing = ref(false);
  const busy = ref(false),
    dirty = ref(false),
    conflict = ref(false);
  const error = ref('');
  const confirmation = ref<string>();
  const changed = ref(0),
    contextRevision = ref(0);
  const snapshot = shallowRef<AdminOperationData>();
  let reading = false;
  let epoch = 0,
    alive = true,
    abort = new AbortController();
  let answer: ((accepted: boolean) => void) | undefined;
  const stamp = () => sessionStamp(admin.session);
  function confirm(accepted: boolean) {
    confirmation.value = undefined;
    const resolve = answer;
    answer = undefined;
    resolve?.(accepted);
  }
  function ask(message: string): Promise<boolean> {
    if (!alive || answer) return Promise.resolve(false);
    confirmation.value = message;
    return new Promise((resolve) => {
      answer = resolve;
    });
  }
  const guard = useDirtyGuard({
    dirty,
    confirmLeave: () => ask('Discard unsaved operational settings?'),
  });
  const guards = navigationGuards.get(admin) ?? new Set();
  const navigation = async (action: () => void | Promise<void>) =>
    !busy.value && guard.requestLeave(action);
  guards.add(navigation);
  navigationGuards.set(admin, guards);

  async function call(
    operation: AdminOperationView | AdminOperationCommand,
    input: AdminOperationData,
    write: boolean,
  ): Promise<AdminOperationData | undefined> {
    if (!alive || busy.value || admin.state.value !== 'ready') return;
    if (reading) {
      if (!write) return;
      // A deliberate write supersedes an optional read; neither is replayed.
      epoch++;
      abort.abort();
      abort = new AbortController();
    }
    const own = epoch,
      context = stamp();
    reading = !write;
    refreshing.value = !write;
    busy.value = write;
    error.value = '';
    try {
      const query: Record<string, string | number> = {};
      if (!write)
        for (const [key, value] of Object.entries(input)) {
          if (typeof value !== 'string' && typeof value !== 'number')
            throw new TypeError('Invalid query.');
          query[key] = value;
        }
      const response = object(
        object(
          await request(
            '/admin/operations/' + operation,
            {
              ...(write
                ? {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(input),
                  }
                : {}),
              signal: abort.signal,
            },
            query,
          ),
        ).data,
      ) as AdminOperationData;
      if (!alive || own !== epoch || context !== stamp()) return;
      if (write && operation !== 'modules.preview') {
        changed.value++;
        conflict.value = false;
      }
      return response;
    } catch (failure) {
      if (!alive || own !== epoch || context !== stamp()) return;
      const settingsFailure =
        (operation === 'application' || operation === 'application.update') &&
        failure instanceof ApiError &&
        failure.status === 503 &&
        failure.payload !== null &&
        typeof failure.payload === 'object' &&
        'scope' in failure.payload &&
        failure.payload.scope === 'operation' &&
        'reason' in failure.payload &&
        typeof failure.payload.reason === 'string' &&
        [
          'application_settings_unavailable',
          'application_settings_audit_unavailable',
        ].includes(failure.payload.reason);
      if (
        failure instanceof ApiError &&
        (failure.status === 401 ||
          failure.status === 403 ||
          (failure.status === 503 && !settingsFailure))
      )
        refuseAdmin(admin, failure);
      conflict.value = failure instanceof ApiError && failure.status === 409;
      error.value = settingsFailure
        ? 'Application settings are temporarily unavailable. Your draft is retained.'
        : conflict.value
          ? 'State changed or the operation could not complete. Refresh and review before another explicit attempt.'
          : 'The operation could not be completed. Check your authority and input.';
    } finally {
      if (own === epoch) {
        busy.value = false;
        reading = false;
        refreshing.value = false;
      }
    }
  }
  const read = (view: AdminOperationView, input: AdminOperationData = {}) =>
    call(view, input, false);
  async function execute(
    command: AdminOperationCommand,
    input: AdminOperationData,
    destructive = false,
  ) {
    const own = epoch,
      context = stamp();
    // Freeze reviewed input before confirmation; changed drafts cannot alter the accepted command.
    const submitted = JSON.parse(JSON.stringify(input)) as AdminOperationData;
    if (
      destructive &&
      !(await ask('Confirm this operation? Existing work may be interrupted.'))
    )
      return;
    if (own !== epoch || context !== stamp() || busy.value) return;
    return call(command, submitted, true);
  }
  async function refresh(filter = 'enabled') {
    const own = epoch;
    const next = await read('diagnostics', { filter });
    if (next && own === epoch && alive) snapshot.value = next;
  }
  const presentationIdentity = () =>
    admin.state.value === 'ready'
      ? adminPresentationIdentity(admin.session)
      : null;
  let observedIdentity = presentationIdentity();
  watch(
    [stamp, () => admin.state.value],
    () => {
      epoch++;
      abort.abort();
      abort = new AbortController();
      busy.value = false;
      reading = false;
      refreshing.value = false;
      const nextIdentity = presentationIdentity();
      if (!nextIdentity || nextIdentity !== observedIdentity) {
        snapshot.value = undefined;
        error.value = '';
        dirty.value = false;
        conflict.value = false;
        contextRevision.value++;
      }
      observedIdentity = nextIdentity;
      changed.value++;
      confirm(false);
    },
    { flush: 'sync' },
  );
  function click(event: MouseEvent) {
    if (
      !dirty.value ||
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.ctrlKey ||
      event.shiftKey ||
      event.altKey
    )
      return;
    const anchor =
      event.target instanceof Element ? event.target.closest('a[href]') : null;
    if (
      !(anchor instanceof HTMLAnchorElement) ||
      (anchor.target && anchor.target !== '_self') ||
      anchor.hasAttribute('download') ||
      anchor.href === location.href
    )
      return;
    event.preventDefault();
    if (!busy.value)
      void guard.requestLeave(() => {
        window.location.assign(anchor.href);
      });
  }
  document.addEventListener('click', click, true);
  onScopeDispose(() => {
    alive = false;
    epoch++;
    abort.abort();
    confirm(false);
    guards.delete(navigation);
    document.removeEventListener('click', click, true);
  });
  return {
    busy: computed(() => busy.value),
    refreshing: computed(() => refreshing.value),
    dirty,
    conflict,
    error,
    confirmation,
    snapshot,
    changed,
    contextRevision,
    read,
    execute,
    refresh,
    confirm,
    beforeLeave: async () => !busy.value && guard.beforeLeave(),
  };
}
export type AdminOperationsController = ReturnType<typeof useAdminOperations>;
