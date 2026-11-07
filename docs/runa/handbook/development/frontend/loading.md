# View readiness and background refresh

Runa renders layout frames immediately, with loading placeholders only inside
the elements or content regions waiting for data. Never replace the application
root, header/sidebar, backdrop, illustration or footer with a page-wide skeleton.
A session response, HTTP completion or known image URL alone does not establish
presentation readiness. Authentication and authorization remain the owning
controller's responsibility; this contract never grants access.

## Content-loading presentation

Content reads use pulsing Skeleton geometry, not a standalone circular spinner.
The UI-owned public `ContentLoading` component from `@/runa/ui` supplies accessible
localized placeholder regions for modules and App replacements. Its complete
props are `label?: string` (Loading), `loading?: boolean` (true),
`refreshing?: boolean` (false), `shape?: 'text'|'form'|'detail'|'image'|'navigation'`
(text), and `lines?: number` (3). Slots: default (retained content when loading is
false), placeholder (optional custom geometry). Implementation lives in
`src/runa/resources/js/components/AppContentLoading.vue`; it ships in frontend
assets. It never fetches, infers request ownership or grants access. Ready/default-slot
children are direct children of its root; put spacing/grid utilities on
ContentLoading itself, not a parent separated from those children. Refresh uses the named busy root region and inserts no extra status child into
spacing/grid layouts.

`Dialog` accepts `loading?: boolean` (false), `loadingLabel?: string` (localized
from locale when omitted) and `loadingShape?: ContentLoadingShape` (form), plus an
optional loading slot. A known parent-owned initial read uses these props; a child
that owns its read uses ContentLoading inside the modal. Do not hide a child needed
to start that same read. Busy remains mutation/closure behavior, distinct from
loading. `AsyncContent` and `ViewBoundary` default to ContentLoading. Consumers
supply state and shape, not another animation implementation.

Initial loading replaces only dependent content. Background reads retain DOM,
focus and drafts, using ContentLoading refreshing for local pulsing without
remounting content. Failures settle into explicit retry/error; they never leave
placeholders indefinitely. Reduced motion/print show static geometry/content.
Tables retain their existing circular read/refresh indicators. Busy buttons retain
embedded action progress. Submit Buttons without an explicit busy prop inherit
only their nearest Form's busy state; independent actions bind their own owner.
Generic scope/loading sentences are not progress indicators; the previously accepted language/theme cover retains
its dedicated transition/spinner. These are not content-loading defaults. Placeholder and retained-content pulse share `src/runa/resources/css/content-loading.css`
(1600ms, reduced-motion/print static). Source
lint restricts Spinner to those UI owners and rejects standalone view imports.

## Ownership and public API

The UI package owns these public consumption/composition APIs for platform views,
optional modules and application-owned replacements. Import them from `@/runa/ui`.
Implementation lives in `src/runa/resources/js/composables` and `components`;
types/exports live behind the UI facade. They ship in the frontend runtime without
another session owner, global request counter, state library or dependency.

`useViewReadiness(options: ViewReadinessOptions): ViewReadiness` accepts:

| Option     | Type                               | Responsibility                                                                                     |
| ---------- | ---------------------------------- | -------------------------------------------------------------------------------------------------- |
| identity   | MaybeRefOrGetter<string \| null>   | Current account/session/page identity; null withdraws content immediately.                         |
| required   | MaybeRefOrGetter<boolean>          | All explicitly required initial dependencies have settled to usable data or a documented fallback. |
| refreshing | optional MaybeRefOrGetter<boolean> | Background read activity.                                                                          |
| pending    | optional MaybeRefOrGetter<boolean> | A deliberate mutation is in progress.                                                              |
| failed     | optional MaybeRefOrGetter<boolean> | Initial read failed; render error/retry presentation.                                              |

The returned readonly computed refs are `ready: boolean` and
`phase: 'loading' | 'error' | 'ready' | 'refreshing' | 'mutating'`. Readiness latches
for that identity once required is true. Subsequent required=false does not hide
content. Identity change/null resets it synchronously. Supply null on access loss;
do not represent loss of authority merely as required=false. Controllers continue
to cancel and fence reads/writes independently. A phase is presentation state,
not permission to write or automatic disabling of an entire page.

`ViewBoundary` accepts `readiness: ViewReadiness`, optional `label` (default
`Loading`) and `errorMessage` (default `Unable to load content.`). Slots are
`default`, `loading`, `error`, all without arguments. Defaults compose Skeleton
and Alert; supply localized copy and an explicit retry in an error slot. The
boundary exposes data-view-phase and aria-busy for loading/refreshing. Its default
subtree stays mounted through background reads/mutations and is removed on
identity invalidation. It adds no minimum delay, page animation or focus change.

Both `MinimalLayoutProps` and `ApplicationLayoutProps` accept optional
`readiness?: ViewReadiness`. Their defaults render immediately for independent
composition and static/native error delivery. Supplied Auth entry/welcome provide
their owning controller's readiness; ApplicationSessionShell mounts its layout immediately. Its initial content and
avatar placeholders share one account/session readiness latch: validated session,
profile/preferences settlement, bounded Admin menu advertisement and decoded image
or bounded fallback. Protected
content still requires session admission. Layout readiness controls only the content/default
slot (and MinimalLayout actions), never the frame. Custom layout replacements forward readiness and keep controller
ownership outside presentation. Supplying readiness delegates initial preference
hydration to that composing controller; the standalone preference-session
initializer runs only when readiness is omitted (and no session owner/static or
custom preferences apply). `ApplicationSessionShell.contentLoading?: boolean` keeps the initial content
placeholder while its owner discovers the page; `navigationLoading?: boolean`
forwards discovery loading to the layout. Custom shell/layout replacements forward
these props. PageNavigation publishes its own loading state to the top-bar host
(see [page navigation](page-layouts.md#page-level-navigation-composition)).
Heavy page content uses a separate boundary;
never register every HTTP request as an application-wide prerequisite.

## Initial reveal and subsequent work

Review request counts as well as reveal timing. Public application presentation
has one in-flight read; overlapping own publications and actual identity changes
coalesce into one trailing current read. Successful same-session checks and passive
focus/visibility/history events do not trigger presentation reads. Results overtaken by an invalidation
cannot replace presentation. The last confirmed display remains while loading,
and disposal cancels the lane. Reuse the existing owner rather than adding another
identity, session or preference fetch to each component. Account and business
reconciliation follow the [action-driven read contract](forms.md#automatic-read-reconciliation)
and retain dirty drafts through applicable reads.

Declare dependencies per element/region: branding uses source defaults while
hydrating; preferences apply through their existing subscriber; profile/avatars
show their own bounded skeleton/fallback. A slow avatar never delays the layout. The initial application shell coordinates
its avatar/content placeholders so they finish together; independent heavy data
regions are not added to this shared prerequisite. A region that depends on an API uses its own skeleton
in the space it will occupy. Session admission still gates protected content,
not the public layout structure. Initial placeholders never mount protected forms.

Once revealed, reads keep the same DOM, focus, validation and dirty drafts.
Refresh failures provide retry/feedback beside retained content where the owning
controller allows it. Mutations disable only their owning operation/required
write lane. Never replay a write after refresh, conflict or reauthentication.
Real logout, account/session change, restrictions, expiry or access refusal clears
protected data immediately and rejects late results. Preserve the
[form reconciliation contract](forms.md) for disjoint updates and local conflicts.

`AsyncContentState` includes `refreshing`: it renders the same success subtree
as success and sets aria-busy. Switching success/refreshing does not rerun content
entrance motion. Initial loading/error/empty retain their existing distinct slots;
controllers decide whether a failure requires withdrawing protected content.

## Admin initial content

Admin retains ApplicationLayout throughout initial session/bootstrap discovery;
MinimalLayout belongs to explicit entry proof and refusal. The supplied Admin
content region mounts its authorized controllers behind a hidden, inert subtree
while page declarations, record/table reads, capability/diagnostic reads and the
initial selected application settings editor read settle. Lazy component boundaries wait for
resolution or their existing bounded failure fallback. It reuses useViewReadiness and waits
for projected children to register before revealing the region and navigation.
Failed reads settle to existing explicit error/retry presentation; they do not
keep a skeleton indefinitely. No extra reads or minimum delay are introduced.
The region is scoped to the mounted account/session/page and removed on actual
access loss. Later reads retain its DOM and drafts. Newly opened application settings editors
also use local boundaries; interactive option searches and optional widgets keep
their own loading ownership. A replacement that starts independent asynchronous
work must compose its own public readiness boundary.

## Image readiness

`useImageReadiness(source: MaybeRefOrGetter<string>,
identity: MaybeRefOrGetter<string | null> = 'image', timeoutMs = 2500):
ImageReadiness` preloads the image, waits for browser decode and returns readonly
computed `src: string`, `state: 'loading' | 'ready' | 'fallback'` and
`settled: boolean`. The timeout covers download and decode together. A failed or
slow image settles to an empty src, allowing initials/source branding. Late
completions cannot restore timed-out, replaced or foreign-identity images. Scope
disposal removes handlers/timers; there is no global cache of private images.

For the same identity, keep the previous decoded image while a replacement loads.
An empty source/removal or identity change clears it synchronously. Do not key an
avatar by a session observation number or remount its owner when opening a menu.
Pass one ImageReadiness owner to multiple consumers with `Avatar.image?:
ImageReadiness`; the standalone Avatar owns its lifecycle when image is omitted.
`ApplicationLayout.userAvatarImage?: ImageReadiness` forwards the prepared image
to its header. Forward this prop in custom wrappers. URLs and authorization remain
the caller's responsibility; readiness neither fetches profile metadata nor
substitutes for guarded private image delivery.

## Supplied view audit

| Consumer                                  | Initial dependencies and later behavior                                                                                                                                                                                                                                                                  |
| ----------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| createApplication                         | Immediate root mount; bounded public identity/presentation reads and decoded logo update branding in place; failed background branding reads retain the last confirmed presentation.                                                                                                                     |
| Auth entry                                | Existing entry/session controller and preference hydration before form reveal; entry pages without a session read use the existing one-time preference initializer. Heading focus also works after delayed presentation mount.                                                                           |
| Auth welcome                              | Immediate layout and static welcome content; navigation alone waits for options/session; no second layout-owned session read.                                                                                                                                                                            |
| ApplicationSessionShell / Admin admission | Immediate layout; initial content/avatar placeholders finish together after session/profile/preferences/image readiness or bounded fallback. Later reads preserve content; header/dropdown share the image owner. Background session/profile reads retain the shell and content.                         |
| Auth account                              | Existing independent account/page loading and nondestructive read reconciliation remain; protected account content starts after shell admission/readiness.                                                                                                                                               |
| Admin bootstrap/page                      | Immediate Application frame; initial Admin content/navigation reveal waits for declared initial dependencies. Same-session observation revalidates in place. Request stamps still include every authority observation. Unchanged descriptors preserve object identity; denial removes protected content. |
| Admin records/forms/operations            | Same-session observation fences requests/reviews without erasing drafts; actual context loss clears them. Unchanged resource projection retains confirmed values while reading. Removed fields follow the new projection.                                                                                |
| Admin tables                              | Revalidation resets stale cursor/selection intent but keeps current same-session filters; a different account/page clears them. Heavy reads do not block the shell.                                                                                                                                      |
| Static/native HTTP errors                 | Immediate source/static preferences; no readiness dependency on session, image service or network.                                                                                                                                                                                                       |

Profile and preference snapshot reads have a five-second presentation timeout;
image preparation has the separate 2.5-second bound. The session itself is required
and uses its existing authority/error flow. Optional entry navigation policy does
not block the shell. Explicit preference changes retain the established overlay;
incoming hydration never saves defaults back to the account.

Admin same-filter diagnostic refresh retains current cards and uses local
ContentLoading refreshing pulse. Changing the module-state filter replaces the card list with shared
animated block Skeletons until the latest selection settles, retaining the filter
and section frame. Older optional diagnostic reads are cancelled and late results
cannot replace the latest selection. Module dialog editors retain the same initial-readiness
and dirty-draft boundaries.

New views and App replacements must declare these boundaries and use the public
primitives instead of adding page-wide loading flags/timers. Review cold cache,
slow/failed images, tab return, a dirty editor, access loss/account switch during
an in-flight read and disposal. Automated regressions supplement rendered owner
acceptance; they do not replace it.

Admin operation feedback is scoped to the initiating operation: diagnosis, work
reads and history reads have independent pending indicators. Diagnosis opens a nested shared Dialog with a local Skeleton, then a safe result
or error/retry, independently of lifecycle/work feedback. Its trigger stays
right-aligned in the module action row. Context/authority loss clears the dialog
and stale results. Optional
lifecycle buttons open a confirmation Dialog with a local Skeleton while its
native preview loads; blocked/loading previews cannot be confirmed. Failed writes
require another explicit review, and context/authority changes discard late previews. Keep the active tab
selected while its containing Dialog fieldset disables mutation controls; marking
all tab items unavailable would remove every panel. History/work Table frames
remain visible for confirmed empty reads and keep existing rows through refresh.
Module/context changes invalidate pending result ownership before clearing tables.

Breadcrumbs share ApplicationLayout's initial navigation readiness: while
navigationLoading or the page-navigation publisher's loading state is true, keep
the region mounted/aria-busy and render the existing Skeleton instead of partial
labels/links. Reveal the completed path with the navigation. This is a local
placeholder, not a page/frame cover or an independent HTTP prerequisite. Supplied
Admin readiness remains latched through ordinary background reads.

The shared session shell includes initial Admin-entry advertisement settlement
(success, refusal or five-second timeout/failure) in its account/session readiness
latch. The account-menu trigger remains disabled until that initial presentation
and page discovery are ready. Existing same-identity background advertisement reads
retain the known link and mounted content; denial/failure removes the link and
identity/access loss clears it immediately. Advertisement remains UX, not authority.

ApplicationLayout masks language/theme controls with local Skeletons while
navigationLoading is true, using the shell's same initial preference/readiness
signal. It renders controls with hydrated labels/icons when ready. Do not expose
a temporary system-theme icon while the account snapshot is pending or start a
second preference/session read. Intentional preference changes keep their existing
cover/focus protocol.
