# Audit administration viewer

Audit 1.1.0 supplies `runa.audit/history` Admin declarations, the private HistoryReader
adapter and usable Polish/English defaults. Audit still has no backend dependency on Admin,
Auth or Permissions. Admin requires current entry/password proof; every read also
resolves AuditReads 1.0.0 under current `runa.audit.read` and owning availability.
The source-only `admin_history` forward edge from 1.0.5 to 1.1.0 transforms no data
and preserves immutable events. Public Audit API signatures remain unchanged.

`catalogue` accepts the owning opaque append cursor and limit 1–100, fixed append
order and filters `module`, `action`, `actor_public_id`, `actor_kind`, `outcome`,
`correlation_id`, UTC ISO-8601 `from`/`until`, and the complete three-field entity
reference `entity_module`, `entity_type`, `entity_public_id`. Partial entity filters,
unknown keys, malformed cursors and client sort requests refuse. There is no total,
offset search or assumed historical snapshot. Summary rows contain occurrence time,
module/action/outcome, actor UUID/kind/historical label and correlation only; changes,
context, execution and entity graphs are available only through explicit detail.

`detail` accepts an event public UUID and projects the same immutable metadata plus
bounded JSON text for persisted redacted changes/context/execution and complete safe
entity references. Payloads use aligned 4096-byte ASCII JSON fragments with an
explicit payload_part label and payload_next cursor; request the next record cursor
for the same event UUID to read the continuation. Empty fragments mean that tree
has ended. This preserves the existing 64-KiB record limit without truncating native
Audit history. Immutable event UUID binds every continuation; invalid part cursors
refuse. The UI labels fragments and offers next/first part controls. Unknown events return a safe 404. General Audit read authority
permits those already approved persisted payloads; no raw request, Auth session or
foreign model is fetched. Historical labels remain historical; optional current
Auth identity enrichment is deliberately omitted. No edit/delete/undo command exists.
Grant removal hides the page and refuses future reads without payload disclosure.

Entity links open this same viewer with an exact entity reference filter. They do
not infer a foreign entity route or grant access to its current record. URL filters
are ordinary read selectors, never trusted execution metadata. Values render as
escaped text and links are constructed from encoded fixed query keys.

## Presentation and replacement

Public `@/runa/audit` exports `AuditAdminHistory(AdminPageProps)` and
`AuditAdminDetail(AdminSectionPresentationProps)`. Source lives under
`src/runa/modules/audit/frontend/components`. The history default reuses public
`AdminRecordBrowser` with declared filters; details reuse the shared read/form
controller and resource continuation with escaped read-only values. Customer replacements use public Admin
page/section presentation providers or these defaults in `src/app`, preserving
Runa's shared session/read/reconciliation ownership. No private reader/query import
or copied authorization is supported. See [Admin frontend](../admin/frontend.md).

Focused privacy/interaction evidence is recorded in the active roadmap; complete
rendered light/dark responsive acceptance and full suites remain final verification.
