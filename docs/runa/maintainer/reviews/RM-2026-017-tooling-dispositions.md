# RM-2026-017 — Point-8 tooling and configuration dispositions

Implementation reconciliation, 2026-11-06. The
[renewed audit](RM-2026-017-renewed-audit-synthesis.md#tests-documentation-instructions-and-engineering-environment)
remains the baseline; the active roadmap owns execution order and final checks.
This record distinguishes source review, focused fixtures and deferred native
acceptance. No advisory, infrastructure or complete retrieval PASS is inferred.

## Demonstrated corrections

- Vite's application root made its default dependency cache appear at
  `src/app/node_modules/.vite` during the point-7 observation. Both Vite configs
  now resolve their cache directories from their configuration URL under the
  workspace `.runtime/frontend/vite` tree. The existing cache was preserved there.
  Real HMR startup creates the application cache and no source-local parent;
  resolving both installed Vite configurations confirms the exact absolute paths.
- DevelopmentSetup's freshness input list omitted shared Runa resources, module
  manifests/frontend, registry plugin and native error build implementation/config.
  Consequently an existing marker could skip rebuilding changed shipped views.
  Include those owning inputs while excluding generated caches/reports. The
  focused startup fixture uses the real fingerprint: unchanged inputs skip build,
  each independent source change triggers build, generated cache does not, and
  no migration/seeding command is invoked. This tests command selection without
  installing dependencies, building assets or touching a real database.
- The command catalogue now includes the canonical application module frontend
  test root added in point 7; optional tests travel with their owning source.
  The native runner's empty-scope check now includes that root too: a customer
  with only module frontend tests executes Vitest instead of reporting empty.
  Removing the optional module still yields the ordinary honest empty result.
- Add exact-source/hash-bound external annotations for RM-016's 11a93 action
  defaults (superseded) and 11a95 refinement (accepted). Keep immutable historical
  bytes and separate surviving action semantics from the replaced solid/blue
  opener rule. Three owner golden cases select each historical decision and the
  current public Button contract. Unknown effective version remains null; a Git
  hash/index timestamp supplies no invented release applicability. Existing
  missing-evidence and receipt/private-source fixtures remain required.

The supported focused tooling runs pass: startup `frontend_input_changes`
1 selected / 19 deselected; ingestion `external_annotation_hash` 1 selected /
24 deselected; runner `module_only_frontend_scope` 1 selected / 13 deselected,
all native exit 0 and cleanup 0. A bounded configuration diagnostic
also loads the real owner composition and verifies accepted/superseded labels and
the exact supersession link. It is not a live golden evaluation. Full selected
tooling, knowledge doctor/evaluation, Boost and native readiness stay at point 12.

## Configuration ownership map and retention

| Inputs                                                       | Actual owner/consumer and disposition                                                                                                                                                                                                                                                                                                                       |
| ------------------------------------------------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `.env.dev.example`, `.env.test.example`, `.env.prod.example` | Technical Laravel inputs are consumed under `src/runa/config`, with `src/app/config` overrides. Configuration/template validation owns type, presence and unsafe production combinations. No private values were printed or rewritten; no new environment key is introduced.                                                                                |
| APP/DB keys                                                  | App identity/key/debug/URL and PostgreSQL connection defaults remain in owning config. Database uses `shared`, UTC and pgsql; separate platform/application ledgers remain intact. No new DB-backed configuration substitute or reset is selected.                                                                                                          |
| MAIL/QUEUE/LOG keys                                          | Owning config routes Mailpit/SMTP, database queue and secret-safe logging. These remain infrastructure configuration; source-owned module policies and DB business settings are not moved into env. No mail send or backlog mutation was performed for this review.                                                                                         |
| DEV_SEED keys                                                | Seeding configuration guards local development opt-in; explicit initialization and managed seed contributors own data preparation. Ordinary reopen/setup still does not run data changes. Retain separate test database guards and worker ownership.                                                                                                        |
| KNOWLEDGE and model/service inputs                           | Laravel's production prohibition and the development knowledge config remain separate. Retain existing CPU/CUDA model/runtime identity, dimensions, pooling, provider budgets, collection and metadata schema. No embedding/model upgrade, index rebuild, payload-index invention or application startup dependency is introduced.                          |
| `.env.dev.local`, resource overlays and Dev Container        | DevelopmentConfig and native Compose own workstation resource/device reconciliation. Bind mount remains `.:/workspace`; prepare/setup reuse the same implementation, `shutdownAction: none` preserves sibling services, and optional knowledge initialization remains independent. No host transition or image change is required by the local corrections. |
| `.env.prod*`, `compose.production.yaml`, Docker allowlists   | Separate production image/env/storage inputs, forced production/debug/knowledge defaults and explicit copy/service allowlists remain. Source review is not an image-content/restart result. No production operation or speculative infrastructure hardening is selected.                                                                                    |
| Dependency owner manifests and lockfiles                     | `tools/application/dependencies.py` composes platform/App declarations without resolving locks. Retain exact locked dependencies and ownership. Setup freshness now includes their actual presentation build consumers. No dependency was added, removed or updated.                                                                                        |

## Dependency use and disposition

Laravel owns framework/bootstrap/database/HTTP; Fortify is consumed by the Auth
transport provider while Runa owns routes/security orchestration. Vue/Tailwind/Vite
own the delivered presentation/build; pdfmake and write-excel-file have actual
lazy consumers in `src/runa/resources/js/lib/binaryExports.ts`. Keep them rather
than declaring unused dependencies from one entrypoint's import list.

Pest/Laravel test integration, PHPStan/Larastan, Pint, Boost, TypeScript/vue-tsc,
ESLint/Prettier and Vitest/Playwright retain distinct development/test/analysis
roles in current configuration and supported commands. Existing Python toolchain
pins, scanner provisioning, Qdrant and embedding runtime remain deliberate
development tooling. This is usage/ownership retention, not current security,
license or compatibility certification. Composer/pnpm audit and the selected
actual image/repository scanners remain strict final gates; no upgrade proposal
or added dependency requires a new owner decision here.

## Retrieval, guidance and remaining work

Point-8 context initially found stale knowledge after the point-7 documentation
edits. Incremental refresh completed with no missing/orphan chunks or interrupted
writes, then a bounded current-intent cache-placement query selected the canonical
project-principles source, which was opened before editing. Subsequent source and
metadata changes require the normal incremental refresh; no rebuild/deletion or
service bootstrap is used. Staged-source allowlisting and receipt-based customer
scope remain intact. New RM-016 annotations are private owner configuration;
customer retrieval still receives only its public product, config and golden set.

Public environment/command guides and central changelog are synchronized. Existing
owner/customer instructions already select generated-state placement, explicit
initialization and knowledge source verification; no new reusable rule or copied
procedure is needed in either instruction product. Module APIs/runtime semantics
and independent module versions are unaffected. Point 9 owns campaign/evidence
and SIGSEGV mechanics; they are not claimed delivered by this point.

Task-owned HMR processes were stopped. No container/network/image/volume was
created or removed. Preserve development/reference data, base test database,
knowledge/model/package caches and all diagnostic/native reports.
