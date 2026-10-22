# Backend Design Principles

1. Fail fast at system/input boundaries when critical assumptions are invalid.
2. Keep business logic independent from transport/framework Request/Response objects when reasonable.
3. Define an explicit source of truth for important data/state.
4. Keep dependency direction readable. Introduce interfaces only for real boundaries, swaps, or integrations.
5. Translate errors at boundaries rather than leaking infrastructure exceptions into API/user contracts.
6. Design failure paths together with the happy path.
7. Never swallow errors silently.
8. Keep command/write behavior and query/read behavior semantically clear without implementing ceremonial full CQRS.
9. Make side effects explicit.
10. Do not create `Helpers`, `Utils`, `CommonService`, or similar junk drawers.
11. Use a Rule-of-Three mindset for abstractions. Limited duplication is preferable to speculative generalization.
12. Comments explain why, constraints, or non-obvious choices; they do not narrate obvious code.
13. Do not leave AI-generated change-history/narrative comments in source.
14. Prefer meaningful names over vague `data`, `result`, `tmp`, or unexplained abbreviations.
15. Use one stable term per concept across the codebase.
16. Do not generalize for hypothetical future use.
17. Prefer explicit behavior over magic for important flows.
18. Global scopes are for truly global invariants, not hidden business filtering.
19. Observers/model hooks must not own critical business workflows.
20. Public methods should expose a small meaningful surface.
21. Prefer explicit dependency injection over service locators.
22. Limit mutable static/global state.
23. Project conventions always outrank generic skill preferences.

## Runa application-layer mapping

Use the existing architecture before inventing another layer:

- Controller — HTTP/transport boundary.
- Form Request — non-trivial input validation.
- Action — substantial application use case/state transition/orchestration.
- Service — shared/cross-cutting/integration capability with a clear responsibility.
- Query — complex reusable read/list/report behavior.
- JsonResource — non-trivial response contract.
- DTO — complex multi-layer/integration input/value contract where it improves meaning.

Do not add a repository layer over Eloquent by default.

Do not introduce a new abstraction because one method is long. First identify whether the responsibility itself is wrong.

## Module framework design boundary

For module framework work, consult the [public module contracts](../../../../docs/runa/handbook/architecture/module-framework.md) and active roadmap. Use explicit owner-scoped registry synchronization during installation/deployment; request boot and snapshot reads never discover or mutate registrations. Source version is not installed data version. Modules supply health and transition/work decision policies; the base enforces the shared protocol, fences and state revisions without guessing business behavior. Use `ModuleGraph` for pure dependency/cascade previews over one registry snapshot. A transition publisher must reacquire the snapshot under its publication lock and revalidate the complete preview; graph validity alone is not health, installed-data or preparation readiness. The contract document distinguishes delivered discovery/graph planning from planned transition/operational adapters.
