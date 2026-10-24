import { RequestCancelledError } from '../../../resources/js/lib/http';
import { computed, onMounted, onScopeDispose, ref, shallowRef } from 'vue';
import { observeRequests } from '../../../resources/js/lib/http';
import { accountPreferences } from '../../../resources/js/lib/preferenceStore';
import {
  authFailure,
  authGet,
  authPost,
  data,
  decodeSession,
  record,
} from './entry';
import type { EntrySession } from './entry';
import { clearAccountDraft } from './account';

export interface ActivitySession extends EntrySession {
  session: { public_id: string; activity_at: string; expires_at: string };
  activity: {
    heartbeat_seconds: number;
    idle_minutes: number;
    warning_seconds: number;
    extension: boolean;
  };
}
function timestamp(value: string) {
  return Date.parse(/[zZ]|[+-]\d\d:?\d\d$/.test(value) ? value : value + 'Z');
}
export function activityResponse(value: unknown): ActivitySession {
  const identity = decodeSession(value),
    p = data(value),
    s = record(p.session),
    a = record(p.activity);
  if (
    typeof s.public_id !== 'string' ||
    typeof s.activity_at !== 'string' ||
    typeof s.expires_at !== 'string' ||
    !Number.isFinite(timestamp(s.activity_at)) ||
    !Number.isFinite(timestamp(s.expires_at)) ||
    ![a.heartbeat_seconds, a.idle_minutes, a.warning_seconds].every(
      (v) => Number.isFinite(v) && Number(v) >= 0,
    ) ||
    typeof a.extension !== 'boolean'
  )
    throw new TypeError('Invalid activity policy.');
  return {
    ...identity,
    session: s as unknown as ActivitySession['session'],
    activity: a as unknown as ActivitySession['activity'],
  };
}
/** One mounted boundary, no polling or write replay. Every response is fenced by its request epoch. */
export function useApplicationSession(
  navigate: (path: string) => void = (path) => window.location.assign(path),
) {
  const session = shallowRef<ActivitySession>();
  const paused = ref(false),
    loading = ref(true),
    failure = ref(''),
    pending = ref(false),
    now = ref(Date.now());
  let epoch = 0,
    alive = true,
    lastActivity = 0,
    refreshing = false;
  let abort = new AbortController();
  const remaining = computed(() =>
    session.value
      ? Math.max(
          0,
          Math.min(
            timestamp(session.value.session.expires_at),
            timestamp(session.value.session.activity_at) +
              session.value.activity.idle_minutes * 60000,
          ) - now.value,
        )
      : 0,
  );
  const warning = computed(
    () =>
      !paused.value &&
      !!session.value &&
      session.value.activity.warning_seconds > 0 &&
      remaining.value <= session.value.activity.warning_seconds * 1000,
  );
  function suspend() {
    if (paused.value) return;
    paused.value = true;
    epoch++;
    abort.abort();
    abort = new AbortController();
    accountPreferences.disconnect();
  }
  const unobserve = observeRequests((event) => {
    if (event.phase === 'start') return epoch;
    if (!alive || event.context !== epoch || paused.value) return;
    if (event.status === 401) suspend();
    if (event.status === 403) {
      try {
        if (record(record(event.payload).error).code === 'account_restricted')
          suspend();
      } catch {
        /* Other denials stay in the owning form. */
      }
    }
  });
  async function refresh() {
    if (refreshing || paused.value) return;
    refreshing = true;
    const own = epoch;
    try {
      await authGet('csrf', abort.signal);
      const next = activityResponse(await authGet('session', abort.signal));
      if (!alive || own !== epoch) return;
      if (session.value && session.value.public_id !== next.public_id)
        clearAccountDraft();
      session.value = next;
      failure.value = '';
      if (next.restrictions.length) suspend();
    } catch (error) {
      if (alive && own === epoch && !(error instanceof RequestCancelledError)) {
        if (authFailure(error).code === 'signed_out') suspend();
        else failure.value = 'unavailable';
      }
    } finally {
      refreshing = false;
      if (alive) loading.value = false;
    }
  }
  async function authenticated(identity: EntrySession) {
    if (session.value?.public_id !== identity.public_id) clearAccountDraft();
    epoch++;
    paused.value = false;
    session.value = undefined;
    loading.value = true;
    await refresh();
  }
  async function logout() {
    if (pending.value) return;
    pending.value = true;
    failure.value = '';
    try {
      await authPost('logout', {}, abort.signal);
      epoch++;
      abort.abort();
      clearAccountDraft();
      accountPreferences.disconnect();
      session.value = undefined;
      navigate('/');
    } catch (error) {
      if (!(error instanceof RequestCancelledError))
        failure.value = authFailure(error).code;
    } finally {
      if (alive) pending.value = false;
    }
  }
  async function activity(kind: string) {
    const current = session.value;
    if (
      !current ||
      paused.value ||
      pending.value ||
      !current.activity.extension ||
      Date.now() - lastActivity < current.activity.heartbeat_seconds * 1000
    )
      return;
    lastActivity = Date.now();
    const own = epoch;
    try {
      const result = activityResponse({
        data: {
          ...current,
          session: data(
            await authPost(
              'session/activity',
              { activity: kind },
              abort.signal,
            ),
          ),
        },
      });
      if (alive && own === epoch && result.public_id === current.public_id)
        session.value = result;
    } catch (error) {
      if (alive && own === epoch && !(error instanceof RequestCancelledError))
        failure.value = authFailure(error).code;
    }
  }
  const activityTypes = {
    keydown: 'typing',
    click: 'click',
    scroll: 'scroll',
    touchstart: 'touch',
  } as const;
  function interact(event: Event) {
    if (event.isTrusted)
      void activity(activityTypes[event.type as keyof typeof activityTypes]);
  }
  function visible() {
    if (document.visibilityState === 'visible') void refresh();
  }
  function pageShow(event: PageTransitionEvent) {
    if (event.persisted) void refresh();
  }
  let timer: ReturnType<typeof setInterval>;
  onMounted(() => {
    void refresh();
    Object.keys(activityTypes).forEach((type) =>
      window.addEventListener(type, interact, { passive: true, capture: true }),
    );
    document.addEventListener('visibilitychange', visible);
    window.addEventListener('pageshow', pageShow);
    timer = setInterval(() => {
      now.value = Date.now();
      if (session.value && remaining.value <= 0) suspend();
    }, 1000);
  });
  onScopeDispose(() => {
    alive = false;
    epoch++;
    abort.abort();
    unobserve();
    clearInterval(timer);
    Object.keys(activityTypes).forEach((type) =>
      window.removeEventListener(type, interact, true),
    );
    document.removeEventListener('visibilitychange', visible);
    window.removeEventListener('pageshow', pageShow);
  });
  return {
    session,
    paused,
    loading,
    failure,
    pending,
    warning,
    remaining,
    refresh,
    logout,
    authenticated,
    activity,
  };
}
