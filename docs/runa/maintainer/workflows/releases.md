# Release and Versioning Workflow

## Canonical version

The root `VERSION` file is the canonical platform version. The protected code manifest `src/runa/platform.json` must carry the same version; owner release validation rejects drift. Installed runtime and browser builds use src/runa/platform.json independently of customer scaffold VERSION. The customer application version in `src/app/application.json` is independent and is never bumped by a platform release.

Initial version:

`0.0.0`

## Version semantics

Runa uses three numeric components with project-specific meaning.

### Major

- `0` — development/pre-1.0.
- `1` — first ready release.
- `2+` — later major project stages/modules/breaking eras.

Only the user decides a major bump.

A major bump resets minor and patch.

### Feature roadmap

Increment minor and reset patch to zero.

Example:

`1.12.2` -> `1.13.0`

### Fix roadmap

Increment patch.

Example:

`1.12.0` -> `1.12.1`

### Maintenance roadmap

Increment patch.

A maintenance release is still a real release even when it has no user-facing feature.

### Release roadmap

A special release roadmap targets a user-selected version.

## One roadmap, one release

Every completed roadmap produces one release.

Roadmap IDs are independent from version numbers.

## Changelog

`docs/runa/maintainer/CHANGELOG.md` is generated and maintained by the agent from roadmap facts.

Keep it concise:

- version,
- date,
- category,
- roadmap ID,
- concise change summary.

Do not manually invent or duplicate release history.

## Git tag

Every published version has an immutable annotated tag:

`vX.Y.Z`

The release records the exact Git SHA/build identity.

Do not move/rewrite a published tag.

## GitHub Release

Every published version has a GitHub Release.

The GitHub Release may be richer than `docs/runa/maintainer/CHANGELOG.md`, but both must derive from the same roadmap facts and must not contradict each other.

## Finalization sequence

Near roadmap completion:

1. complete implementation,
2. run the complete agreed quality selection and its mandatory/prerequisite gates,
3. resolve failures using inserted fix points and rerun,
4. prepare concise final technical report,
5. compute the version from current `VERSION` and `release_type`,
   reconcile the roadmap's `proposed_version` with that result before closure,
6. update `VERSION` and the version field of `src/runa/platform.json` to the same value; preserve `src/app/application.json`,
7. update `docs/runa/maintainer/CHANGELOG.md` and synchronize the maintainer README current version,
8. complete the roadmap checklist/review evidence, move it to `closed/` on the roadmap branch and set the maintainer README active roadmap to `none`,
9. refresh the knowledge index incrementally and run `python3 tools/maintainer/check-docs` and `python3 tools/maintainer/check-release`, including the closure-state review from the roadmap workflow,
10. commit final release metadata,
11. merge with a merge commit to `main`,
12. sanity-check `main`,
13. create annotated tag,
14. push `main` and tag,
15. publish GitHub Release,
16. delete roadmap branch.

Reviewed release-only edits after the full gate are limited to VERSION, the matching version field in src/runa/platform.json, CHANGELOG, roadmap checklist/closure/report evidence and the maintainer README block's version/active-roadmap values. Reindex affected knowledge and run focused docs/release checks. Capability prose, procedures, validators or runtime changes require an inserted fix point and a new full gate. Do not reuse a PASS for substantive changes. Repeat docs/release checks as part of main sanity.

## Release 1.0

A user decision is required to change major version from 0 to 1.

Before 1.0, follow `docs/runa/maintainer/release/1.0-readiness.md`.

## Read-only metadata validation

`python3 tools/maintainer/check-release` validates the pre-gate active state and prints the expected next version. It does not edit files, tag, push or publish. The only open checklist item at that stage must be final verification.

Run it again after updating VERSION/CHANGELOG/README and moving the roadmap to closed. A closed roadmap needs its completion checklist, `closed_at` date and `## Final report` naming its ID and version; the newest changelog entry identifies the same roadmap. Any existing release tag must be annotated and identify HEAD. On main only, a tagged ancestor may be followed by draft roadmap Markdown changes under `docs/runa/maintainer/roadmaps/draft/`; all other paths must be identical to the tagged release, whose own version/changelog/closed-roadmap metadata must be valid. Runtime changes or unrelated metadata drift never qualify as draft planning. The roadmap branch additionally checks the version increment against main; after merge the same metadata check is part of main sanity.

For `release_type: release`, explicitly record the user-approved `target_version: X.Y.Z` in frontmatter. Feature/fix/maintenance compute their normal increment and never infer a major bump. Isolated contracts run via `python3 tools/maintainer/testing/release.py` and the final quality gate. These checks do not prove remote GitHub publication or replace full quality/security evidence.

## Checked README current state

docs/runa/maintainer/README.md has exactly one block between `<!-- runa:current-state -->` and `<!-- /runa:current-state -->`. Its content is exactly two ordered Markdown list fields, with inline-code values:

1. `Current version`: canonical VERSION, in X.Y.Z form.
2. `Active roadmap`: the ID from the single active roadmap's frontmatter, or `none`. Its status, filename and branch must agree with its active location and current branch.

Use these fields as a checked projection of existing sources, not as a second source of truth. Keep these current-state facts in the block instead of repeating values elsewhere in README prose. The block does not claim GitHub publication. Examples and historical documents are outside this bounded syntax and remain untouched.

`doctor docs` checks this block and command documentation coverage. `doctor release` invokes the same documentation checks in addition to release metadata checks, so final verification covers documentation even without a documentation impact area. Arbitrary capability prose still requires the recorded roadmap review. Offline contracts are `python3 tools/maintainer/testing/docs.py` and `python3 tools/maintainer/testing/release.py`.

## Selected-scope evidence

New roadmaps record `quality_groups` (comma-separated names or `all`) and a nonempty
`quality_rationale` before final execution. Preview through the supported quality
plan command. Final and closure must match this declaration, graph/commands, actual
complete PASS and candidate commit. An exploratory/narrower or stale run cannot
close the roadmap. After failures or scope changes, rerun the complete agreed plan
from the beginning. Only reviewed release-only metadata may follow PASS. Legacy
flag-free workflows retain existing behavior; the agent normally chooses groups
without asking the owner on every invocation. See the quality guide.
