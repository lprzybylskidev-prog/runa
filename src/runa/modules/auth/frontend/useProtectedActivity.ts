import { onScopeDispose, watch } from 'vue';
import { request, RequestCancelledError } from '@/runa/http';

export interface ProtectedActivityStatus {
  remaining_seconds: number;
  heartbeat_seconds: number;
}
export interface ProtectedActivityOptions {
  endpoint: string;
  identity: () => string | undefined;
  active: () => boolean;
  root: () => HTMLElement | undefined;
  /** Optional status already received from the owning protected bootstrap. */
  initial?: () => ProtectedActivityStatus | undefined;
  onExpired: (cause: unknown) => void;
}
export function decodeProtectedActivity(
  value: unknown,
): ProtectedActivityStatus {
  if (typeof value !== 'object' || value === null)
    throw new TypeError('Invalid protected activity status.');
  const row = value as Record<string, unknown>;
  if (
    typeof row.remaining_seconds !== 'number' ||
    !Number.isFinite(row.remaining_seconds) ||
    row.remaining_seconds <= 0 ||
    row.remaining_seconds > 900 ||
    typeof row.heartbeat_seconds !== 'number' ||
    !Number.isFinite(row.heartbeat_seconds) ||
    row.heartbeat_seconds <= 0 ||
    row.heartbeat_seconds > 30
  )
    throw new TypeError('Invalid protected activity status.');
  return {
    remaining_seconds: row.remaining_seconds,
    heartbeat_seconds: row.heartbeat_seconds,
  };
}
/** One owner-selected section. Reads never renew; only trusted visible input does. */
export function useProtectedActivity(options: ProtectedActivityOptions) {
  let alive = true,
    epoch = 0,
    busy = false,
    pending = false;
  let abort = new AbortController();
  let expiryTimer: ReturnType<typeof setTimeout> | undefined;
  let batchTimer: ReturnType<typeof setTimeout> | undefined;
  let lastSent = -Infinity,
    interval = 30000,
    observed = '';
  const enabled = () =>
    alive &&
    options.active() &&
    !!options.identity() &&
    document.visibilityState !== 'hidden';
  function cancel() {
    ++epoch;
    abort.abort();
    abort = new AbortController();
    clearTimeout(expiryTimer);
    clearTimeout(batchTimer);
    expiryTimer = batchTimer = undefined;
    busy = pending = false;
  }
  function apply(status: ProtectedActivityStatus, started: number) {
    interval = status.heartbeat_seconds * 1000;
    clearTimeout(expiryTimer);
    expiryTimer = setTimeout(
      () => {
        void refresh();
      },
      Math.max(
        0,
        started + status.remaining_seconds * 1000 - performance.now(),
      ),
    );
  }
  async function send(renew: boolean) {
    if (!enabled() || busy) return;
    const own = epoch,
      identity = options.identity(),
      started = performance.now();
    busy = true;
    if (renew) {
      pending = false;
      lastSent = started;
    }
    try {
      const result = await request(options.endpoint, {
        method: renew ? 'POST' : 'GET',
        signal: AbortSignal.any([abort.signal, AbortSignal.timeout(10000)]),
      });
      if (!enabled() || own !== epoch || identity !== options.identity())
        return;
      if (typeof result !== 'object' || result === null || !('data' in result))
        throw new TypeError('Invalid protected activity response.');
      apply(decodeProtectedActivity(result.data), started);
    } catch (cause) {
      if (
        !(cause instanceof RequestCancelledError) &&
        enabled() &&
        own === epoch &&
        identity === options.identity()
      ) {
        cancel();
        // The owner handles proof expiry, session loss and availability failures.
        options.onExpired(cause);
      }
    } finally {
      if (own === epoch) {
        busy = false;
        schedule();
      }
    }
  }
  function schedule() {
    clearTimeout(batchTimer);
    if (!enabled() || !pending || busy) return;
    const wait = Math.max(0, lastSent + interval - performance.now());
    batchTimer = setTimeout(() => {
      void send(true);
    }, wait);
  }
  function activity(event: Event) {
    if (
      !enabled() ||
      !event.isTrusted ||
      !(event.target instanceof Node) ||
      !options.root()?.contains(event.target)
    )
      return;
    pending = true;
    schedule();
  }
  async function refresh() {
    await send(false);
  }
  function visible() {
    cancel();
    if (enabled()) void refresh();
  }
  const stop = watch(
    () => [options.identity(), options.active(), options.initial?.()] as const,
    ([identity, active]) => {
      const key = JSON.stringify([identity, active]);
      if (key !== observed) {
        observed = key;
        cancel();
        lastSent = -Infinity;
      }
      if (!enabled()) return;
      const initial = options.initial?.();
      if (initial) apply(decodeProtectedActivity(initial), performance.now());
      else void refresh();
    },
    { immediate: true, flush: 'sync' },
  );
  const events = [
    'click',
    'pointerdown',
    'keydown',
    'input',
    'wheel',
    'touchmove',
  ] as const;
  for (const event of events)
    document.addEventListener(event, activity, {
      capture: true,
      passive: true,
    });
  document.addEventListener('visibilitychange', visible);
  onScopeDispose(() => {
    alive = false;
    cancel();
    stop();
    for (const event of events)
      document.removeEventListener(event, activity, true);
    document.removeEventListener('visibilitychange', visible);
  });
  return { refresh };
}
