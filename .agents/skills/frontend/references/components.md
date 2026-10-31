# Components reference

Use for controls, exported props/events/handles and component reuse.

The authoritative delivered contract is [the public components guide](../../../../docs/runa/handbook/development/frontend/components.md).
For screen/controller replacement read [frontend adoption](../../../../docs/runa/handbook/development/frontend/customization.md);
for exact module behavior follow its linked public guide. Maintain contracts and
examples there, with application-specific patterns in local App guidance.
Customer composition never authorizes protected platform edits or copied security logic.

For nested subview links, use public PageNavigation and the UI-owned host contract
in `docs/runa/handbook/development/frontend/page-layouts.md#page-level-navigation-composition`;
reuse the shell renderer rather than adding a content tab/dropdown implementation.
