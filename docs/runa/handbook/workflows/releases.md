# Application release workflow

The application version in `src/app/application.json` is independent of protected src/runa/platform.json; root VERSION is customer scaffold outside the Runa owner checkout. Preserve platform versions/source. Application release notes belong in `docs/app/CHANGELOG.md`; do not inherit platform release history. Repository/tag/publication conventions refer to the configured customer repository, never the Runa source upstream.

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

## Finalization

Reconcile the roadmap's `proposed_version` with the release computed from the actual baseline and `release_type` before closing the roadmap. A draft's projection does not change the installed application version.

Finish all implementation and documentation points, review current-state application/public guidance, and run the complete applicable local quality/security/AI gates. Failed verification requires an inserted fix point and a fresh full run. Security, data-loss and production-isolation failures cannot be warnings. Long background final gates end the agent turn; inspect status later without polling.

Compute the application version from the current application manifest and the accepted roadmap release_type. Only the user approves a major change. Update the application manifest and application changelog, complete the final checklist/evidence, move the roadmap from docs/app/roadmaps/active to closed and set closed_at. Refresh the knowledge index and validate docs, manifest compatibility and release metadata before committing. Runtime or substantive procedure changes after PASS invalidate that result; reviewed release-only metadata edits receive focused checks.

Merge with a merge commit, sanity-check main, create an immutable annotated application tag and publish the application release to its configured repository, then delete the roadmap branch. Never rewrite a published tag or a closed roadmap. Preserve exact Git/build identities and significant verification evidence in the final report. Platform maintenance/release commands are not available in customer installations.

Before application 1.0 or subsequent stable updates, apply [release-scale verification](../release/1.0-readiness.md). See [roadmaps](roadmaps.md) and [Git](git.md).
