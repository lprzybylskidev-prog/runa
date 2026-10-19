import { computed, onScopeDispose, shallowRef } from 'vue';
import { ApiError } from '../lib/http';
import type {
  ExportOperation,
  OperationResult,
  OperationStatus,
  OperationFailure,
} from '../runa/ui/exports';

export function operationFailure(cause: unknown): OperationFailure {
  const known = [
    'export_limit',
    'invalid_export_cell',
    'invalid_export_columns',
    'unsupported_export',
    'selection_stale',
    'unsafe_download_url',
  ];
  const code =
    cause instanceof ApiError && [401, 403].includes(cause.status)
      ? 'forbidden'
      : cause instanceof Error && known.includes(cause.message)
        ? cause.message
        : 'operation_failed';
  return { code, message: '', retryable: code === 'operation_failed' };
}
export function useOperation(pollInterval = 750) {
  const status = shallowRef<OperationStatus | null>(null),
    busy = shallowRef(false);
  let aborter: AbortController | undefined,
    generation = 0;
  const operation = shallowRef<ExportOperation>();
  let repeat: ((signal: AbortSignal) => Promise<OperationResult>) | undefined;
  let starter: ((signal: AbortSignal) => Promise<OperationResult>) | undefined;
  let requireDownload = false;
  const localCancellation = shallowRef(false);
  async function consume(
    result: OperationResult,
    signal: AbortSignal,
    current: number,
  ) {
    if (current !== generation) return;
    if (result.kind === 'download') {
      status.value = { state: 'completed', download: result.download };
      return;
    }
    if (result.kind === 'status') {
      if (['queued', 'running'].includes(result.status.state))
        throw new Error('invalid_operation');
      status.value = result.status;
      return;
    }
    operation.value = result.operation;
    // A failed poll resumes the same operation rather than creating another job.
    repeat = async () => ({ kind: 'operation', operation: result.operation });
    for (let polls = 0; polls < 1200; polls++) {
      signal.throwIfAborted();
      const next = await result.operation.poll(signal);
      if (current !== generation) return;
      status.value =
        next.state === 'running' && next.progress !== undefined
          ? {
              ...next,
              progress: Number.isFinite(next.progress)
                ? Math.max(0, Math.min(100, next.progress))
                : 0,
            }
          : next;
      if (!['queued', 'running'].includes(next.state)) {
        repeat = result.operation.retry
          ? (s) => result.operation.retry!(s)
          : undefined;
        return;
      }
      await new Promise<void>((resolve, reject) => {
        const finish = () => {
          signal.removeEventListener('abort', cancel);
          resolve();
        };
        const timer = globalThis.setTimeout(finish, pollInterval);
        const cancel = () => {
          globalThis.clearTimeout(timer);
          reject(signal.reason);
        };
        signal.addEventListener('abort', cancel, { once: true });
      });
    }
    throw new Error('operation_timeout');
  }
  async function execute(
    start: (signal: AbortSignal) => Promise<OperationResult>,
  ) {
    if (busy.value) return;
    aborter?.abort();
    aborter = new AbortController();
    const current = ++generation;
    busy.value = true;
    operation.value = undefined;
    status.value = { state: 'queued' };
    try {
      await consume(await start(aborter.signal), aborter.signal, current);
      const outcome = status.value as OperationStatus | null;
      if (
        current === generation &&
        requireDownload &&
        outcome?.state === 'completed' &&
        !outcome.download
      )
        throw new Error('missing_export_download');
    } catch (cause) {
      if (current === generation)
        status.value = { state: 'failed', error: operationFailure(cause) };
    } finally {
      if (current === generation) busy.value = false;
    }
  }
  async function run(
    start: (signal: AbortSignal) => Promise<OperationResult>,
    expectsDownload = false,
    allowLocalCancellation = false,
  ) {
    if (busy.value) return;
    localCancellation.value = allowLocalCancellation;
    starter = start;
    repeat = start;
    requireDownload = expectsDownload;
    await execute(start);
  }
  async function retry() {
    const next = repeat ?? starter;
    if (next && canRetry.value) await execute(next);
  }
  async function cancel() {
    if (!canCancel.value) return;
    const current = generation;
    try {
      if (operation.value) {
        if (!operation.value.cancel) return;
        await operation.value.cancel(new AbortController().signal);
      }
    } catch {
      if (current !== generation || !busy.value) return;
      generation++;
      aborter?.abort();
      busy.value = false;
      status.value = {
        state: 'failed',
        error: { code: 'cancel_failed', message: '', retryable: true },
      };
      return;
    }
    if (current !== generation || !busy.value) return;
    generation++;
    aborter?.abort();
    busy.value = false;
    status.value = { state: 'cancelled' };
  }
  const canRetry = computed(
    () =>
      !busy.value &&
      !!repeat &&
      (status.value?.state === 'failed' || status.value?.state === 'partial') &&
      status.value.error.retryable,
  );
  const canCancel = computed(
    () =>
      busy.value &&
      (operation.value ? !!operation.value.cancel : localCancellation.value),
  );
  const download = computed(() =>
    status.value?.state === 'completed' || status.value?.state === 'partial'
      ? status.value.download
      : undefined,
  );
  onScopeDispose(() => {
    generation++;
    aborter?.abort();
  });
  return { status, busy, canRetry, canCancel, download, run, retry, cancel };
}
export type OperationController = ReturnType<typeof useOperation>;
