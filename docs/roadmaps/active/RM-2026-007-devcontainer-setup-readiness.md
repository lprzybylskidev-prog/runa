---
id: RM-2026-007
title: Devcontainer setup readiness repair
status: active
branch: roadmap/RM-2026-007-devcontainer-setup-readiness
created_at: 2026-10-20
activated_at: 2026-10-20
closed_at: null
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

- [ ] 1. Repair setup readiness and regression coverage.
  - Verify current generator outputs, correct the stale readiness path, cover valid existing setup, missing output and stale fingerprint, preserve failure semantics, update development guidance and verify actual containerized fast setup/reopen preparation. Commit the coherent correction.
- [ ] 2. Full verification, release finalization, merge and final report.
  - Review affected current-state docs and run the complete selected local gate including development and strict AI checks. Insert fix points/rerun on failures. Finalize patch metadata, close roadmap, refresh knowledge/docs/release checks, merge/main sanity, annotated tag/push/GitHub Release and branch deletion. End the turn after starting a background final gate.

## Execution context

Native host checkout: /home/lprzybylski/Documents/php/runa. Development Compose project runa-dev, compose.yaml plus .devcontainer/compose.resources.yaml and private .env.dev.local. Use existing app container for PHP/Composer/pnpm. Host recovery ./bin/dev up and ./bin/dev doctor passed before activation. Preserve all development/test data, caches, backups, reports and other projects; no destructive reset is needed. Installed VS Code reopening is owner-observed separately from CLI preparation.
