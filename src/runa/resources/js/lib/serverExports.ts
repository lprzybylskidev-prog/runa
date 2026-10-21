/* eslint-disable no-control-regex -- Reject control characters in filenames and spreadsheet formula prefixes. */
import { request } from './http';
import type {
  ExportCapabilities,
  ExportDownload,
  ExportOperation,
  ExportProvider,
  ExportResult,
  OperationStatus,
} from '../runa/ui/exports';

function object(value: unknown): Record<string, unknown> {
  if (!value || typeof value !== 'object' || Array.isArray(value))
    throw new Error('invalid_operation_response');
  return value as Record<string, unknown>;
}
function resource(value: unknown): string {
  if (
    typeof value !== 'string' ||
    !/^\/[a-zA-Z0-9/_-]+$/.test(value) ||
    value.startsWith('//')
  )
    throw new Error('invalid_operation_response');
  return value;
}
function count(value: unknown): number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value < 0)
    throw new Error('invalid_operation_response');
  return value;
}
function download(value: unknown): ExportDownload {
  const data = object(value);
  if (
    typeof data.filename !== 'string' ||
    typeof data.mime_type !== 'string' ||
    typeof data.url !== 'string'
  )
    throw new Error('invalid_operation_response');
  const source = new URL(data.url, location.origin);
  if (
    source.origin !== location.origin ||
    !['http:', 'https:'].includes(source.protocol) ||
    source.username ||
    source.password
  )
    throw new Error('unsafe_download_url');
  if (
    ![
      'text/csv;charset=utf-8',
      'text/csv',
      'application/pdf',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ].includes(data.mime_type)
  )
    throw new Error('invalid_operation_response');
  return {
    filename: data.filename.replace(/[\\/\u0000-\u001f]/g, '_'),
    mimeType: data.mime_type,
    source,
  };
}
export function decodeOperationStatus(value: unknown): OperationStatus {
  const data = object(value);
  switch (data.state) {
    case 'queued':
      return { state: 'queued' };
    case 'running':
      if (
        data.progress !== undefined &&
        (typeof data.progress !== 'number' ||
          !Number.isFinite(data.progress) ||
          data.progress < 0 ||
          data.progress > 100)
      )
        throw new Error('invalid_operation_response');
      return {
        state: 'running',
        ...(typeof data.progress === 'number'
          ? { progress: data.progress }
          : {}),
      };
    case 'cancelled':
      return { state: 'cancelled' };
    case 'completed':
      return {
        state: 'completed',
        ...(data.download ? { download: download(data.download) } : {}),
        ...(data.succeeded !== undefined
          ? { succeeded: count(data.succeeded) }
          : {}),
      };
    case 'failed':
    case 'partial': {
      const error = object(data.error);
      if (
        typeof error.code !== 'string' ||
        typeof error.retryable !== 'boolean'
      )
        throw new Error('invalid_operation_response');
      const failure = {
        code: error.code,
        message: '',
        retryable: error.retryable,
      };
      return data.state === 'failed'
        ? { state: 'failed', error: failure }
        : {
            state: 'partial',
            error: failure,
            succeeded: count(data.succeeded),
            failed: count(data.failed),
            ...(data.download ? { download: download(data.download) } : {}),
          };
    }
    default:
      throw new Error('invalid_operation_response');
  }
}
/** Application endpoints are relative to /api; the backend owns authorization and query validation. */
export function createApiExportProvider<Query>(options: {
  path: string;
  capabilities: ExportCapabilities;
}): ExportProvider<Query> {
  const decode = (value: unknown): ExportResult => {
    const data = object(value);
    if (data.kind === 'download')
      return { kind: 'download', download: download(data.download) };
    if (data.kind !== 'operation')
      throw new Error('invalid_operation_response');
    const wire = object(data.operation);
    if (typeof wire.public_id !== 'string' || !wire.public_id)
      throw new Error('invalid_operation_response');
    const path = resource(wire.status_path);
    const cancelPath =
      wire.cancel_path === undefined ? undefined : resource(wire.cancel_path);
    const retryPath =
      wire.retry_path === undefined ? undefined : resource(wire.retry_path);
    const operation: ExportOperation = {
      publicId: wire.public_id,
      poll: async (signal) =>
        decodeOperationStatus(await request(path, { signal })),
      ...(cancelPath && options.capabilities.cancellation
        ? {
            cancel: async (signal: AbortSignal) => {
              await request(cancelPath, { method: 'POST', signal });
            },
          }
        : {}),
      ...(retryPath
        ? {
            retry: async (signal: AbortSignal) =>
              decode(
                await request(retryPath, {
                  method: 'POST',
                  signal,
                  headers: { 'Idempotency-Key': wire.public_id as string },
                }),
              ),
          }
        : {}),
    };
    return { kind: 'operation', operation };
  };
  return {
    capabilities: { ...options.capabilities, execution: 'server' },
    async start(input, signal) {
      if (
        !options.capabilities.formats.includes(input.format) ||
        !options.capabilities.scopes.includes(input.scope.kind)
      )
        throw new Error('unsupported_export');
      return decode(
        await request(resource(options.path), {
          method: 'POST',
          signal,
          headers: {
            'Content-Type': 'application/json',
            ...(input.operationKey
              ? { 'Idempotency-Key': input.operationKey }
              : {}),
          },
          body: JSON.stringify(input),
        }),
      );
    },
  };
}
