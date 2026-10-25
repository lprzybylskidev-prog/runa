---
id: RM-2026-012
title: Optional Throttling module and Auth ownership handoff
status: draft
branch: roadmap/RM-2026-012-optional-throttling
created_at: 2026-10-25
activated_at: null
closed_at: null
release_type: feature
proposed_version: 0.6.0
impact_areas:
  - backend
  - security
  - documentation
---

# RM-2026-012 — Optional Throttling module and Auth ownership handoff

## Overview

### What and why

Deliver the future optional Throttling module as an independent roadmap after
RM-2026-011. The owner has agreed its optional nature and exclusive request-limit
responsibility when enabled. RM-2026-011 supplies only the narrow Auth adapter and
repairs the current overbroad Auth cap; it does not implement this module.

### Scope and non-goals

Design and deliver the optional general request-limiting module, its Auth bridge,
current-state documentation and lifecycle/ownership regression coverage. Do not
activate from this draft or implement through the active Core roadmap. No broad
business request policy, limiter store, administration UI or new dependency has
been approved. Those choices require agreement before activation.

## Specification

- Absent/unregistered/disabled Throttling retains Auth's targeted local request cap.
- Enabled and available Throttling exclusively owns classified Auth request limits
  through `Runa\Contracts\Modules\AuthRequestThrottle`; never also consume the
  built-in source bucket or count again in global module middleware.
- Recheck lifecycle/availability on every invocation, including long-lived workers.
  An enabled integration failure remains a failure rather than a false claim of
  absence and silent weaker fallback.
- Auth retains credential/proof validity, one-time use, replay prevention, eligibility
  and authorization. Its intrinsic login/proof abuse windows remain in place;
  relocating their storage/ownership requires an explicitly agreed protocol design.
- Optional availability must not create a way to disable both request-limit owners.
- Ordinary business application work has no blanket Auth unsafe-method bucket.
  Any future application/module policies must be explicit, scoped and agreed.
- Use existing guarded module contracts and source-owned declarations. New modules
  develop through 0.x.y and receive 1.0.0 only at verified creation-roadmap closure.
- Preserve secret-safe keys/responses, bounded 429/Retry-After behavior and audit
  requirements for meaningful policy mutations. New dependencies require approval.

### Open decisions before activation

Agree public policy/declaration API, supported scopes, limit algorithms/persistence,
concurrency/failure semantics, lifecycle/data retention, operational surfaces,
Auth-specific policy settings and any UI or infrastructure scope. Confirm release
metadata and refresh the projected version if roadmap ordering changes.

### Decisions

- 2026-10-25 — Owner requests the future optional module to take responsibility
  for throttling when enabled. Captured during RM-2026-011 correction 19zw.
- 2026-10-25 — Agent proposes feature 0.6.0 as a MINOR increment after the active
  roadmap's confirmed 0.5.0. These metadata are provisional, not owner-approved;
  this draft is not ready for activation. Confirmation and activation are separate.

## Implementation plan

- [ ] 1. Agree unresolved policy/architecture and release metadata before activation;
      finalize module specification and scoped acceptance scenarios.
- [ ] 2. Implement the optional module and source-owned contracts/storage with test
      code and synchronized documentation. Run only necessary focused diagnostics.
- [ ] 3. Implement exclusive Auth takeover, disable/absence fallback and enabled
      failure behavior with lifecycle/concurrency regression code and instruction-product
      updates. Preserve intrinsic Auth safeguards and ordinary application usability.
- [ ] 4. Full integrated verification, current-state documentation review, module/
      platform release finalization, merge and report under maintainer workflows.
      Choose and preview proportionate quality groups here; fix failures through inserted
      implementation points and repeat final verification. No broad gates run earlier.
