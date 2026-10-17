# Backups and Restore

## Scope

Baseline backup covers:
- PostgreSQL,
- application persistent storage.

Canonical interface:

- `./bin/backup create`
- `./bin/backup verify`
- `./bin/backup restore`

Backup target/location and retention are configurable.

Do not force a specific cloud provider such as S3 into the baseline.

## Principles

A backup is not considered trustworthy merely because a file exists.

Verification must include meaningful integrity/restore validation.

Before 1.0 and periodically thereafter, perform a real restore test in a safe non-production target.

## Create

Backup creation should:
- identify project/version/time,
- fail clearly on partial output,
- avoid embedding secrets in filenames/logs,
- preserve the information needed to restore database and storage consistently.

## Verify

Verification should detect:
- missing files,
- unreadable/corrupt archives where practical,
- invalid PostgreSQL dump,
- obvious incomplete storage backup,
- metadata/version mismatch.

For higher assurance, restore to an isolated validation target and run smoke checks.

## Restore

Restore is destructive.

Before restore:
- clearly show the target,
- require explicit confirmation for interactive use,
- provide an intentional non-interactive mode for automation/operations,
- verify target is not an accidental development/test mismatch.

Do not overwrite a running production database/storage blindly.

Document and execute service stop/maintenance steps appropriate to the current production architecture.

## Retention

Retention is configurable.

Deletion/pruning must operate only on the configured Runa backup target, not arbitrary host files.

## Logs

Backup/restore commands are English-only and must provide concise actionable output without printing secrets or sensitive application payloads.
