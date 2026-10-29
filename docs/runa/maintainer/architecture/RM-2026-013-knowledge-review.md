# RM-2026-013 source and instruction review

## Baseline and scope

Recorded on 2026-10-29 against released v0.6.0 and activation commit 36460e62.
The unchanged retrieval baseline is commit 36460e62 (schema 1, model and pins unchanged).
Final comparison must use the same frozen queries/budgets from point 1 against
that implementation and the candidate; no live evaluation has run in point 1.
The documentation relocation/immutability map remains `documentation-map.json`.
This record tracks review evidence, not a second authority/registry.

## Claim ledger

| Claim/case                                        | Authoritative evidence                                                                          | Point 1 disposition                                                                                                                                                                    |
| ------------------------------------------------- | ----------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Development-only RAG, separate startup            | RM-001 Knowledge/RAG stack; RM-003 Application and knowledge lifecycle; current knowledge guide | Retain original history and current procedure; freeze both historical queries.                                                                                                         |
| Deploy migrations exclusion/inclusion             | RM-005 Decisions, 2026-10-20 and 2026-10-21; current deployment guide                           | Evolution, not contradiction. Both decisions must remain available for history/audit.                                                                                                  |
| Production sparse versus complete host source     | RM-001 Production isolation; RM-005 Decisions; current production architecture                  | Distinguish host checkout from runtime images; preserve history.                                                                                                                       |
| Atomic module transitions                         | RM-006 Dependencies and administrative transitions; current module framework                    | Freeze historical section; current contract remains separate.                                                                                                                          |
| Auth/Permissions/Audit direction and transactions | Current public module guides and src/runa/modules/*/module.json                                 | Auth requires Audit; Permissions requires both; Audit has no reverse dependency. Freeze each public source.                                                                            |
| Current module versions                           | Source manifests, released 0.6.0 changelog                                                      | Auth 1.1.4, Audit 1.0.4, Permissions 1.0.4. Correct residual README claims (1.1.1/1.0.2/1.0.2) and stale Audit ^0.2.0 consumer constraint to ^1.0.0. No runtime/module version change. |
| Foundations intermediate versions                 | core-module-foundations.md and source manifests                                                 | Explicitly label engineering record and route current usage/version questions to public contracts/manifests/changelog. Older paragraphs remain historical evidence.                    |
| RM-012 point 9 open fragment                      | Immutable RM-012 intermediate note and final report                                             | Fragment cannot establish current state. RM-012 is closed/released; do not rewrite it.                                                                                                 |
| Missing rationale/dates                           | Original research has no Qdrant-over-alternatives rationale or complete effective dates         | Keep unknown; a hash/index time/score does not supply missing evidence.                                                                                                                |

## Frozen retrieval expectations

`tools/maintainer/knowledge-golden.json` retains the three original owner cases and
adds source/section cases for all three exercises. Each added query uses top-k 6;
normal baseline per-source diversity remains 1. Point 6 adds explicit coverage,
negative/unknown labels and paired language probes without weakening these sources.
The active roadmap contains the original chunk IDs and complete exercise limits.
A fabricated integration example is an agent inference, never observed behavior.

## Instruction product size baseline

Reproduce at commit 36460e62 using tracked Markdown under `.agents/skills`,
`docs/runa/maintainer/customer-agent`, and root AGENTS. Counts use splitlines/split;
counts signal context growth, not correctness or mandatory caps.

| Product          | Files | Lines | Words |
| ---------------- | ----: | ----: | ----: |
| customer product |    30 |  1499 | 13955 |
| owner AGENTS     |     1 |   394 |  3361 |
| owner skills     |    47 |  2063 | 21557 |

## Review coverage inventory

Inventory is complete for tracked Markdown at activation; **inventory is not completed review**.
Points 7–8 must review every group/product and record individual changes or justified
no-change dispositions. Review references progressively within their own domain.

| Reader / canonical group                           | Files | Review state                                           |
| -------------------------------------------------- | ----: | ------------------------------------------------------ |
| .agents/skills/README.md                           |     1 | Contract/ownership review complete; disposition below. |
| .agents/skills/backend                             |     5 | Contract/ownership review complete; disposition below. |
| .agents/skills/docker                              |     5 | Contract/ownership review complete; disposition below. |
| .agents/skills/frontend                            |     9 | Contract/ownership review complete; disposition below. |
| .agents/skills/laravel                             |     5 | Contract/ownership review complete; disposition below. |
| .agents/skills/postgresql                          |     4 | Contract/ownership review complete; disposition below. |
| .agents/skills/rag                                 |     5 | Contract/ownership review complete; disposition below. |
| .agents/skills/security                            |     5 | Contract/ownership review complete; disposition below. |
| .agents/skills/tailwind                            |     4 | Contract/ownership review complete; disposition below. |
| .agents/skills/vue                                 |     4 | Contract/ownership review complete; disposition below. |
| AGENTS.md                                          |     1 | Contract/ownership review complete; disposition below. |
| README.md                                          |     1 | Contract/ownership review complete; disposition below. |
| closed roadmaps (immutable history)                |    12 | Contract/ownership review complete; disposition below. |
| docs/README.md                                     |     1 | Contract/ownership review complete; disposition below. |
| docs/app (application)                             |     3 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/README.md                       |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/ai                              |     4 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/architecture                    |    10 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/data                            |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/design                          |     4 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/development                     |    15 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/modules                         |    18 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/production                      |     3 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/quality                         |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/release                         |     2 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/security                        |     2 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/testing                         |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/tooling                         |     2 | Contract/ownership review complete; disposition below. |
| docs/runa/handbook/workflows                       |     4 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/CHANGELOG.md                  |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/README.md                     |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/agent-contract.md             |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/architecture                  |     5 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/customer-agent (product data) |    30 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/development                   |     2 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/evidence                      |    15 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/maintenance                   |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/production                    |     2 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/quality                       |     2 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/release                       |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/roadmaps                      |     3 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/security                      |     7 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/testing                       |     1 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/tooling                       |     9 | Contract/ownership review complete; disposition below. |
| docs/runa/maintainer/workflows                     |     3 | Contract/ownership review complete; disposition below. |

## Representative context baseline

- Backend: AGENTS → backend/Laravel skill → public API/module guide → relevant code;
  database/security skills only when the task requires them.
- Frontend: AGENTS → frontend/Vue/Tailwind skill → component/pattern reference and
  presentation contract → relevant view. No backend skill bundle by default.
- Operations: commands guide → owning environment/deploy procedure; avoid closed history.
- History: short English query with roadmap/source filters → original and superseding
  decisions; expand relevant sections and keep absent rationale unknown.
- Customer extension: installed customer AGENTS/skills → public handbook and docs/app;
  authoring templates confer no owner authority. Preserve customer-owned additions.

These are baseline navigation routes, not executed acceptance. Final fresh-context
scenarios must measure actual selected source sets with and without RAG.

## Point 1 instruction-product assessment

Owner AGENTS/skills and separately authored customer guidance need no edit in point 1:
source/fixture reconciliation introduces no new public command, reusable policy or
runtime capability. Existing rules already require verified source/version claims.
All product consolidation remains explicitly scheduled in points 7–9.
No suites, live concurrency or installation acceptance have run. Existing development
services were reopened nondestructively; persistent volumes/caches/reports retained.

## Point 7 contract and ownership review

The complete grouped Markdown inventory above was reviewed by reader, purpose,
heading/navigation coverage, version/delivery claims and canonical contract.
Focused source searches covered residual pending-point/version claims across the
current tree; public module versions were compared with all three real manifests
and release changelogs, application compatibility with src/app/application.json,
Artisan examples with the source-owned catalogue, and frontend entrypoints with
public facades and the placement map. This is a complete contract-level coverage
review, not an exhaustive reimplementation/security audit of every paragraph or
runtime API. Final deterministic links/ownership and live navigation still run in
point 10. Historical bytes and dated scanner/release evidence were not rewritten.

| Owning contract / complete inventory group   | Canonical home and disposition                                                                                                                                                                                                                                                                                        |
| -------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Documentation/root/app indexes               | docs/README.md owns placement; handbook index owns public navigation, maintainer index owner state, docs/app application records. Corrected active RM-013 projection.                                                                                                                                                 |
| Architecture/API/configuration/database      | Public architecture guides own supported contracts; maintainer architecture owns engineering maps. Corrected scaffold range to >=0.2.1 <0.7.0 from actual manifest. Public API placement/signature map remains separate from permission to invoke internals.                                                          |
| Modules and seeding                          | Public module guides and manifests own use/version claims; changelogs are historical delivery evidence. Point 1 corrected residual versions/dependency. Versioned upgrade paragraphs remain explicit historical steps, not current-version assertions. No module/source upgrade edits.                                |
| Frontend/design/localization                 | Public components/layouts/forms/tables/motion/customization guides own complete presentation contracts; domain references route to them. Corrected obsolete design statement that profile/sign-out were still future; notifications remain deferred. App-prefixed source paths and public facade exports still exist. |
| Development/host/editor/Python               | Environment, editor, Python, resources and knowledge guides own procedures; command guide owns discovery. Reconciled old schema-preservation prose with additive payload migration. Optional AI remains independent; application toolchain stays containerized. No lifecycle redesign.                                |
| Production/update/delivery                   | Public production/reconciliation guides own customer operations; maintainer installation/update/deploy guides own private mechanics/evidence. Separate owner/customer remote and protected-source policies are intentional. No infrastructure/security authority change.                                              |
| Testing/quality/releases/workflows           | Public workflow/quality/test guides apply to applications; maintainer guides own platform gates/releases. Their authority scopes are intentional, so they are not deduplicated into one workflow. Final-only broad verification and repair exception remain unchanged.                                                |
| Security/maintenance                         | Current security baseline/runtime policy and maintenance registry own current disposition. Dated gosu/Brotli/risk reports are historical, not active waivers; explicit purpose rules now classify them. No new security exception.                                                                                    |
| Foundations/API map/Python migration records | Intermediate points/versions are historical engineering evidence. Added current routing/closure notice to API placement record, removed stale open RM-011 points from current deferred-concerns registry. Existing source-object maps/ledgers remain engineering data.                                                |
| Closed roadmaps/extracted evidence           | Preserve bytes and annotate externally where needed; explicit reviewed purpose rules prevent these records being treated as current instructions. The original RM-005 decision evolution remains accessible.                                                                                                          |
| All nine owner/customer skill products       | Triggers, domain references and local source metadata reviewed against the canonical homes above. Repeated shared workflow text, per-delivery appendices and near-duplicate public procedures are consolidation work for point 8. No upstream source was freshly researched or adopted.                               |
| Customer AGENTS/CLAUDE/authoring             | Application-only authority is intentional. CLAUDE delegates to AGENTS instead of copying it. Local extensions survive reviewed updates. Templates remain excluded from owner corpus; installed product alone enters customer retrieval.                                                                               |

Reviewed purpose configuration explicitly separates changelogs, extracted evidence,
dated security/engineering records, current procedures and proposals. Unknown
unclassified sources stay unknown. Directory/age is not independently authoritative:
these are reviewed source declarations, with section-specific declarations available
for mixed documents. Nothing claims source freshness certifies factual currency.
