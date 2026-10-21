# Owner procedure and historical evidence

Source classification: `docs/runa/handbook/design/visual-direction.md`. These owner-only procedures and dated execution records are retained separately from customer guidance. Historical claims keep their original context; current public operations are documented in the handbook.

Status: root view and implemented visual direction accepted on 2026-10-18 under closed RM-2026-001, point 16. [Active RM-2026-004](../roadmaps/active/RM-2026-004-ui-foundations.md) now plans reusable UI/layouts. Planned requirements below describe the target, not delivered components; visual implementation has separate owner acceptance gates. Authentication and business/admin behavior remain deferred.

Roadmap: [RM-2026-001, point 16](../../maintainer/roadmaps/closed/RM-2026-001-development-environment.md).

Combine selected features from subsequent references into a coherent interface. Do not treat any single reference as the complete design. Record further decisions here before implementation and obtain explicit approval under the relevant active roadmap. The baseline point-16 specification and acceptance are recorded in closed RM-2026-001.

Planned under RM-2026-004, superseding the earlier breadcrumbs-first order:

Record a reusable shell for both application contexts; this does not authorize implementing business modules, authentication, permissions, notifications or profile functionality. Phone drawer behavior, top-link overflow and sidebar persistence were implemented and accepted under RM-2026-004 point 3 on 2026-10-18. The circular initials control is specifically selected despite the rejection of strongly rounded general component corners in REF-001.

Owner correction (2026-10-19, RM-2026-004 point 12): normalize app-owned text/numeric/textarea/select chrome with appearance:none. Replace the detached outer input-focus ring with a theme-colored border/inset indicator inside the control boundary; keep keyboard focus visible and retain the system focus outline in forced-colors mode. Autofill paints the current field surface/text, including readonly/disabled variants, without disabling autocomplete. Native browser/password-manager suggestion popups remain browser-owned; styling the closed control does not replace those workflows.

Date/time/color picker requirement reaffirmed by the owner on 2026-10-19 (RM-2026-004 point 13): provide Runa-owned picker content and interaction, not merely a styled field opening a default browser picker. Reuse the palette, surfaces, shared buttons and floating layers; retain keyboard, touch, manual entry, clear actions and focus recovery. Forced-colors may use system focus/paint where necessary for accessibility. Browser-managed saved-credential suggestions and operating-system file selection remain separate platform interactions.

The accepted `/` welcome page consumes `MinimalLayout` directly and remains the regression baseline. Preserve its purpose, body, geometry, illustration, backdrop and preference interactions in RM-2026-004. The earlier intention to remove the root soon is superseded; the dual-version footer and the owner-requested compact illustration above content on narrow screens are approved root changes in this roadmap.

RM-2026-004 plans minimal, application and email layouts, branded HTTP errors and reusable primitives. This adds no authentication, permissions, admin behavior or business workflows. Point 1 establishes contracts, audit evidence and a temporary review/catalog skeleton. Actual layouts/components are delivered at subsequent points with their own visual gates; technical checks do not replace explicit owner acceptance. The owner accepted the catalog and motion; temporary application review infrastructure is retired in point 22, preserving isolated test fixtures.

Application-shell motion implemented in RM-2026-004 point 3 (visually accepted by the owner on 2026-10-18): sidebar width/workspace offset and mobile slide/backdrop use 180ms ease-out; inline navigation groups and chevrons use 160ms; top-navigation and initials dropdown cards fade with 4px vertical travel over 140ms. Reduced-motion removes these transitions. State, focus and dismissal update immediately; closing dropdown contents become inert during their exit.

- Further references and the specific features selected from each.
- Rendered validation of the approved palette, including gradient intensity.
- Layout and the placement/visibility of decorative background around application surfaces.
- Typography scale/weights (IBM Plex Mono selected), exact spacing/sizing and component appearance; approximately 4px ordinary radii and moderate density are accepted starting points.
- Future motion changes beyond the accepted point-21 component baseline.
- Responsive treatment of the decoration and shell.
- Authentication panel proportions, small-screen arrangement and exact CSS illustration.
- Detailed tablet adaptation and top-navigation overflow; phone navigation panel and sidebar preference persistence are accepted.
- Flash-message timeout, stacking and detailed presentation.
- Rendered validation of the implemented configurable dual-version footer (point 2 accepted on 2026-10-18) in RM-2026-004.
- Further component-specific theme validation as new views are implemented.
- Future auth/app view implementation scope; the current root preview is accepted.

The split-panel root originally used AuthLayout (2026-10-17 decision). On 2026-10-18, during RM-2026-004 point 2, the owner explicitly requested its removal after MinimalLayout extraction. MinimalLayout now owns the backdrop, identity header, preferences, skip/main structure and footer. Named content/default, illustration and actions slots supply page composition. Following the owner's visual feedback, MinimalLayout itself includes the preserved `AppBrandIllustration` on the right by default; narrow screens place a compact 240px illustration above content. Consumers, including `/`, supply only the left-side content. Screens can explicitly hide the illustration with `illustration: false` or override its slot. This supersedes the compatibility facade requirement and preserves the original content-left orientation. Authentication behavior and forms remain out of scope.

Application navigation texture (RM-2026-004 point 3): the top header and sidebar use a quiet static 44px square grid over the opaque surface. Lines reuse secondary-text color at 30% of the theme-specific backdrop-grid opacity (2.7% light, 1.35% dark), reduced after owner review. Forced-colors removes the decorative grid.

MinimalLayout is the common template for the welcome dashboard, HTTP errors and future Auth views. The owner reaffirmed this in RM-2026-004 point 5: errors must not introduce an independent centered card or another background/header/footer composition. Error content occupies the existing layout's content slot; the default supplied illustration, desktop split/mobile stack, full backdrop/constellations, header preferences and footer remain part of MinimalLayout. Build-time pre-rendering of that actual Vue component supplies HTML for native Laravel errors and static emergency 503, preserving operation without runtime JavaScript/PHP/database as applicable. Technical codes remain English and UI copy supports PL/EN/light/dark. The owner visually accepted the corrected shared-MinimalLayout errors on 2026-10-18. Real production-Nginx host verification remains mandatory in roadmap point 7.
