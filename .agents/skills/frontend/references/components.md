# Components reference

The authoritative delivered contract is [the public components guide](../../../../docs/runa/handbook/development/frontend/components.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

For public screen/layout composition and full replacement, use [frontend
adoption](../../../../docs/runa/handbook/development/frontend/customization.md).
New platform views require usable defaults and a documented public replacement
contract in the same point. Keep presentation separated from reusable controllers;
export supported contracts through facades rather than private imports.

Use public Avatar for repeated profile-image/initials presentation. Forward the
ApplicationLayout userAvatar prop in wrappers, and consume current profile display
through useApplicationSession rather than copying private profile orchestration.
Profile/image presentation is never authentication or authorization state.

Auth authenticator setup uses public AuthTotpSetup from `@/runa/auth` for private
QR/manual-key presentation; see the [presentation contract](../../../../docs/runa/handbook/development/frontend/customization.md).
