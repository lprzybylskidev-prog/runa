# Release and Versioning Workflow

## Canonical version

The root `VERSION` file is the canonical project version.

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

`CHANGELOG.md` is generated and maintained by the agent from roadmap facts.

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

The GitHub Release may be richer than `CHANGELOG.md`, but both must derive from the same roadmap facts and must not contradict each other.

## Finalization sequence

Near roadmap completion:

1. complete implementation,
2. run the full applicable local gate,
3. resolve failures using inserted fix points and rerun,
4. prepare concise final technical report,
5. compute the version from current `VERSION` and `release_type`,
6. update `VERSION`,
7. update `CHANGELOG.md`,
8. move the roadmap to `closed/` on the roadmap branch,
9. perform focused post-metadata consistency checks,
10. commit final release metadata,
11. merge with a merge commit to `main`,
12. sanity-check `main`,
13. create annotated tag,
14. push `main` and tag,
15. publish GitHub Release,
16. delete roadmap branch.

If release metadata changes invalidate a checked condition, rerun the relevant lightweight validation. Do not rerun expensive unrelated work mechanically unless the final quality contract requires it.

## Release 1.0

A user decision is required to change major version from 0 to 1.

Before 1.0, follow `docs/release/1.0-readiness.md`.

## Read-only metadata validation

`./bin/doctor release` validates the pre-gate active state and prints the expected next version. It does not edit files, tag, push or publish. The only open checklist item at that stage must be final verification.

Run it again after updating VERSION/CHANGELOG and moving the roadmap to closed. A closed roadmap needs its completion checklist, `closed_at` date and `## Final report` naming its ID and version; the newest changelog entry identifies the same roadmap. Any existing release tag must be annotated and identify HEAD. The roadmap branch additionally checks the version increment against main; after merge the same metadata check is part of main sanity.

For `release_type: release`, explicitly record the user-approved `target_version: X.Y.Z` in frontmatter. Feature/fix/maintenance compute their normal increment and never infer a major bump. Isolated contracts run via `python3 tools/testing/release.py` and the final quality gate. These checks do not prove remote GitHub publication or replace full quality/security evidence.
