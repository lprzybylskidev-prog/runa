# Application rag guidance

Read docs/runa/handbook/development/knowledge.md and ai/knowledge-system.md. Query local knowledge before broad loading; check availability/freshness once. On failure, lock or staleness, explain and use focused source searches without bootstrapping AI merely to navigate. Open original sources before applying significant retrieved rules. Stage durable App/handbook/installed instruction changes, then run runa:knowledge:index incrementally; do not rebuild for normal edits. Keep chats/secrets/runtime/dependencies and copied owner material out of the corpus.

Fresh projects use empty project-scoped Qdrant and first-start initialization from delivered customer sources only. Never import owner vectors/snapshots or clear existing project/model caches for a fresh-install proof. Reopen preserves state; optional AI failure does not block application readiness. Dedicated doctor/eval and selected final AI gates remain strict. Use supported search/status/stale/index/doctor/eval commands, explain only for diagnosis and rebuild only for an explicit model/schema/repair need. Production contains no knowledge runtime or commands. Application agents maintain their local domain guidance; they never alter owner authoring templates.

After a delivered tooling/parser update, a stale parser fingerprint normally needs
incremental `runa:knowledge:index`; it does not itself require `rebuild`. Follow the
public knowledge guide's package/ownership composition procedure. Customer loaders
use delivered resources only; private owner corpus/golden additions are excluded.

For explicit purpose, nullable applicability and source-owned decision declarations,
follow `docs/runa/handbook/development/knowledge.md#applicability-and-decision-annotations`.
Metadata refresh reuses semantic vectors; unknown dates/rationale remain unknown.
