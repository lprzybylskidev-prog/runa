# Audit module changelog

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
