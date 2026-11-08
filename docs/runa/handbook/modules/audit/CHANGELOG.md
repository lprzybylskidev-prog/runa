# Audit module changelog

## 1.1.2 — Unreleased

- Admit the verified platform0.8.0 release in the supplied>=0.7.0 <0.9.0
  compatibility range; module/API versions and runtime behavior are unchanged.

- Use the shared semantic action/feedback icons with visible labels and preserved
  busy, keyboard, proof and operation behavior; no backend contract/storage change.

- Initialize directly at the current schema/version; remove historical development
  upgrade scaffolding. Retain the 1.1.1 → 1.1.2 current-schema update,
  reserved declaration/Audit participation and native history/recovery refusals.
  Published Core databases with replaced migration history require explicit
  development reconciliation; no historical schema conversion is provided.

## 1.1.1 — Unreleased

- Declare Framework API ^2.0.0 compatibility after native settings review publication changes. Publish the current source contract; Audit APIs, event storage and immutable history remain unchanged.

## 1.1.0 — 2026-10-30

- Extend AuditReads to 1.1.0 with optional SQL summary ordering before cursor paging, nulls last and stable append-identity ties; expose implemented sort keys in the Admin history table without changing immutable events or read authority.

- Combine history From/Until filters into one compact shared DateTimeRangeInput. Preserve exact URL/query keys, authorized filtering, UTC transport and application-timezone display.

- Make truncated JSON explicit with a separate continuation-marker line.

- Use detail-shaped pulsing ContentLoading placeholders for initial detail-modal reads and retry, with retained payload pulsing during continuation. Circular content indicators are removed.

- Move correlation into Event and make correlation/actor UUID values links to their exact history filters; remove technical-ID/related-entity cards and share colored JSON previews with inline expansion.

- Group event/actor/technical metadata, omit empty filter-only properties, format and collapse JSON with Show all, hide single-part controls and explain entity-history links.

- Localize actor kinds and outcomes consistently in history filters, rows and immutable details, using lowercase labels in both languages without changing stored payloads or export codes.

- Return exact SQL-filtered AuditPage.total before cursor/limit and forward it to Admin, including entity/date filters and zero matches.

- Declare from/until as datetime filters and use the shared timezone-aware calendar/time picker. Keep history headers and horizontal scrolling accessible while scrolling the page.

- Keep reset beside filter fields in the shared filter region. Render declared
  event timestamps and known Auth entry dates through the application locale/
  timezone formatter, preserving canonical instants in transport and exports.

- Use shared table refresh/reset icons with Tooltips, compact automatic filters
  and dictionary Selects; open Details in guarded dialogs. Supply bounded
  CSV/XLSX/PDF/Print behind the shared `runa.permissions.tables.export` grant
  and current owning read authorization.

- Use shared FormNotifications for administrative event-detail read errors.

- Localize the administrative history/detail presentation through shared Admin PL/EN copy while preserving immutable Audit records and technical identifiers.

- Add reserved operational participation for atomic framework lifecycle phase history and first cooperative cancellation requests, without configuration values or runner tokens.

- Preserve the 64-KiB management-record bound with explicit large-payload continuation; reconstruct complete immutable JSON through guarded reads.
- Deliver source-owned Admin cursor/filter/detail/entity-history viewer and public presentation defaults; summaries omit payloads, current read authority remains required.
- Add no-transform admin_history forward preparation and target platform >=0.7.0 <0.8.0. AuditReads/AuditRecords remain 1.0.0; no backend module dependency or mutable event API.

## 1.0.5 — 2026-10-30

- Widen platform compatibility to <0.8.0, preserving the >=0.4.2 lower bound and existing API contracts.
- Append the source-only admin_platform forward edge; no schema or data transformation.

## 1.0.4 — 2026-10-28

- Complete typed capture, event and payload contracts with the actual public trait consumer; retain atomic history and redaction rules.
- Add the source-only verification_contracts forward edge without schema or data changes.

## 1.0.3 — 2026-10-28

- Support platform 0.6.x explicitly while retaining public APIs and all historical upgrade identities.
- Record the source-only platform_delivery edge; production images exclude developer implementations and raw module frontend sources. No schema or data transformation.

## 1.0.2 — 2026-10-28

- Reconcile internal platform collaborators with the module framework and Core integration homes; public contracts and runtime behavior remain compatible.
- Add a source-only forward edge with no schema or data transformation.

## 1.0.1 — 2026-10-27

- Relocate module test metadata and cases to the central owner test tree; runtime APIs and data remain unchanged.

## 1.0.0

Prepare the first stable Audit release for verified closure of RM-2026-011.
Preserve the delivered public contracts and add the source-only 0.7.0 -> 1.0.0
update edge; no schema or data transformation.

## 0.7.0

Extend approved own-history values to bounded redacted JSON for structured preferences; existing scalar projections remain compatible.
Source-only forward edge; no schema migration.

## 0.6.0

Add the guarded OwnHistoryDetails 1.0.0 API for account-bound, action/field-allowlisted
change pairs. Summaries remain unchanged. No actors, context, raw payloads or secrets
are projected. Source-only forward edge; no storage migration.

## 0.5.0

Add safe read-only future-management event metadata and a source-only 0.4.0 -> 0.5.0 edge. The framework optionally snapshots trusted human display names through Auth public identity participation without adding an Audit dependency. Existing APIs/store/history remain compatible and immutable.

## 0.4.0

Add source-owned localized read permission declarations through the existing management category and a source-only 0.3.0 -> 0.4.0 upgrade edge. Public Audit API signatures and the dependency-free Audit store remain unchanged. Installed Permissions synchronizes declarations through reserved preparation; no request writes or automatic grants.

## 0.3.0

Add OwnHistory 1.0.0 safe summary pages bound to a trusted human's own UUIDv7 entity,
bounded source-selected actions and same-scope append cursors. No arbitrary subject,
actor/entity/payload details or general Audit permission is exposed. Add a no-data-
transform 0.2.0 -> 0.3.0 edge; existing AuditRecords/AuditReads signatures and
framework API remain compatible, with no Auth/Permissions dependency.

## 0.2.0

Compatible automatic Auditable single-model capture with explicit approved fields,
diff/snapshot and per-event configuration; existing default-connection transaction
precondition, atomic mutation savepoint and finally-unwound automatic suppression.
Manual API 1.0.0 stays unchanged. New permission-protected AuditReads API 1.0.0
adds immutable bounded summaries/detail, filters, append cursors and safe metadata.
Forward 0.1.0 -> 0.2.0 adds query indexes without transforming retained events.

## 0.1.0

Initial Core module: append-only PostgreSQL events and multi-entity references,
immutable manual event/reference/query contracts, guarded record/independent-attempt
API 1.0.0, bounded redaction and trusted execution/actor context. Same-connection
transactional success and post-root-rollback refusal persistence; source-owned
settings and reserved preparation participation; explicit store-ready bootstrap.
No Auth/Permissions dependency. Automatic capture and authorized reads follow
separately; no update/delete/retention or administrative UI is delivered.
