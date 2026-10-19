# Table and List Patterns

Record canonical source examples as lists are implemented; none exist yet.

Reuse filters, statistics, pagination, sorting, row/bulk actions and loading/empty/error states. Planned RM-2026-004 order is heading/description/actions, breadcrumbs, optional statistics, optional toolbar/filters, then mandatory content, with an optional supplementary panel. Statistics/Statistic now implement the global/filtered user switch, KPI slots and optional per-view mode persistence through useStatisticsMode; providers/calculations remain external. ApplicationReview demonstrates integration above its toolbar. Pagination is available from point 10; the table implementation remains deferred. Preserve mobile access to data and actions, including horizontal scrolling or an intentional responsive presentation when needed. Respect the canonical API pagination/filter contract and finite page sizes. Update this reference when a reusable pattern changes.
