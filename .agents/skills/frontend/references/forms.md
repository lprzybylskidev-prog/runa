# Forms reference

The authoritative delivered contract is [the public forms guide](../../../../docs/runa/handbook/development/frontend/forms.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

Use outlined submit buttons for forms, retaining destructive tone/busy/disabled
states. Use public TextLink for quiet welcome/Auth form navigation: no permanent
underline or button surface, hover underline and visible keyboard focus. Reuse the
shared implementation; do not restore page-local copies.
