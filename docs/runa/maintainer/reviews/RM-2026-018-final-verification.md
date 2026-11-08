# RM-2026-018 pre-verification source review

Date: 2026-11-08. Target: owner-approved fix release 0.8.1 after 0.8.0.
Implementation points 1–3 are committed; this review accompanies point 4.
Final automated acceptance, release metadata, merge, tag and publication remain
pending at this snapshot. Final results belong to the roadmap and retained native
coordinator evidence; this dated source review is not updated to imply later gates
already ran. Development diagnostics below are not final acceptance evidence.

## Changed contracts and source review

Reviewed the root README and documentation ownership, owner/customer AGENTS,
affected backend skill, owner/public roadmap and quality workflows, release
metadata procedure, testing/command/Python guides against their owning code.

- Root README remains an application workflow and links the owning public guides;
  it needs no platform release-state prose. The maintainer README correctly shows
  VERSION 0.8.0 and active RM-2026-018 until verified closure.
- Operational decision, prerequisite, progress and handoff rules now live in the
  existing owner/customer instruction products and roadmap workflows. The owner
  backend skill routes to the single owner SIGSEGV procedure. Other domain skills
  already route to shared workflow and need no duplicated procedure. Customer
  restart/recovery policy and protected-platform authority remain unchanged.
- Quality node `terminal` is declared on the existing full backend nodes. Planner
  dependencies include all other selected nodes, including final test-bearing
  checks. Executor additionally checks the entire recorded graph. Database groups
  retain full backend selection, with migration/constraint/seeding checks first.
  Explicit/legacy/all selections retain their real checks and failure propagation.
- Shared backend `--evidence` is a development command option, declared in the
  command catalogue and public guide. It uses the existing process/suite lifecycle
  and schema-2 result, with private native reports and a predecessor hash link.
  Native engine exit and cleanup results survive collection failure; missing
  provenance blocks acceptance. Ordinary invocation output remains inherited;
  recorded output is displayed after completion. `-p` now uses the same documented
  profile worker limit as `--parallel`.
- Owner closure alone enables bounded recovery through existing `check_evidence`.
  It requires unchanged candidate/configuration/runtime/mode, intact producer
  links, actual native artifacts, remaining gate PASS and exact complete coverage.
  Original native failures stay failed. Successful recovery is terminal, without
  owner exception fields. Customer closure refuses the owner recovery input.
- Source inspection of pinned PHPUnit 13.4.5 / ParaTest 7.27.4 established that
  TeamCity does not emit warning/deprecation events. Partial finished cases count
  only when the completed worker's native zero-issue TestResult also matches.
  Strict byte recognition never deserializes PHP objects. Missing final worker
  receipts leave those finished markers unverified until valid later coverage;
  known issues and unsupported formats block recovery. No worker instrumentation,
  runtime diagnosis, dependency change or alternative runner is introduced.
- New evidence helper ships with shared development tooling; private tests and
  generated artifacts remain excluded from customer/production delivery. No module
  API, business behavior, UI, schema, dependency or independent module manifest is
  changed. Module versions and interactive module examples therefore need no edit.
- The testing guide listed older dependency versions. Lockfile, installed package
  metadata and the retained native snapshot candidate agree on Pest 5.3.4,
  PHPUnit 13.4.5 and ParaTest 7.27.4; corrected that current-state statement without
  changing dependencies.
- No closed roadmap, dated historical result or published tag was edited. The
  RM-2026-017 representation exception is historical and authorizes no new waiver.

Source review found no unresolved blocking security finding in the changed surface.
Artifact inputs remain bounded and descriptor-anchored; native commands are literal
argument arrays and evidence mode refuses weakening flags. PHP objects are never
deserialized by the validator. Configuration/environment data contributes only to
private opaque fingerprints, and actual native exit/cleanup outcomes remain visible.
Automated acceptance is pending; this is not a security gate PASS.

## Regression scenario assessment

Automated scenarios are authored in the existing central tooling suites. Their
complete final execution remains pending; the table records coverage ownership,
not a blanket PASS.

| Task scenarios                         | Owning evidence                                                                                                                                                                     |
| -------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| A–B successful first/second retry      | `test_quality_recovery.py` producer/closure fixtures with native class/dataset IDs, a remaining selection and preserved original FAILED bytes                                       |
| C exhausted recovery                   | Unresolved/exceeded budgets refuse automatic acceptance; existing owner disposition remains separate                                                                                |
| D historical evidence                  | Closure reads retained producer files without rewriting their original failure                                                                                                      |
| E, K missing coverage                  | Recovered case does not complete the original inventory; missing gates and unsealed workers remain blocking                                                                         |
| F inconsistent exception state         | Extra owner-approval/classification fields are refused                                                                                                                              |
| G–H complete graph and recovered graph | Existing native successful graph path plus bounded recovered backend path                                                                                                           |
| I ordinary failures                    | Assertion events, issue-bearing native XML/TestResult, warning flags and unconfirmed failures cannot become SIGSEGV PASS                                                            |
| J incompatible evidence                | Candidate/runtime/dirty source, altered graph/commands/scope, substantive source changes and broken artifact bindings refuse reuse                                                  |
| L cleanup                              | Nonzero cleanup blocks acceptance even when engine exit is zero                                                                                                                     |
| M duplicates                           | Duplicate run IDs/references and duplicate case identities cannot inflate set coverage                                                                                              |
| N retry limits                         | Two additional attempts, omitted intermediate receipts and repetition after recovered success are checked                                                                           |
| O–P backend ordering                   | `test_quality_ordering.py` exercises real owner/customer graphs, selected/all/legacy expansion, cycles and executor blocking                                                        |
| Q post-validation defect               | Source invalidation is rejected by closure tests; owning workflow requires affected non-backend revalidation before invalidated full backend runs last                              |
| R–S established decisions/new defects  | Source-reviewed authority paragraphs distinguish materially new evidence from recurrence and ordinary implementation investigation                                                  |
| T–U accurate progress                  | Status includes counts for the complete recorded scope during active execution; milestone instructions require remaining gates and blockers                                         |
| V closure readiness                    | Existing closure compatibility checks plus new complete recovered evidence, prerequisite, source and cleanup refusals                                                               |
| W–Y background handoff                 | Source-reviewed owner/customer workflow: successful supported background launch ends the turn; no polling, work or automatic continuation; provide status/log/completion/next scope |
| Z short diagnostics                    | Existing synchronous focused workflow remains explicit; no agent simulation framework added                                                                                         |

## Preliminary execution evidence

Supported focused Python diagnostics resolved specific implementation uncertainties:
first/second retry identity normalization, omitted-retry predecessor rejection,
recognition of the real pinned zero-issue worker receipt, and a recovered selection
combined with proven earlier worker results. All returned native zero exits.
These small selections do not replace the complete final tooling suite, and older
diagnostic source snapshots are not reused as final candidate PASS.

Mandatory hooks remain enabled. A scoped Ruff diagnostic exposed an imported-name
shadow and import layout error after the hook's safe error wrapper hid details;
both were corrected before commit. Hooks subsequently passed source secret scans,
formatting, quick lint, repository checks and knowledge freshness.

A supported tooling diagnostic automatically rebuilt the helper management image
after the CLI declaration changed. This early operational build should have been
anticipated before invocation; it is recorded here without claiming final image
acceptance. Its command-help/exit diagnostic passed. The active management image
`runa-management:2f9a44d7a8ef61a1f5e2ceac` is retained for supported tooling.
No test simulation containers, networks or database volumes were provisioned.
Development/test databases, Qdrant/model caches, backups, builder caches and native
historical evidence remain preserved.

## Final acceptance scope and pending results

Selected groups: `docs,tooling,knowledge,delivery,security-source`, plus their
mandatory baseline and real prerequisites. Tooling includes full owner unit and
contract suites, quality/closure contracts, pinned Python analysis and package
checks. Delivery verifies source projection, command transport and customer
instruction contracts; knowledge remains strict. Security-source adds both locked
dependency audits and actual content-bound changed-surface review because native
artifact reads and acceptance trust boundaries changed.

No Laravel/business behavior or PHP test engine implementation changed. Native
report formats were source-verified and exercised with deterministic process
fixtures and a retained zero-issue native snapshot. Full Laravel backend, browser
matrix, installation/update/production acceptance, image scanning and all-group
qualification are excluded, not PASS. Platform 0.8.1 does not trigger the five-minor
milestone cadence. Add a heavy area only for an actual changed contract or newly
demonstrated prerequisite.

Before launch, retain prior latest quality/security evidence, commit this coherent
candidate, refresh indexed sources and preview the exact supported graph. Use the
supported final background gate and end the turn after launch confirmation. The
coordinator owns actual candidate, run ID, graph, timestamps and per-check logs.

Final gate: pending. Release 0.8.1: pending. Merge/tag/publication: pending.
