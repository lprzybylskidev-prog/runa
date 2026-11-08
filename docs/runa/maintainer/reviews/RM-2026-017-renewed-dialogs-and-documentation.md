# RM-2026-017 — Renewed dialog and documentation audit

## Scope and status

Inspection date: 2026-11-06. Source candidate: `62b1cec7` on
`roadmap/RM-2026-017-platform-quality-and-workflows`, initially clean.
This is additional point-1 evidence under the
[withdrawn Gate B](../roadmaps/active/RM-2026-017-platform-quality-and-workflows.md#current-execution-state--owner-restart-decision-2026-11-06).
It completes neither the baseline audit nor acceptance of points 2–6.
No implementation, dependency, configuration, schema or version changed.

Knowledge status was current: 213 documents, 11,952 chunks, no changed/deleted
sources, missing/orphan chunks, interrupted write or branch/parser/metadata drift.
Bounded audit-intent retrieval for dialog focus, access editing and account
creation led to Permissions administration, component semantics and the frontend
accessibility baseline; the applicable original sections were opened.
Frontend, Vue and Tailwind skill entrypoints and relevant frontend references
were read. The declared Tailwind dependency is 4.3.3; no style compilation or
independent installed-package attestation was needed for these observations.

## Browser method and native side effects

Purpose was announced before execution: inspect existing account-creation,
selected-user and definition dialogs, nested access copying, focus and narrow
presentation without submitting business forms. Used installed Chromium directly
against existing `http://nginx:8080`, normal login and full-page Admin password
confirmation with the existing local account. Configured credentials went directly
to process/browser memory; no secret, cookie, response body or storage snapshot
was printed or saved. This one-off diagnostic has no equivalent supported suite
selection without broader mocked-fixture preparation. No harness/service was
started or rebuilt. Browser contexts/processes closed in finally blocks.

Ordinary authentication, password confirmation and activity produce native
session/security/Audit effects. Request inventory also observed POSTs to the
preferences endpoint. That endpoint uses POST for snapshot reads as well as
writes; recording only path/method cannot establish an actual preference write.
Do not describe this browser activity as literally GET-only or establish a
zero-write claim from the absence of business-form submission. No account,
role, preset, access assignment or settings Save/Apply was submitted. Native
session/history/data and incidental UI state were preserved, without rollback
or cleanup through application mutations.

Evidence root: `.runtime/artifacts/playwright/RM-2026-017-restart/`.
`dialog-audit.mjs`, `dialog-audit-first.json` and `dialog-audit.json` retain two
bounded observations. The second corrects the diagnostic's copy-opener name
from an unmatched guessed label to the actual `Kopiuj od użytkownika`; the first
run did not inspect that nested dialog. `read-failure-audit.mjs/json` retain a
separate targeted attempt to attribute a visible error. JSON includes metadata
and visible control inventories, not private backend payloads. Heading arrays
include mounted hidden tabs; they are not lists of simultaneously visible content.

## Capability coverage

| Capability and source owners                                                                         | Method and actual result                                                                                                                                                                                                                                                            | Remaining work / disposition                                                                                                                                                                                                    |
| ---------------------------------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Account creation: AuthAdminCreation, admin_users, Dialog, Form, controlled identity/lifecycle fields | Opened the real creation dialog at 1440×1000 and 390×844. Inspected both captures; responsive fields remain within the modal and document width. Blank required fields leave Create disabled. Escape closes and restores the creation opener.                                       | Did not fill every variant, scroll through every lower field, submit, verify server refusal, invite/mail delivery or test an alternate capability configuration. No correction selected from these samples.                     |
| User details: AuthAdminUserDetails, Tabs, supplied sections                                          | Opened the actual selected administrator; inspected User and Security tabs. Both focus stays inside the modal and no document overflow was observed. Security renders session and trusted-device table controls, with real populated sessions.                                      | Headings alone do not establish every field's correctness. Dirty-draft switching, independent writes, action reviews, protected accounts, grants and identity withdrawal remain open.                                           |
| Nested copy access: PermissionsAccessImport, PermissionsAssignments, Dialog/useModalLayer            | Opened Copy from user in selected-user details at desktop and 390px. Visually inspected narrow capture. Parent becomes inert; Shift+Tab from the child's first button wraps inside, Tab returns to that first button. Escape removes only the child and leaves focus in the parent. | Did not choose a source, search/page the popup or Apply. Preset import, aborted/late lookups and changed identity/source need separate evidence. No change to modal ownership selected from this observation.                   |
| Security tables in a narrow dark presentation                                                        | Inspected `user-security-mobile-dark.png`; filter/control regions reflow within the modal, without document overflow. Dark appearance is visible in the capture.                                                                                                                    | This viewport capture does not establish row reachability at all scroll positions, sticky-header behavior, export completeness, contrast or screen-reader support. Reduced-motion media was requested, not measured.            |
| Permissions role and preset creation: admin_definitions/admin_definition, PermissionsAssignmentList  | Opened each real creation dialog without submission; inspected the role capture and control inventories. Localized name/description fields and bounded membership page controls render.                                                                                             | Preset screenshot was collected but not independently visually reviewed in this pass. Did not edit membership, page/search, save/delete/force-delete or exercise readonly definitions. No blanket definition-editor acceptance. |
| Shared feedback: FormNotifications/useToasts, Auth details and form guidance                         | Compared actual watcher/dismissal source with feedback.test.ts and the public failure/attempt contract. A-08's existing mounted-translation regression remains relevant.                                                                                                            | Dismiss → locale-only change with unchanged invalid state reaches show() again in source. That is a source-derived RH-02 outcome, not a live reproduction or accepted new interaction decision.                                 |

No pageerror was recorded in either dialog diagnostic. This does not mean no
network/application failure: one sample visibly contained a failure notification.

## Unexplained error observation

`user-tab-1.png` and `access-copy-mobile.png` show the localized generic failure
notification. These captures establish visible feedback, not its cause or severity.
The dialog diagnostic did not collect response statuses or failed transport
events, so attribution to preferences, a section, revocation or validation would
be speculation. Keep this as **RH-03, unclassified pending attribution**.

The separate read-failure observation reopened user details and Security while
recording method/path/status and transport-failure metadata. All completed
authenticated selected-user/section responses were 200; expected guest session
401 and pre-confirmation Admin 403 also occurred. Two identity/presentation GETs
were aborted during document navigation. Neither sampled tab had a toast.
This does not reproduce the earlier error or prove that a navigation abort caused
it. Do not weaken security or change feedback from this evidence. A later bounded
observation should retain request identity/query-kind and timing around the exact
creation → close → details → nested-dialog sequence if attribution is needed.

## Confirmed documentation findings

### RA-03 — Obsolete scratch placement, Low

Owner: public testing guidance, with project-principles/root instruction routing.
`docs/runa/handbook/testing/strategy.md`, Render diagnostics, directs temporary
exploration to `.playwright/scratch` or `tools/scratch`. The canonical generated
tooling contract in `architecture/project-principles.md` requires one workspace
`.runtime/<area>/` tree and browser evidence under `.runtime/artifacts/playwright/`.
This is a current-guidance contradiction, missed by the earlier per-document
disposition. It can cause misplaced diagnostics and ambiguous cleanup ownership;
it is not evidence of data loss. Reconcile the testing paragraph with the canonical
home after renewed Gate B. Small effort, low risk, proposed point 4. Preserve
existing evidence; do not mechanically delete either old scratch location.

### RA-04 — Comment stripping hides real links, Medium

Owner: `tools/application/documentation_markdown.py::prose`, consumed by
`docs_checks.links/structure`; introduced in point 3 candidate `86b01c65`.
The global HTML-comment regex executes before fenced-code and code-span
recognition. A literal comment opener inside either kind of code can consume
intervening real prose through a later literal closer. The offline checker then
accepts a document containing a real broken link without inspecting that link.

Two tiny diagnostics supplied text to the current parser and then to actual
`structure`/`links` using disposable `/tmp` files. Both accepted an absent
`missing.md`. Exact source and output remain in `parser-observations.json`:

1. One fenced HTML example contains only `<!--`; a later separate fenced example
   contains only `-->`. A real section and `[Broken](missing.md#absent)` between
   them disappear from prose, heading and link outputs.
2. Ordinary prose explains the opener as inline code, then contains a real broken
   link, then explains the closer as inline code. The real link disappears.

This contradicts the delivered authoring contract that code examples do not
create links/headings while real local links are checked. Existing fixtures
exercise comments, long fences and inline links independently, without comments
inside code spanning real prose. Keep those protections and add interaction
fixtures; do not replace source validation with a renderer/framework by default.
Reconcile code/comment ordering within the existing bounded parser after Gate B.
Medium effort, parser regression risk; proposed point 3 repair/reacceptance before
relying on point 4's automated link dispositions. No full validator or suite ran.

A third exploratory sample, a heading containing `module <owner.code>` in inline
code, returns `tool-module-`: HTML stripping also removes literal angle-bracket
code text. Actual command headings contain such syntax. Rendered slug correlation
and current inbound-link impact remain open; do not count this as a second
confirmed rendering defect or silently expand supported Markdown semantics.

## Test-value and prior-work assessment

This is a bounded source review, with no test execution or proposed deletion.

| Existing evidence                                       | Unique protection / decision                                                                                                                                    | Missing evidence                                                                                                                         |
| ------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| dialogs.test.ts nested-modal and floating-child cases   | Keep; modal stack isolation, focus restoration and Escape ownership have independent value. Real nested-copy observation adds live consumer evidence.           | Mounted/native assertions do not cover every live popup or permission withdrawal.                                                        |
| auth-creation.test.ts                                   | Keep capability withdrawal, hidden-secret cleanup, Enter/known validation and failed-read retry cases. These differ from opening/closing an empty real form.    | No current suite PASS; real successful creation intentionally unexecuted.                                                                |
| auth-administration.test.ts import cases                | Keep guarded lookup, nonclearable mode, dirty-draft omission and cancellation parameter cleanup. These exercise owning state independently from generic Dialog. | Real source picker/preset/review races remain unobserved.                                                                                |
| feedback.test.ts                                        | Keep existing A-08 in-place identity/focus and deliberate-repeat tests.                                                                                         | Dismissal followed by locale-only change is absent from the inspected cases.                                                             |
| DocumentationMarkdown fixtures in test_documentation.py | Keep links/anchors, long fences, explicit anchors, projection, encoded paths and title cases.                                                                   | Add code/comment interaction coverage if RA-04 is accepted. Source review proves neither fixture execution nor whole parser correctness. |

Point 3's existing changes are useful candidates, with a demonstrated missing
interaction case. Point 4's broad no-change dispositions cannot certify semantic
currency: RA-03 is a concrete counterexample. Point 6's A-08 fix is preserved;
RH-02 needs a dismissal decision/evidence before additional changes. This pass
does not independently accept point 2 or reassess the whole point-5 backend scope.

## Remaining boundary and resources

No broad suites, full browser matrix, graph, scanner, image build or infrastructure
acceptance ran. Remaining renewed inventory includes unvisited operational views,
settings/snapshots, complex tables/temporal/value interactions, entry/security
branches, alternate permissions/configuration and other backend/database/tooling/
instruction capabilities recorded by the active roadmap. This report is evidence
for its named capabilities only; point 1 remains open.

Temporary `/tmp` parser fixtures were removed by their scoped context manager.
Browser processes/contexts closed; scripts, screenshots and JSON are retained
as roadmap evidence. No containers, networks, images or volumes were created or
deleted. All application/base test data, session history, caches, backups,
inherited recovery journals and verification reports were preserved.
Only owner audit records change. Root instructions, skills and the customer bundle
need no update until an accepted correction changes their reusable rules/products.
