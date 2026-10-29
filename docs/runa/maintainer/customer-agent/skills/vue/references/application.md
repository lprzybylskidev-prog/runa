# Application vue guidance

Use Vue 3 TypeScript SFCs with script setup, typed props/emits and read-only props. Keep computed pure and drafts explicit, use stable keys, cancel stale observations and clean listeners/timers. Never silently replay writes or remount an edited form to refresh it.

Load only the relevant canonical procedure:

- Public facades and presentation lifetime: `docs/runa/handbook/development/frontend/customization.md`.
- Forms/read reconciliation: `docs/runa/handbook/development/frontend/forms.md`.
- Account preferences/hydration: `docs/runa/handbook/modules/auth/preferences.md`.
- HTTP, cancellation and state: `docs/runa/handbook/architecture/frontend.md`.

Maintain application-specific patterns in local guidance/docs/app. Ordinary App
code and optional app modules are supported; copied owner files or a Contracts
namespace never authorize platform/internal APIs. Preserve installed local
extensions during reviewed updates. This default contains no owner-only procedure.
