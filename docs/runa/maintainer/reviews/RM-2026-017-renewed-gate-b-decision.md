# RM-2026-017 — Renewed Gate B Owner Approval

I have reviewed the renewed baseline audit synthesis and the proposed reconciliation of RM-2026-017.

The renewed audit is accepted as an adequate evidence-based baseline for continuing the roadmap, with its explicitly documented limitations.

This approval does not certify complete runtime, security, accessibility, performance or test-suite correctness.

Those obligations remain assigned to the appropriate implementation and final verification stages.

## 1. Gate B decision

**APPROVED.**

Accept the renewed baseline audit and the proposed finite correction scope.

Approve RA-01 through RA-07 for implementation according to their owning roadmap points.

Preserve the existing original findings, accepted process decisions and historical evidence.

Do not manufacture additional corrections merely to increase the number of changes.

## 2. RH-01 — Remember token

**Decision: RETAIN `runa_auth.users.remember_token`.**

Runa currently uses its own authentication and remember-me mechanisms.

However, Runa is a Laravel-based platform, and customers or application developers may reasonably choose to integrate or restore standard Laravel authentication behavior.

The database should not unnecessarily remove a standard Laravel authentication compatibility field simply because Runa's current authentication implementation does not actively use it.

Preserve the column and its compatibility contract.

Do not treat current non-use as sufficient evidence of dead schema.

Document this retention decision and its architectural rationale.

Do not claim that preserving the column alone guarantees compatibility with every alternative Laravel authentication configuration.

Any future use of the standard Laravel remember-me mechanism must preserve the applicable session, authentication, revocation and security guarantees.

No migration or schema removal is authorized for RH-01.

## 3. RH-02 — Notification dismissal

**Decision: APPROVED FOR CORRECTION.**

Changing the application language must not redisplay a notification that the user has explicitly dismissed.

Required behavior:

- A currently visible notification updates its text when the language changes.
- The update must preserve relevant focus and component identity.
- A previously dismissed notification remains dismissed.
- A deliberate new operation or submission attempt may create a new notification.
- Do not introduce unnecessary global state or a new notification framework.

Preserve the existing valid A-08 correction.

Add an appropriate regression covering dismissal followed by PL/EN language changes.

## 4. RA-01 — Repeated database reads

**APPROVED FOR INVESTIGATION AND TARGETED REPAIR.**

The repeated SQL reads represent a justified optimization target.

Before modifying the implementation, define the exact owning read-scope and transaction/freshness design.

Preserve:

- Authorization freshness.
- Current module availability.
- Transaction boundaries.
- Lock ownership.
- Rollback behavior.
- Same-connection invalidation.
- Reconnect behavior.
- Long-lived worker safety.
- Audit and security invariants.

Do not introduce a persistent authorization or eligibility cache.

Do not remove necessary security checks merely to decrease query counts.

Use comparable before/after SQL evidence and meaningful regression tests.

If the proposed solution requires significant architectural changes or introduces unresolved correctness risks, stop and request owner approval before implementation.

## 5. Previous implementation points

The existing commits from points 2–6 are retained as implementation candidates.

Do not revert correct work merely because the initial audit was incomplete.

However, previous completion declarations do not automatically restore acceptance.

For each point:

1. Reconcile its existing implementation against the renewed audit.
2. Complete all approved additional corrections.
3. Verify its actual acceptance criteria.
4. Preserve relevant tests and evidence.
5. Record the renewed completion decision in the roadmap.

In particular, the earlier no-backend-repair conclusion is superseded by RA-01.

The earlier shared-frontend completion conclusion is superseded by the renewed component and interaction findings.

## 6. Remaining audit limitations

Accept the documented distinction between baseline audit evidence and future runtime verification.

Do not reinterpret source inspection as a native test PASS or rendered accessibility certification.

Complete the relevant practical skill evaluations, component tests, stateful security checks, migration and update verification, performance comparisons, browser scenarios and infrastructure checks in their assigned roadmap stages.

Preserve explicit evidence of any remaining unknowns.

## 7. Execution authorization

You are authorized to restore Gate B approval and resume RM-2026-017 according to its ordered implementation workflow.

Begin with point 2 and reconcile each subsequent point in sequence.

Previously completed work may be retained, but no point may be marked complete until its renewed requirements and acceptance criteria are satisfied.

Continue to follow mandatory owner approval requirements for major architectural decisions or scope expansions.

The mandatory manual frontend acceptance checkpoint and final verification remain unchanged.

Preserve the complete roadmap history, including the withdrawn original Gate B approval, the restart, the renewed audit and these decisions.
