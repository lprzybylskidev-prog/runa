# View readiness and background refresh

Runa renders layout frames immediately, with loading placeholders only inside
the elements or content regions waiting for data. Never replace the application
root, header/sidebar, backdrop, illustration or footer with a page-wide skeleton.
A session response, HTTP completion or known image URL alone does not establish
presentation readiness. Authentication and authorization remain the owning
controller's responsibility; this contract never grants access.

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
profile/preferences settlement and decoded image or bounded fallback. Protected
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
initial selected settings/editor read settle. Lazy component boundaries wait for
resolution or their existing bounded failure fallback. It reuses useViewReadiness and waits
for projected children to register before revealing the region and navigation.
Failed reads settle to existing explicit error/retry presentation; they do not
keep a skeleton indefinitely. No extra reads or minimum delay are introduced.
The region is scoped to the mounted account/session/page and removed on actual
access loss. Later reads retain its DOM and drafts. Newly opened settings editors
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

New views and App replacements must declare these boundaries and use the public
primitives instead of adding page-wide loading flags/timers. Review cold cache,
slow/failed images, tab return, a dirty editor, access loss/account switch during
an in-flight read and disposal. Automated regressions supplement rendered owner
acceptance; they do not replace it.
