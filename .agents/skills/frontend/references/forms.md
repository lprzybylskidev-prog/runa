# Forms reference

The authoritative delivered contract is [the public forms guide](../../../../docs/runa/handbook/development/frontend/forms.md). Load it only for the current task. Maintain platform capability contracts there and owner-specific implementation decisions in the active owner roadmap. Customer application patterns belong in their local App guidance; extracting application controls never authorizes protected-source edits.

Use outlined submit buttons for forms, retaining destructive tone/busy/disabled
states. Use public TextLink for quiet welcome/Auth form navigation: no permanent
underline or button surface, hover underline and visible keyboard focus. Reuse the
shared implementation; do not restore page-local copies.

Use public FormNotifications for successful saves and form-wide failures only in
the top-right viewport. Do not duplicate outcome banners above forms. Preserve
associated field errors, persistent failure/recovery actions and explicit retries;
increment attempt on each submit. Modal notifications stay within modal focus.

Follow the public forms guide for independent Card forms, uneven-height column
stacks, explicit security-method actions and confirmed destructive removal.

Integrated editable collections retain their own draft and submit boundary; use
the public feature controller without temporarily rebinding the parent form's
fields during collection requests. See Auth's saveSecondary presentation contract.

Use the public forms guide's automatic read reconciliation and shared FormConflict
for current/future data views. Refresh current reads without discarding drafts;
resolve only overlapping/ambiguous changes locally and never retry writes silently.

Keep form submits with their fields using spacing, without a horizontal separator
above actions. Use the shared Form actions slot; reserve separators for independent
sections or operations. See the public forms guide.
