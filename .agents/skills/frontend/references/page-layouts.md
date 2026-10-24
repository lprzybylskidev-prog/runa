# Page Layouts reference

The authoritative delivered contract is [the public page-layouts guide](../../../../docs/runa/handbook/development/frontend/page-layouts.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

For delivered login and restricted-session screens, use the [Auth entry contract](../../../../docs/runa/handbook/modules/auth/entry-ui.md) and its public `@/runa/auth` composition. Preserve server-driven restrictions, explicit token confirmation and flow cleanup.
