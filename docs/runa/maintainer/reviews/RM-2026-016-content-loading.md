# Content-loading presentation review

Date: 2026-11-01. Owner: RM-2026-016, point 11a40.
Scope: supplied frontend under src/runa and src/app, including every loading,
aria-busy, pending-read, Spinner, Skeleton, AsyncContent and LoadingOverlay
consumer, all 17 Dialog/ConfirmDialog instances and their shared primitives.
Source inspection distinguishes content reads from mutation progress. This is
current-roadmap evidence, not a claim that full automated verification or owner
manual acceptance has passed.

## Decision and prevention

Content reads use pulsing placeholders; revealed content stays mounted through
background reads, using local pulsing refresh feedback. UI owns ContentLoading,
Dialog's loading props and AsyncContent/ViewBoundary defaults. Request ownership,
cancellation, identity/access fences, errors and dirty drafts stay with controllers.
There is no network-global loading inference and no minimum placeholder delay.

Only shared Table, Button and the previously owner-accepted language/theme cover
own circular indicators. The cover is a deliberate preference transition, not
content discovery. AppSpinner is their low-level implementation. The source lint
rule rejects standalone Spinner imports (including aliases), namespace members
and template references outside those owners. It runs through normal lint/hooks
in both owner and customer source contexts. Existing Skeleton geometry and retained-content refresh share the single
content-loading.css pulse; geometry remains the common placeholder primitive; local replacement slots are still supported.

## All modal instances

Paths below are relative to src/runa/modules.

| Source / instances                            | Content and decision                                                                                                                                                                                    |
| --------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| admin/frontend/AdminForm.vue (1)              | Known dirty/destructive confirmation; no initial content read. Existing mutation ownership retained.                                                                                                    |
| admin/frontend/AdminOperations.vue (4)        | Module editor uses existing readiness boundary; diagnosis and lifecycle review now use Dialog loading defaults; known confirmation needs no placeholder. Footer native preview/authority guards remain. |
| admin/frontend/AdminTableSection.vue (1)      | Selected record content uses shared AdminSection placeholders; Table retains its circular read owner.                                                                                                   |
| admin/frontend/AdminResourceTable.vue (1)     | Known bulk-action confirmation, no content discovery.                                                                                                                                                   |
| admin/frontend/AdminRecordBrowser.vue (1)     | Audit and Permissions descendants own their local reads; migrated shared record/Audit placeholders.                                                                                                     |
| auth/frontend/AuthHistoryDetail.vue (1)       | Replaced standalone circle with Dialog loading/detail placeholders; failures/retry/immutable details retain their contract.                                                                             |
| auth/frontend/AuthSecondaryEmails.vue (1)     | Known address deletion/conflict confirmation, no initial request-driven content.                                                                                                                        |
| auth/frontend/AuthAccountPreferences.vue (1)  | Known reset consequences, busy is mutation progress, not content discovery.                                                                                                                             |
| auth/frontend/AuthAccountEditor.vue (2)       | Avatar deletion uses confirmed profile; image crop modal delegates decoding to ContentLoading image geometry in AuthAvatarCropper.                                                                      |
| auth/frontend/AuthRecoveryCodesDialog.vue (1) | Opens only after returned codes exist. Acknowledgement progress is mutation feedback; codes remain mounted and nonclosable semantics remain.                                                            |
| auth/frontend/AuthAccountSecurity.vue (2)     | Factor forms are current controlled state; trusted-device content is a Table with its own allowed loading. Trust-removal confirmation already knows its subject.                                        |
| auth/frontend/components/admin_users.vue (1)  | Auth sections reuse corrected shared AdminSection read geometry; bounded collections do not show premature empty messages.                                                                              |

Total: 17 instances. The Dialog/ConfirmDialog/AlertDialog primitives themselves
are reviewed separately below; they are not extra application modal instances.

## Every loading owner and primitive

| Source / group                                                                                     | Finding and disposition                                                                                                                                                                                        |
| -------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| UI AppDialog / AppConfirmDialog / AppAlertDialog                                                   | Added loading/label/shape and optional loading slot. Initial content is replaced inside the retained frame. Loading does not imply mutation/closure lock; confirm/acknowledge refuse unfinished content.       |
| UI AppAsyncContent                                                                                 | Changed circular default to ContentLoading. Refresh preserves its success subtree and adds local pulse. Custom loading/error/empty slots remain supported.                                                     |
| UI AppViewBoundary                                                                                 | Uses the same ContentLoading default and retained-content refresh pulse; identity loss still withdraws protected slots.                                                                                        |
| UI AppContentLoading                                                                               | Shared accessible text/form/detail/image/navigation geometry and refresh pulse, no requests or controller ownership.                                                                                           |
| UI AppSkeleton / AppAvatar                                                                         | Existing pulsing geometry and bounded image readiness are correct; retained.                                                                                                                                   |
| UI AppSelect                                                                                       | Replaced plain loading-only text with option-list ContentLoading; retained options pulse during more-results reads. Keyboard selection, generation cancellation, errors and retries remain owned by Select.    |
| UI AppPageNavigation                                                                               | Existing local pulsing navigation geometry is correct; retained.                                                                                                                                               |
| UI AppTable                                                                                        | Explicit circular loading slot and refresh-icon progress remain with Table, including tables inside modals. Rows/filters/export behavior unchanged.                                                            |
| UI AppButton / AppSpinner                                                                          | Embedded action progress retained; standalone content imports are prohibited by lint.                                                                                                                          |
| UI AppLoadingOverlay                                                                               | Accepted preference-change cover/spinner retained with its original hold/focus/colors; not reused for content reads.                                                                                           |
| UI AppForm / AppWizard / AppStatistics                                                             | Busy/disabled/aria-busy represent controlled actions or presentation, not missing content; no new fetch placeholder invented.                                                                                  |
| layouts ApplicationLayout / MinimalLayout                                                          | Frames stay immediate; existing local navigation/breadcrumb/preferences/avatar Skeletons and content readiness remain. Preference cover remains dedicated. Static/native error output has no API prerequisite. |
| auth ApplicationSessionShell / AuthApplication                                                     | Existing coordinated initial content/avatar readiness is correct; shell/frame, defaults and replacement slots retained.                                                                                        |
| auth AuthAccountDefault / AuthAccountLoading                                                       | Existing form-shaped placeholders are correct; preserved with their public replacement seam. Account/session/history tables retain Table feedback.                                                             |
| auth AuthAccountPreferences                                                                        | Existing notification Skeletons are correct; reset confirmation is a known mutation, not a missing read.                                                                                                       |
| auth AuthAccountSecurity                                                                           | Trusted-device loading belongs to Table. Factor steps and mutations retain their existing controlled state.                                                                                                    |
| auth AuthHistoryDetail                                                                             | Migrated to shared Dialog loading instead of circle.                                                                                                                                                           |
| auth AuthAvatarCropper                                                                             | Migrated to image-shaped ContentLoading while decoding, keeping bounded decoding/error/cleanup.                                                                                                                |
| auth AuthEntryFlow                                                                                 | Initial form uses ContentLoading form geometry; input/submit/proof ownership retained.                                                                                                                         |
| auth AuthWelcomeDefault                                                                            | Session-dependent navigation uses ContentLoading navigation geometry; welcome content remains immediate.                                                                                                       |
| admin AdminApplication / AdminPageContent / AdminReadiness / AdminComponentBoundary / AdminLoading | Existing scoped readiness, lazy fallback and explicit error settlement retained; common AdminLoading delegates to ContentLoading. No global HTTP counter or shell mask added.                                  |
| admin AdminApplicationSettings                                                                     | Existing ViewBoundary now inherits shared initial/refresh defaults; field/draft/proof and image-write state are retained.                                                                                      |
| admin AdminOperations                                                                              | Same-filter diagnostics pulse current content. Filter changes retain existing block Skeletons. Diagnosis/lifecycle modals use Dialog loading. Work remains a Table.                                            |
| admin AdminOperationHistory / AdminResourceTable                                                   | Existing Table loading and refresh retained.                                                                                                                                                                   |
| admin AdminSection                                                                                 | Replaced the recently introduced circle with form-shaped ContentLoading. Failure/retry remains explicit.                                                                                                       |
| audit admin_detail                                                                                 | Replaced the recently introduced circle with detail-shaped placeholders. Continuation pulses retained payload, never erases/reinitializes it.                                                                  |
| permissions admin_effective                                                                        | Read state belongs to Table; no second loading owner added. Definitions use shared AdminSection.                                                                                                               |
| src/app                                                                                            | No independent supplied loading animation/modal implementation was found. App-owned future views inherit defaults and the same lint rule.                                                                      |

## Evidence and limits

The owning roadmap records focused native tests, source lint policy checks,
served-build desktop/mobile and reduced-motion observations, asset preparation
and live public endpoint status. Report paths are under
.runtime/frontend/RM-2026-016-content-loading-* and
.runtime/artifacts/playwright/runa/RM-2026-016-content-loading/.
Broad suites and owner manual acceptance remain roadmap points 12 and 11b.
