# Customer instruction and update ownership

## Release inputs and installation

The owner operates root AGENTS.md and .agents/skills. The separately authored
customer product lives in docs/runa/maintainer/customer-agent and is data for the
owner, never an additional operating contract. Both products accompany changes
to reusable rules and delivered capabilities. A release without a customer bundle
retains its historical projection; adopting the new bundle in an existing project
requires review of existing instructions rather than an automatic overwrite.

Acquisition verifies the complete upstream release before projection. For releases
with a customer bundle, materialize its AGENTS.md, CLAUDE.md and skills in the
customer root and stage those exact replacements in the Git index. Staging makes
ordinary customer commits/clones retain the product and prevents sparse reapply
from restoring owner defaults. Record the substitution inventory in the receipt;
that inventory is bookkeeping, never a replacement for independently acquired
release baselines. Preserve upstream history and the read-only upstream fence.
The external bootstrap remains self-contained.

## File classification

| Input                                                                  | Update ownership                                                                              |
| ---------------------------------------------------------------------- | --------------------------------------------------------------------------------------------- |
| src/runa                                                               | Exact independent original-release integrity; mismatch blocks unconditionally                 |
| src/app, docs/app                                                      | Customer; preserve and report manual reconciliation where needed                              |
| Composed dependency manifests and locks                                | Customer build inputs; preserve, validate compatibility                                       |
| Private environment, storage, database, backup, cache, generated files | Preserve; never report contents                                                               |
| Customer AGENTS/CLAUDE/skills                                          | Delivered defaults with customer extensions; overlapping local changes require reconciliation |
| Other delivered regular files and safe symlinks                        | Review original/local/target identity, type and executable mode; exact approved actions only  |
| Local additions absent from both releases                              | Preserve; never infer disposal permission                                                     |
| Owner-only source                                                      | Excluded from customer projection                                                             |

Inventory release-tracked delivered paths and customer tracked additions, without
walking dependency/runtime trees. Inspect only eligible delivered local inputs;
private environment values never enter a report. Symlink parents, unsupported
objects and unsafe paths fail closed. Local additions at an upstream-added path
are conflicts, not permission to overwrite arbitrary customer content.

## Review and execution

Three-way identities distinguish local edits/deletions/additions from target
changes. Reports contain paths, categories, actions, conflicts, affected services
and workspace impact. Consent binds installed and target release identities plus
the entire local/action inventory. Continue authorizes only reported eligible
replacements/removals; instruction extensions remain manual reconciliation.
Missing consent or abort leaves installation source/data unchanged. Revalidate
under the existing operation lock before mutation.

Extend the existing journal with per-file before/after identities and progress.
Stage target bytes before replacing files atomically; save previous bytes and
record transitional states for interrupted rename recovery. Resume verifies
installed/staged/previous identities and cannot waive protected source refusal.
Source recovery does not reverse database changes. Production still deploys the
complete reviewed customer commit, never upstream updates.

## Knowledge and environment boundaries

Owner retrieval explicitly excludes the customer authoring tree. Fresh customer
Compose projects use empty project-scoped Qdrant storage and only delivered
customer sources; reopen preserves it. No existing owner/customer volumes or
model caches may be cleared for acceptance. Production remains AI-free.

Keep Laravel runtime credentials, Compose settings and PostgreSQL initialization
secrets distinct. Audit creators/readers and permissions without logging values.
An unexplained private backup remains preserved; absence of a code consumer alone
does not authorize deleting it or silently rotating database roles.

## Verification ownership

Implementation creates regression scenarios and synchronizes guidance. Only
small, fast diagnostics resolving a concrete uncertainty run before the final
point. Final integrated acquisition/clone/update/recovery/knowledge/production
acceptance runs through the owner quality coordinator. Its background handoff uses
python3 tools/maintainer/quality-status; unrelated processes are not tracked there.

## Instruction review record (2026-10-23)

All nine existing skills, their references and provenance were reviewed for reader/task scope. Owner references retain platform implementation detail. Customer skills were rewritten into one concise application reference per domain rather than copying owner procedures:

| Domain     | Customer disposition                                                                                               |
| ---------- | ------------------------------------------------------------------------------------------------------------------ |
| Backend    | Ordinary Actions/Services/Queries and optional guarded module consumption; public handbook owns detailed protocols |
| Laravel    | App providers/config/routes and native generators; application-only tests/data commands                            |
| Frontend   | Public facade catalogue and composition; repeated application controls stay in App; no platform extraction         |
| Vue        | Strict SFC/reactivity/async contracts and the real /api-prefixing request wrapper                                  |
| Tailwind   | Installed syntax/tokens/static discovery with application-owned styling                                            |
| PostgreSQL | Application ownership, strong constraints, migration safety and optional app-module boundaries                     |
| Docker     | Supported lifecycle, teammate preparation, preserved volumes and customer-commit deployment                        |
| RAG        | Delivered-only corpus, incremental freshness, independent optional startup; no runtime redesign permission         |
| Security   | Server authority, safe boundaries, supplied scanners and unconditional protected-source refusal                    |

Customer provenance retains the existing research pins with explicit local adaptation dates, without claiming a new upstream review. AGENTS remains the authority; CLAUDE is a thin @AGENTS.md adapter in each product. Representative use cases route ordinary CRUD to App, optional module generation to app.code, shared UI reuse to the facade and application wrappers, tests to application or owner entrypoints by scope, and final monitoring to the matching coordinator status. Missing-command one-off diagnostics require an explanation and observed results, not an invented permanent command. Full regression execution is reserved for final verification.
