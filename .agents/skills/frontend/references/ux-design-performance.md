# UX and performance review

For visual/task hierarchy select the [UI standards map](../../../../docs/runa/handbook/design/visual-direction.md#ui-standards-and-guidance-map)
and the [component/UI behavior and performance sections](../../../../docs/runa/handbook/architecture/frontend.md#componentui-behavior).
Review the actual user's task, state ownership and analogous consumers; avoid
speculative splitting, virtualization or new abstractions.

For slow views inspect request owners and measure render, request, table, image or
bundle costs before optimizing. Follow [read-path performance](../../../../docs/runa/handbook/architecture/application-architecture.md#read-path-performance)
and [readiness/reconciliation](../../../../docs/runa/handbook/development/frontend/loading.md).
Retain trailing invalidations, current authority and dirty drafts when coalescing.
A source suspicion is not a measured bottleneck; record evidence and unknowns.
