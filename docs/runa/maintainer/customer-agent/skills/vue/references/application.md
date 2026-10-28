# Application vue guidance

Use Vue 3 SFCs with script setup lang=ts, strict payload types, typed props/emits and stable semantic keys. Props stay read-only; explicit local drafts are intentional. Keep computed pure, use watch for side effects, cancel obsolete async effects and ignore stale responses. Dispose listeners, timers and observers. Route parameters are inputs; account for reuse without remounting. Use composables for real reusable state with explicit inputs/outputs, not hidden global stores. Add no state library without an established shared need and dependency approval. Avoid any for known payloads, untrusted v-html, index keys for editable data and needless optimization. Unexpected Vue warnings fail acceptance.

Use the shared request wrapper in src/runa/resources/js/lib/http.ts through the established project alias. It prefixes /api; pass resource paths such as /documents, not /api/documents. Decode unknown payloads at the boundary. Preserve cancellation and stable safe ApiError handling. Frontend checks use runa:test:frontend and runa:test:e2e via documented transport; internal runners do not replace them.

Use docs/runa/handbook/modules/auth/preferences.md for the delivered AuthPreferences/
AuthDelivery contracts. Declare exact source-owned preference schemas and notification
categories, use optimistic revisions, and keep account caches/requests isolated on
logout or switch. Legacy guest adoption requires explicit ownership confirmation;
server values win and reset never resurrects legacy state. Do not put credentials,
drafts or permission state in preferences, disable mandatory primary security mail,
cache recipients across jobs, or import Auth private SQL/models. Reference-only
managed security mail rechecks state and uses a persisted single SMTP attempt;
critical recovery retains direct mail. Application code consumes these contracts
without requiring conversion into an app module; protected Runa remains read-only.

For all current/future Runa module views use
docs/runa/handbook/development/frontend/customization.md. Runa supplies usable
defaults plus documented public presentation replacement. Customer screens,
wrappers and CSS belong in src/app and survive reviewed updates/clones. Use typed
public facades/controllers, preserve identity/page cleanup, CSRF, restrictions,
reauthentication and explicit retries; never copy private security orchestration.
Native errors and generated emergency 503 share one static, CSP-constrained
presentation build. Keep the application scaffold minimal; domain pages are added
only for real customer requirements.

Separate initial document/UI application of persisted preferences from persistence.
Mounting a locale/theme consumer while account hydration is pending must not write
defaults or manufacture save failures. Apply initial display once, subscribe to
hydration without echo writes, and persist subsequent deliberate changes. Keep
actual rejected-write feedback and account-switch/late-response fencing intact.

Use the shared HTTP document-cancellation contract for requests on views being
left: RequestCancelledError from @/runa/http cancels observation and must not become
false error feedback or an automatic write retry. Preserve real network/API errors,
synchronous dirty-form guards and explicit state reload after interrupted writes.
Do not add page-local raw fetch/unload handlers to bypass the shared lifecycle.

For view refresh, follow the public forms guide's automatic read reconciliation.
Coalesce reads, fence late snapshots, clean up listeners, merge against confirmed
baselines and preserve dirty refs; component remount/reset is not reconciliation.

For module components use runa:make:module and the public module development guide.
The generated frontend/components/<snake_case>.vue uses the delivered opt-in build
plugin; page/slot declarations remain explicit. Consume public facades from owned
application code and preserve generator preview/exact-hash overwrite review.
