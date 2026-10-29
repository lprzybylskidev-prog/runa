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

| Reader / canonical group                           | Files | Review state                                              |
| -------------------------------------------------- | ----: | --------------------------------------------------------- |
| .agents/skills/README.md                           |     1 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/backend                             |     5 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/docker                              |     5 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/frontend                            |     9 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/laravel                             |     5 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/postgresql                          |     4 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/rag                                 |     5 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/security                            |     5 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/tailwind                            |     4 | Inventoried; review pending except point 1 sources above. |
| .agents/skills/vue                                 |     4 | Inventoried; review pending except point 1 sources above. |
| AGENTS.md                                          |     1 | Inventoried; review pending except point 1 sources above. |
| README.md                                          |     1 | Inventoried; review pending except point 1 sources above. |
| closed roadmaps (immutable history)                |    12 | Inventoried; review pending except point 1 sources above. |
| docs/README.md                                     |     1 | Inventoried; review pending except point 1 sources above. |
| docs/app (application)                             |     3 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/README.md                       |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/ai                              |     4 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/architecture                    |    10 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/data                            |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/design                          |     4 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/development                     |    15 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/modules                         |    18 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/production                      |     3 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/quality                         |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/release                         |     2 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/security                        |     2 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/testing                         |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/tooling                         |     2 | Inventoried; review pending except point 1 sources above. |
| docs/runa/handbook/workflows                       |     4 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/CHANGELOG.md                  |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/README.md                     |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/agent-contract.md             |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/architecture                  |     5 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/customer-agent (product data) |    30 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/development                   |     2 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/evidence                      |    15 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/maintenance                   |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/production                    |     2 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/quality                       |     2 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/release                       |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/roadmaps                      |     3 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/security                      |     7 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/testing                       |     1 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/tooling                       |     9 | Inventoried; review pending except point 1 sources above. |
| docs/runa/maintainer/workflows                     |     3 | Inventoried; review pending except point 1 sources above. |

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
