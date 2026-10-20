---
id: RM-2026-007
title: Devcontainer setup readiness repair
status: closed
branch: roadmap/RM-2026-007-devcontainer-setup-readiness
created_at: 2026-10-20
activated_at: 2026-10-20
closed_at: 2026-10-20
release_type: fix
impact_areas:
  - development
  - tooling
  - documentation
  - ai_environment
---

# RM-2026-007 — Devcontainer setup readiness repair

## Overview

Restore the existing lightweight setup path on ordinary devcontainer reopen. Setup checks a retired emergency-document filename and unnecessarily repeats Composer installation and frontend builds. The observed Composer SIGSEGV disappeared on ordinary host recovery; no root cause for that native crash is established.

Scope: correct readiness detection, test fast/full setup behavior and preserve failure propagation, synchronize development guidance, verify and publish the normal patch release. No dependency updates, PHP/image changes, database resets, production changes, automatic crash retries or unrelated roadmap activation.

## Specification

With a matching fingerprint and current required generated assets, setup --if-needed validates configuration/migrations and registers hooks/MCP without Composer installation or frontend rebuilding. Missing required output or a changed fingerprint still runs complete setup. Setup failures propagate and never create a success marker. Check the actual generated 503-pl-system.html instead of retired 503-pl.html. Keep application startup independent of optional knowledge services and preserve all existing data/caches/backups/reports.

### Decisions

- 2026-10-20 — Owner explicitly requested creation, activation, repair and closure of this fix roadmap in the same instruction. This authorizes activation once its concrete scoped draft is written; it is not agent self-approval. Planned patch is 0.2.1 from current VERSION 0.2.0.
- 2026-10-20 — Treat the filename defect and the observed native Composer SIGSEGV separately. Repair the confirmed unnecessary-setup defect; do not claim the native crash has been diagnosed or fixed.

## Implementation plan

- [x] 1. Repair setup readiness and regression coverage.
  - Verify current generator outputs, correct the stale readiness path, cover valid existing setup, missing output and stale fingerprint, preserve failure semantics, update development guidance and verify actual containerized fast setup/reopen preparation. Commit the coherent correction.
- [x] 2. Full verification, release finalization, merge and final report.
  - Review affected current-state docs and run the complete selected local gate including development and strict AI checks. Insert fix points/rerun on failures. Finalize patch metadata, close roadmap, refresh knowledge/docs/release checks, merge/main sanity, annotated tag/push/GitHub Release and branch deletion. End the turn after starting a background final gate.

## Execution context

Native host checkout: /home/lprzybylski/Documents/php/runa. Development Compose project runa-dev, compose.yaml plus .devcontainer/compose.resources.yaml and private .env.dev.local. Use existing app container for PHP/Composer/pnpm. Host recovery ./bin/dev up and ./bin/dev doctor passed before activation. Preserve all development/test data, caches, backups, reports and other projects; no destructive reset is needed. Installed VS Code reopening is owner-observed separately from CLI preparation.

## Point 1 execution record

- Corrected the stale readiness sentinel in bin/dev to the generated 503-pl-system.html. No changes to Composer/PHP flags, dependencies, normal migrations, setup fingerprint semantics or optional AI topology.
- Added regression coverage demonstrating both failures before the fix: unnecessary full setup with valid current output, and incorrectly taking the shortcut when only the retired filename exists. After correction all 13 startup contracts pass, including stale fingerprint/full setup and existing failed-setup marker protection.
- Actual containerized full setup passes after the input change. A second setup --if-needed reports dependency installation/frontend build skipped while retaining configuration/migration and MCP checks. Host ./bin/dev up and ./bin/dev doctor pass; app ID/start time are unchanged across reopen preparation. Composer SIGSEGV did not recur; its native cause remains unproven. Installed VS Code GUI attachment is not claimed by CLI checks. Local evidence: .runtime/rm007-setup-refresh.log, rm007-setup-fast.log, rm007-reopen-preparation.log, rm007-doctor.log and app-before/app-after files.
- Updated current environment guidance. No task-created disposable Docker resources or data resets; development/test databases, knowledge/model caches, backups, reports and other projects remain preserved. Existing services remain running.

## Final verification source review

Reviewed README checked state, current VERSION, bin/dev setup/fingerprint/locking and Compose application/optional-AI ordering, current generated native-error output, startup contracts, docs/development/environment.md and editor-and-agents.md, command guidance and Docker lifecycle skill, plus roadmap/release/quality workflows. Guidance now identifies the real generated sentinel and fast/full behavior; no other capability correction is needed for this bounded repair. Closed roadmaps remain historical/immutable. The complete selected final gate passed; release closure records no active roadmap and no successor activation.

## Final report

RM-2026-007 delivers fix release 0.2.1. Development setup now recognizes the actual generated 503-pl-system.html readiness output. A matching fingerprint and valid assets retain the lightweight configuration/migration/hook/MCP path; missing output or stale inputs still trigger full setup, and failures still propagate. Regression tests reproduced the old defect before correction and all 13 startup contracts pass afterward. Actual full/fast container setup and host startup/doctor passed; the app container ID/start time remained unchanged across reopen preparation. VS Code GUI attachment remains owner-observed, not inferred from CLI success. The observed Composer SIGSEGV did not recur; no native crash root cause or PHP-level fix is claimed.

All 22 selected final quality nodes passed on 765c140f350fe6d511600249e242d699ab4d2652, including backend tests, formatting/lint/PHPStan, dependency/secret checks, hook/runner/startup/release/docs contracts, development health and strict knowledge doctor/evals/Boost validation. Local full evidence is .runtime/quality; setup/recovery evidence is listed above. Current-state documentation was reviewed and synchronized in point 1. Post-gate edits are limited to release metadata: VERSION/CHANGELOG/README and roadmap closure/evidence. Focused docs/release validation and knowledge synchronization apply under the release workflow, followed by merge/main sanity and immutable annotated tag/GitHub publication.

No new dependency, PHP/image change, migration, production behavior or real deployment was introduced. No disposable Docker containers/networks/images/volumes were created for the fix, so no cleanup was required. Existing development/test services and data, knowledge/model caches, backups, verification reports and other projects are preserved. Release metadata is 0.2.1 with no active roadmap; RM-2026-005/006 remain drafts.
