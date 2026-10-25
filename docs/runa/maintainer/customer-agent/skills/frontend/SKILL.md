---
name: runa-frontend
description: Runa customer guidance for accessible responsive UI, public components, forms, tables and page patterns.
---

# Runa application frontend

Load for accessible responsive UI, public components, forms, tables and page patterns. Read the active application roadmap, query available local knowledge and inspect analogous App code. Project rules override generic advice. Load references/application.md only when needed. Preserve protected platform source.

Maintain application patterns and current docs in the same point. Use docs/app for domain records, the delivered handbook for Runa contracts, and supported command entrypoints. Only necessary small, fast diagnostics belong before final verification; receive their results and continue. Full suites and broad acceptance belong to final quality, whose background status interface is runa:quality:status. Never replace supported tests with internal or detached log-only runners.

Use docs/runa/handbook/development/frontend/customization.md for every Runa/module
view, including future modules: retain defaults, compose public layouts/components
or own the frontend in src/app through supported contracts. Keep private platform
source protected and reuse security/session controllers rather than copying them.

For data views, follow the public forms guide's automatic read reconciliation
contract: refresh reads in place, merge clean/disjoint values, preserve dirty drafts
and use local decisions only for overlapping/ambiguous changes. Share aggregate
mutation queues/revisions. Never replay failed writes, advance an unreconciled token,
reinitialize an edited form or demand a page reload after a successful refresh.

For custom initial account loading, use the public AuthAccountLoading or the
accountLoading presentation entry; see the public Auth account UI/customization
guides. Loading presentation must not mount protected controllers before session
validation.

For externally fetched client Table rows, pass the request loading state through
the public loading prop; see the public tables guide.

Shared Table owns query-change loading feedback in client/server mode. Do not add
page-local loading timers or duplicate reads; follow the public tables guide.

MinimalLayout alone enables sparse constellation-point glow and occasional
brand-illustration point laps. Keep ApplicationLayout
static and use the public layout opt-out/motion contract when wrapping layouts.
