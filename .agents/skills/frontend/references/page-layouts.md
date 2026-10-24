# Page Layouts reference

The authoritative delivered contract is [the public page-layouts guide](../../../../docs/runa/handbook/development/frontend/page-layouts.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

For delivered login and restricted-session screens, use the [Auth entry contract](../../../../docs/runa/handbook/modules/auth/entry-ui.md) and its public `@/runa/auth` composition. Preserve server-driven restrictions, explicit token confirmation and flow cleanup.

Use the [Auth application/account contract](../../../../docs/runa/handbook/modules/auth/account-ui.md) for welcome policy, post-security
navigation, shared-shell profile/sign-out, real-activity expiry and safe memory draft
review. Preserve application-owned composition and server guards; never replay a
write after login or persist credentials/drafts in browser preferences.
