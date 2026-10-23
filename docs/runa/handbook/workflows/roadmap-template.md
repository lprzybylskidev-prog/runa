---
id: RM-YYYY-NNN
title: Replace me
status: draft
branch: roadmap/RM-YYYY-NNN-replace-me
created_at: YYYY-MM-DD
activated_at: null
closed_at: null
release_type: feature
proposed_version: 0.1.0
quality_groups: docs
quality_rationale: Documentation-only changes; choose groups from the actual agreed scope.
impact_areas:
  - documentation
---

# RM-YYYY-NNN — Replace me

## Overview

### What

### Why

### Scope

### Out of scope

## Specification

### Decisions

- YYYY-MM-DD — Decision and rationale.

## Implementation plan

Implementation points produce code, tests and documentation; only small, fast checks for a concrete uncertainty may run there. Put full suites, broad acceptance, analysis, builds and scanner pipelines in the final point after all implementation. When reviewing any supplied roadmap, stop and notify the user if it requires these earlier; follow the roadmap workflow before activation or execution. Mandatory commit hooks still apply.

- [ ] 1. First coherent implementation point.
  - Prerequisites: explicit inputs or none.
  - Completion: outcome and relevant durable documentation.
- [ ] 2. Next coherent implementation point.
  - Prerequisites: completed earlier points only.
  - Completion: outcome, test code, synchronized documentation and any necessary small, fast, focused check.
- [ ] N. Full verification, release finalization, merge, and final report.

## Execution handoff (optional)

Use only for a substantial host/container transition; follow `docs/runa/handbook/workflows/roadmaps.md`.

- Context: roadmap/point, branch/base commit, worktree state, timestamp/direction.
- Purpose/progress: reason, completed work/checks, evidence paths, remaining tasks.
- Environment: verified host checkout or unknown, Compose project/files, tools/services, observed identities/state.
- Procedure: exact startup commands/source links, intended targets, protected resources (no secrets).
- Acceptance: verification commands, host completion criteria, remaining gates.
- Return: results/issues, cleanup/preservation, current service/configuration state, safe-to-return status.
