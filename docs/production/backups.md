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

## Implemented archive and restore contract

Configure `.env.prod.backup` from `.env.prod.backup.example`: `BACKUP_DIRECTORY` is a dedicated absolute private host directory (mode 700), `BACKUP_RETENTION` is a positive complete-backup count. Backups use IDs such as `backup-20261017T123456123456Z`; verify/restore accept IDs only, never arbitrary paths. Keep the directory and manifests trusted and private; checksum validation detects corruption, not malicious replacement by an operator with access to database credentials.

`create` requires a healthy managed deployment and no unresolved operation. It records recovery state and stops Nginx, workers, scheduler and PHP-FPM while PostgreSQL remains available, then writes a custom-format PostgreSQL dump and `storage/app` archive. Framework sessions/cache/health files and logs are runtime state, excluded from durable-file restore. The archive manifest records the exact deployment and SHA256 digests. Output is published only after dump/archive validation; failure keeps a `.partial` directory, restores service startup, returns nonzero and never prunes partial output as though it were a good backup.

Retention considers only complete backup-ID directories under the configured target, verifies each candidate before deletion and never follows directory symlinks. Restore protects its chosen source from retention while creating the mandatory pre-restore backup. No global Docker/volume/file pruning occurs.

`verify <backup-id>` checks all digests, reads the full gzip/tar contents, rejects traversal, links and special files, parses the PostgreSQL archive, then actually restores into a randomly named `runa_backup_verify_*` database and checks `shared.migrations`. The validation database is dropped on success or failure. It does not overwrite the application database/storage.

`restore <backup-id>` requires the same application SHA and real/simulation context as the backup, performs verification including the isolated restore, shows the explicit target and obtains typed confirmation (or the deliberately named noninteractive flag). It creates a pre-restore backup, records recovery state, stops application writers, recreates only database `runa`, restores as runtime role `runa`, reapplies database/public-schema restrictions and replaces only `storage/app`. Services are recreated and health/isolation checked before success is recorded. Failure leaves the recovery marker and may leave writers stopped; inspect and recover deliberately. No automatic migration reversal is attempted.

Database-tool stderr is captured to avoid echoing restored application values into operational logs. Failures remain nonzero and require diagnosis in the private operator context.
