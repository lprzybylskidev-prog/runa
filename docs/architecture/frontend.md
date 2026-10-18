# Frontend Architecture

## Stack

- Vue 3
- TypeScript with strict mode
- Tailwind CSS
- Vite
- ESLint
- Prettier
- `vue-tsc`

No UI framework is installed by default.

No global state library is installed by default.

## Language

User-facing UI:

- Polish is the default language.
- English is the secondary language.

The mandatory administration panel is English-only and is not translated to Polish.

Backend, tooling, logs, source code, and documentation remain English-only.

Polish Laravel validation/localization resources are required from the start for user-facing messages. Technical backend output, machine codes and admin messages remain English. The approved implementation uses pinned vendored translation data with Laravel’s native loader; no `laravel-lang/lang` or `laravel-lang/common` dependency is permitted. Provenance, MIT attribution and deliberate update instructions are in `docs/development/localization.md`.

## Responsive and themes

Design every user-facing feature for:

- desktop,
- tablet,
- mobile.

Support light and dark themes from the beginning.

Do not treat mobile as a stripped-down product. Adapt presentation without hiding important functionality.

Breakpoints follow content needs rather than a catalog of device models.

## HTTP client

The SPA uses one thin HTTP client/wrapper.

It owns transport concerns such as:

- base URL,
- CSRF/cookie behavior,
- standard headers,
- normalized error envelope handling,
- network/session-wide concerns.

It must not globally toast every API error. Feature-specific presentation remains near the use case.

Do not scatter raw `fetch` calls.

## Validation

Frontend validation is UX-authoritative for conditions the client already knows. It should prevent known invalid submissions immediately.

Backend validation/integrity is always authoritative for security and final correctness.

Where useful, expose simple shared validation metadata:

- required,
- min/max,
- length,
- simple format,
- enum choices.

Do not attempt to serialize the entire Laravel validation system into the frontend. Complex business and authorization state remains backend-owned.

Custom form components disable native browser validation/messages when those conflict with controlled application UX.

Neutralize browser autofill colors where necessary to preserve theme consistency.

Where simple validation metadata is shared from backend to frontend, include a consistency check so obvious required/min/max/length/format/enum drift is detected rather than silently diverging.

## Shared UX states

Provide consistent patterns for:

- loading,
- empty state,
- network error,
- retry,
- forbidden,
- not found.

Forms have local submit/busy state and consistent common error behavior.

Busy state is not the same as disabled state.

Block accidental duplicate submissions while preserving the user's form data after server errors.

## Accessibility baseline

WCAG 2.2 AA is the project baseline, not a formal certification claim.

- Prefer semantic HTML before ARIA.
- All functionality must be keyboard accessible.
- No keyboard traps.
- Focus must be visible.
- Manage focus for modal/temporary layers and return it appropriately.
- Forms have real labels; placeholders are not labels.
- Instructions and errors are programmatically related to controls.
- Important dynamic messages are exposed to assistive technology.
- Color is not the only carrier of meaning.
- Design-system contrast targets AA.
- UI must remain functional at 200% zoom/text scaling.
- Minimum WCAG 2.2 target size is approximately 24x24 CSS px; prefer about 44x44 for frequent touch actions when practical.
- Respect `prefers-reduced-motion` without breaking state communication.
- No critical action/information may exist only on hover.
- Screen-reader checks are performed periodically for critical/complex flows rather than mechanically for every button.

Accessibility is part of normal component review.

## Content robustness

Test:

- long text,
- missing/empty content,
- Polish/English translation expansion.

Do not design only against ideal short English labels.

## Component/UI behavior

Complex components have explicit state ownership.

Rendering/computed logic must not contain non-obvious side effects.

Relevant controls define complete states:

- normal,
- hover,
- focus,
- active,
- selected,
- disabled,
- busy.

Use clear action hierarchy:

- primary,
- secondary,
- destructive.

Action text describes the outcome (`Save changes`, not generic `OK`).

Use modals only when the interaction genuinely benefits from modality.

Protect destructive actions proportionally to consequence, using confirmation, undo, or equivalent behavior as appropriate.

## Design system

Use pragmatic design tokens for repeated:

- colors,
- spacing,
- radii,
- typography.

Add a token only when it reduces real drift/repetition.

Design pages around user tasks and information hierarchy, not around maximizing card/component count.

Do not default every layout to a dashboard grid of cards.

Motion communicates state/change; it is not decoration by default.

Do not rebuild the whole design system during an unrelated roadmap.

## Performance

Understand actual render/request/table/image/bundle costs before optimizing.

Lazy-load/code-split genuinely heavy isolated areas, not every component.

Do not render huge collections blindly. Add virtualization only when data volume and measured behavior justify it.

Media should use known dimensions/aspect ratios and lazy loading where sensible to avoid layout shift and wasted transfer.

## Acceptance

Code appearance is not frontend acceptance.

For frontend changes, inspect the rendered UI at representative sizes:

- desktop around 1440x900,
- tablet around 1024x768,
- mobile around 390x844.

Check:

- relevant light/dark states,
- PL/EN where user-facing,
- console errors/warnings,
- failed network requests,
- accidental 404/500 responses,
- forms and validation.

Rendered behavior is the acceptance truth.

## Component and page discovery

Before creating a page or form, inspect two analogous existing pages/forms when available and search existing shared controls/layouts. If fewer exist, inspect what is available and establish the first pattern deliberately. Never invent examples that do not exist.

Reuse suitable project controls instead of native replacements, including date inputs, selects, labels, errors and actions. The planned application workspace hierarchy under RM-2026-004 is heading/description/actions, breadcrumbs, optional statistics, optional toolbar, then mandatory content, with an optional supplementary panel. The sidebar stays viewport-fixed and scrolls independently; top navigation and toolbar scroll normally. These shell features are not implemented yet; follow the established analogous page where one exists.

The maintained catalogue lives in `.agents/skills/frontend/references/components.md`; page, form and table patterns live alongside it. Update those references in the same task whenever a reusable component or pattern is established, replaced or removed. Record real paths, intended use, relevant contracts and canonical examples. Persist reusable user corrections without waiting for a separate documentation request.

Disable native form validation for controlled application forms and show errors through the common application UX. Preserve labels, input semantics, autofill/password-manager support and accessible error associations. Block known invalid submits and duplicate submits; preserve recoverable input on 409/422/server failures. A conflict offers a clear refresh/recovery path instead of silent overwriting.

## Public UI boundary and temporary review

Import individual existing primitives from `@/runa/ui` (Vite, Vitest and TypeScript share the alias). The facade exports Button, Tooltip, Icon, Form, Select, LoadingOverlay and Logo without importing a layout, router, registry or global installation. Include the application's `resources/css/app.css` for theme tokens/fonts/styles. The underlying App-prefixed components remain the single implementations.

Public identity, navigation, preference-storage, selection/export/provider and print types live behind the same facade. They define integration contracts only: no identity provider, route resolver, generalized storage adapter or export engines are delivered yet. Existing `runa.locale`/`runa.theme` behavior remains unchanged. Application versions are independent of platform versions; URL-derived route context is separate from persisted navigation appearance.

The temporary `resources/review/` entry is built separately with `./bin/pnpm exec vite build --config vite.review.config.mjs` into ignored `public/ui-review/`. In `local`/`testing`, named route `review.ui` serves `/__review/ui`; a missing build returns 404. Other environments do not register it, and its controller rejects stale review routes outside local/testing. No environment flag enables production review. The main production entry/import graph and Docker build output copies exclude this separate entry/output. `http://localhost:8080/__review/ui` uses the normal development Nginx endpoint. Category controls currently demonstrate existing controls only; upcoming layouts/errors remain pending. `/__review/not-found` is a real 404, presently unbranded. There is no catch-all success route. Remove temporary infrastructure at roadmap point 19 after explicit final visual acceptance.
