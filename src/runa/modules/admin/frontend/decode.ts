import { decodeProtectedActivity } from '../../auth/frontend/useProtectedActivity';
import type {
  AdminText,
  AdminBootstrap,
  AdminComponent,
  AdminPageData,
  AdminJson,
  AdminRecord,
  AdminSectionData,
  AdminFieldData,
  AdminCommandData,
  AdminWidgetData,
  AdminReadQuery,
  AdminReadResult,
} from './contracts';
import type { AdminCommandReview, AdminCommandResult } from './contracts';

export function decodeCommandReview(
  value: unknown,
  section?: AdminSectionData,
): AdminCommandReview {
  const v = object(object(value).data);
  if (!['none', 'password'].includes(String(v.requirement)))
    throw new TypeError('Invalid command review.');
  const changes = values(v.changes);
  if (
    section &&
    Object.keys(changes).some(
      (key) =>
        !section.fields.some((field) => field.key === key && !field.sensitive),
    )
  )
    throw new TypeError('Unreadable review field.');
  return {
    fingerprint: text(v.fingerprint),
    changes,
    requirement: v.requirement as AdminCommandReview['requirement'],
    every_action: boolean(v.every_action),
  };
}
export function decodeCommandResult(
  value: unknown,
  section: AdminSectionData,
): AdminCommandResult {
  const v = object(object(value).data);
  if (!['committed', 'pending', 'recovery_required'].includes(String(v.status)))
    throw new TypeError('Invalid command outcome.');
  const operationId = nullable(v.operation_id, (v) => text(v));
  if (v.status !== 'committed' && (v.record !== null || !operationId))
    throw new TypeError('Invalid pending command.');
  return {
    status: v.status as AdminCommandResult['status'],
    operation_id: operationId,
    record:
      v.record === null
        ? null
        : (decodeRead(
            { data: v.record },
            { kind: 'record' },
            section,
          ) as AdminRecord),
  };
}
export function object(value: unknown): Record<string, unknown> {
  if (value === null || typeof value !== 'object' || Array.isArray(value))
    throw new TypeError('Invalid Admin object.');
  return value as Record<string, unknown>;
}
function text(value: unknown, max = 256): string {
  if (typeof value !== 'string' || value.length > max)
    throw new TypeError('Invalid Admin text.');
  return value;
}
function localizedText(value: unknown, max = 256): AdminText {
  if (typeof value === 'string') return text(value, max);
  const v = object(value);
  if (Object.keys(v).sort().join(',') !== 'en,pl')
    throw new TypeError('Invalid Admin localized text.');
  return { pl: text(v.pl, max), en: text(v.en, max) };
}
export function owner(value: unknown): string {
  const id = text(value, 128);
  if (!/^(app|(?:app|runa)\.[a-z][a-z0-9_]*)$/.test(id))
    throw new TypeError('Invalid Admin owner.');
  return id;
}
export function key(value: unknown): string {
  const id = text(value, 128);
  if (!/^[a-z][a-z0-9_]*$/.test(id)) throw new TypeError('Invalid Admin key.');
  return id;
}
function operation(value: unknown): string {
  const id = text(value, 128);
  if (!/^[a-z][a-z0-9_]*(?:\.[a-z][a-z0-9_]*)*$/.test(id))
    throw new TypeError('Invalid Admin operation key.');
  return id;
}
function integer(value: unknown): number {
  if (typeof value !== 'number' || !Number.isSafeInteger(value) || value < 0)
    throw new TypeError('Invalid Admin integer.');
  return value;
}
function boolean(value: unknown): boolean {
  if (typeof value !== 'boolean') throw new TypeError('Invalid Admin flag.');
  return value;
}
function array<T>(
  value: unknown,
  decode: (value: unknown) => T,
  max: number,
): T[] {
  if (!Array.isArray(value) || value.length > max)
    throw new TypeError('Invalid Admin list.');
  return value.map(decode);
}
function nullable<T>(value: unknown, decode: (value: unknown) => T): T | null {
  return value === null ? null : decode(value);
}
function unique<T>(values: T[], identity: (value: T) => string): T[] {
  if (new Set(values.map(identity)).size !== values.length)
    throw new TypeError('Duplicate Admin identity.');
  return values;
}
function hash(value: unknown): string {
  const v = text(value, 64);
  if (!/^[a-f0-9]{64}$/.test(v))
    throw new TypeError('Invalid Admin build identity.');
  return v;
}
function component(value: unknown): AdminComponent {
  const v = object(value),
    id = owner(v.owner),
    name = text(v.key);
  const prefix = id + '.';
  if (
    !name.startsWith(prefix) ||
    !/^[a-z][a-z0-9_]*$/.test(name.slice(prefix.length))
  )
    throw new TypeError('Invalid Admin component owner.');
  if ((id === 'app') !== (v.manifest_hash === null))
    throw new TypeError('Invalid Admin manifest identity.');
  return {
    key: name,
    owner: id,
    manifest_hash: nullable(v.manifest_hash, hash),
    build_id: nullable(v.build_id, hash),
  };
}
function json(value: unknown, depth = 0): AdminJson {
  if (depth > 16) throw new TypeError('Admin JSON depth exceeded.');
  if (value === null || typeof value === 'boolean' || typeof value === 'string')
    return value;
  if (typeof value === 'number' && Number.isFinite(value)) return value;
  if (Array.isArray(value))
    return array(value, (v) => json(v, depth + 1), 4096);
  return Object.fromEntries(
    Object.entries(object(value)).map(([k, v]) => [k, json(v, depth + 1)]),
  );
}
function values(value: unknown): Record<string, AdminJson> {
  return object(json(object(value))) as Record<string, AdminJson>;
}
export function decodeRecord(value: unknown): AdminRecord {
  const v = object(value);
  return {
    public_id: nullable(v.public_id, (id) => {
      const s = text(id, 36);
      if (
        !/^[a-f0-9]{8}-[a-f0-9]{4}-7[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i.test(
          s,
        )
      )
        throw new TypeError('Invalid Admin record.');
      return s;
    }),
    revision: text(v.revision),
    values: values(v.values),
  };
}
function field(value: unknown): AdminFieldData {
  const v = object(value);
  return {
    key: operation(v.key),
    type: key(v.type),
    label: localizedText(v.label, 1024),
    readonly: boolean(v.readonly),
    sensitive: boolean(v.sensitive),
    filterable: boolean(v.filterable),
    sortable: boolean(v.sortable),
    lookup: boolean(v.lookup),
    required: boolean(v.required),
    max_length: nullable(v.max_length, integer),
    choices: array(v.choices, (s) => text(s, 4096), 256),
    multiple: boolean(v.multiple),
    component: nullable(v.component, component),
  };
}
function command(value: unknown): AdminCommandData {
  const v = object(value);
  if (
    (v.mode !== 'independent' && v.mode !== 'atomic') ||
    !['action', 'save', 'create', 'delete'].includes(String(v.intent)) ||
    !['none', 'password'].includes(String(v.requirement))
  )
    throw new TypeError('Invalid Admin command.');
  return {
    key: operation(v.key),
    title: localizedText(v.title),
    mode: v.mode,
    destructive: boolean(v.destructive),
    requirement: v.requirement as AdminCommandData['requirement'],
    preview_required: boolean(v.preview_required),
    inputs: unique(array(v.inputs, operation, 64), (v) => v),
    bulk: boolean(v.bulk),
    action: nullable(v.action, operation),
    available: boolean(v.available),
    intent: v.intent as AdminCommandData['intent'],
  };
}
function section(value: unknown): AdminSectionData {
  const v = object(value),
    id = text(v.key, 512);
  if (
    !/^[a-z][a-z0-9_.]*$/.test(id) ||
    (v.view !== 'record' && v.view !== 'table')
  )
    throw new TypeError('Invalid Admin section.');
  return {
    export_allowed: v.export_allowed === true,
    key: id,
    view: v.view,
    title: localizedText(v.title),
    readonly: boolean(v.readonly),
    required: boolean(v.required),
    component: nullable(v.component, component),
    fields: unique(array(v.fields, field, 64), (f) => f.key),
    commands: unique(array(v.commands, command, 32), (c) => c.key),
    record: nullable(v.record, decodeRecord),
    diagnostic: nullable(v.diagnostic, text),
  };
}
function timestamp(value: unknown): string {
  const v = text(value, 64);
  if (
    !/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d(?:\.\d+)?(?:Z|\+00:00)$/.test(v) ||
    !Number.isFinite(Date.parse(v))
  )
    throw new TypeError('Invalid Admin timestamp.');
  return v;
}
function widget(value: unknown): AdminWidgetData {
  const v = object(value);
  const observed = nullable(v.observed_at, timestamp),
    expires = nullable(v.expires_at, timestamp);
  if (expires && (!observed || Date.parse(expires) <= Date.parse(observed)))
    throw new TypeError('Invalid Admin freshness.');
  return {
    id: text(v.id),
    title: localizedText(v.title),
    component: nullable(v.component, component),
    values: values(v.values),
    observed_at: observed,
    expires_at: expires,
    diagnostic: nullable(v.diagnostic, text),
  };
}
function envelope(value: unknown): Record<string, unknown> {
  if (JSON.stringify(value).length > 1048576)
    throw new TypeError('Admin response limit exceeded.');
  const v = object(object(value).data);
  if (v.schema_version !== 1) throw new TypeError('Invalid Admin schema.');
  return v;
}
export function adminPath(location: { owner: string; page: string }): string {
  return '/admin/' + owner(location.owner) + '/' + key(location.page);
}
export function decodeBootstrap(value: unknown): AdminBootstrap {
  const v = envelope(value),
    dashboard = object(v.dashboard),
    presentation = object(v.presentation);
  return {
    schema_version: 1,
    activity: decodeProtectedActivity(v.activity),
    generation: integer(v.generation),
    revision: text(v.revision),
    owners: unique(
      array(
        v.owners,
        (item) => {
          const o = object(item),
            id = owner(o.id);
          const pages = unique(
            array(
              o.pages,
              (item) => {
                const p = object(item),
                  k = key(p.key);
                if (p.href !== adminPath({ owner: id, page: k }))
                  throw new TypeError('Invalid Admin page URL.');
                if (
                  typeof p.order !== 'number' ||
                  !Number.isSafeInteger(p.order)
                )
                  throw new TypeError('Invalid Admin page order.');
                return {
                  key: k,
                  title: localizedText(p.title),
                  href: p.href as string,
                  group: nullable(p.group, localizedText),
                  order: p.order,
                };
              },
              32,
            ),
            (p) => p.key,
          );
          const defaultPage = key(o.default_page);
          if (!pages.some((p) => p.key === defaultPage))
            throw new TypeError('Invalid Admin default page.');
          return {
            id,
            title: localizedText(o.title),
            default_page: defaultPage,
            pages,
          };
        },
        256,
      ),
      (o) => o.id,
    ),
    dashboard: {
      widgets: unique(array(dashboard.widgets, widget, 512), (w) => w.id),
    },
    presentation: {
      name: text(presentation.name),
      revision: integer(presentation.revision),
      logo: nullable(presentation.logo, (value) => {
        const logo = object(value);
        return {
          src: text(logo.src, 2048),
          accessibleName: text(logo.accessibleName),
        };
      }),
    },
  };
}
export function decodePage(value: unknown): AdminPageData {
  const v = envelope(value);
  return {
    schema_version: 1,
    owner: owner(v.owner),
    key: key(v.key),
    title: localizedText(v.title),
    generation: integer(v.generation),
    revision: text(v.revision),
    component: nullable(v.component, component),
    sections: unique(array(v.sections, section, 256), (s) => s.key),
  };
}

/** Decode only the requested capability and the still-readable field projection. */
export function decodeRead(
  value: unknown,
  query: AdminReadQuery,
  section: AdminSectionData,
): AdminReadResult {
  if (JSON.stringify(value).length > 1048576)
    throw new TypeError('Admin response limit exceeded.');
  const v = object(object(value).data);
  function projectedRecord(value: unknown): AdminRecord {
    const result = decodeRecord(value);
    if (
      query.public_id &&
      query.kind === 'record' &&
      result.public_id !== query.public_id
    )
      throw new TypeError('Admin subject changed.');
    for (const [name, value] of Object.entries(result.values)) {
      const field = section.fields.find((f) => f.key === name);
      if (!field) throw new TypeError('Unexpected Admin field.');
      if (field.sensitive) {
        const state = object(value);
        if (Object.keys(state).length !== 1 || typeof state.set !== 'boolean')
          throw new TypeError('Invalid Admin secret state.');
      } else if (value !== null) {
        const valid =
          field.type === 'text'
            ? typeof value === 'string' &&
              (field.max_length === null ||
                [...value].length <= field.max_length)
            : field.type === 'boolean'
              ? typeof value === 'boolean'
              : field.type === 'integer'
                ? typeof value === 'number' && Number.isSafeInteger(value)
                : field.type === 'datetime'
                  ? typeof value === 'string' && timestamp(value) === value
                  : field.type === 'dictionary'
                    ? typeof value === 'string' &&
                      (!field.choices.length || field.choices.includes(value))
                    : field.type === 'uuid'
                      ? field.multiple
                        ? Array.isArray(value) &&
                          value.length <= 100 &&
                          value.every(isPublicId)
                        : isPublicId(value)
                      : false;
        if (!valid) throw new TypeError('Invalid Admin field value.');
      }
    }
    return result;
  }
  if (query.kind === 'record') return projectedRecord(v);
  if (query.kind === 'options') {
    if (
      v.field !== query.field ||
      !section.fields.some((f) => f.key === v.field && f.lookup)
    )
      throw new TypeError('Admin lookup field changed.');
    return {
      field: query.field,
      items: unique(
        array(
          v.items,
          (value) => {
            const option = object(value);
            return {
              value: text(option.value),
              label: text(option.label, 160),
            };
          },
          query.limit ?? 25,
        ),
        (option) => option.value,
      ),
      next: nullable(v.next, (value) => text(value, 2048)),
    };
  }
  return {
    items: array(v.items, projectedRecord, query.limit ?? 25),
    next: nullable(v.next, (value) => text(value, 2048)),
    total: nullable(v.total, integer),
  };
}
function isPublicId(value: unknown): value is string {
  return (
    typeof value === 'string' &&
    /^[a-f0-9]{8}-[a-f0-9]{4}-7[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i.test(
      value,
    )
  );
}
