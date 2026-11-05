# Named module settings snapshots

The framework owns module snapshot storage in `shared.module_settings_snapshots`.
Module authors consume `Runa\Support\Modules\Settings\SettingsSnapshots` through
`Runa\Support\Modules\Settings\SettingsSnapshotSection`, a source-owned management
adapter. This is a trusted integration, not an unguarded HTTP or customer identity API.
The service runs only inside the adapter's default transaction and registry/authority
locks. Snapshots contain committed non-sensitive values, never form drafts or secrets.
A source-defined scope selects explicit keys; authorization applies to that whole scope.
Modules with field-specific grants must use separately authorized scopes.

The abstract readonly adapter implements ManagementResourceReader/ManagementWriter.
Subclasses define MODULE, SCOPE, READ_PERMISSION, WRITE_PERMISSION, DELETE_ACTION
and `public static function keys(): array` (list of non-sensitive setting keys).
Its public management signatures are `read(ManagementRead): ManagementRecord`,
`page(ManagementRead): ManagementPageResult`, `preview(ManagementWrite): ManagementReview`
and `execute(ManagementWrite): ManagementOutcome`. Catalog records expose JSON text
`snapshots`, nullable text `name`, nullable UUID `snapshot_id` and JSON text `values`.
A record read with publicId returns only that scope's selected snapshot values.
Commands `capture` (name) and `delete` (snapshot_id) never publish settings.
Deletion consumes DELETE_ACTION password proof within the same transaction as Audit.

The trusted store signatures are `catalog(string $module, string $scope): array`,
`capture(string $module, string $scope, array $keys, string $name, int $revision, int $generation): string`,
`load(string $module, string $scope, array $keys, string $publicId): array` and
`delete(string $module, string $scope, string $publicId): void`.
Capture checks the displayed settings revision/generation; module/scope/name is unique
among live snapshots, with a maximum of 100 live snapshots per scope. Names are plain
text, trimmed and bounded to 80 characters. Deletion is soft. Captures and deletions
write explicit Audit in their storage transaction, excluding configuration values.
A snapshot retains a definition fingerprint; incompatible definitions fail closed.
Actual application always uses the owning current validation/publication workflow.

Public `@/runa/admin` exports AdminSettingsPage and AdminSettingsGroup. Its props are
`controller: AdminPageController`, `form: AdminFormController`,
`groups: AdminSettingsGroup[]`, `settingsSection?: string` (default policy),
`snapshotsSection?: string` (default snapshots). Groups have `key`, localized `title`
and explicit ordered `fields: string[]`. It uses public controllers and AdminField;
customer replacements keep this orchestration or compose the same controllers.
Loading changes only draft values. Previous-value information compares the loaded
values with the pre-load draft; cancellation restores that draft. Concurrent updates
use normal reconciliation/conflicts and never silently replay a write. Saving applies
all settings in one owning reviewed publication, with current password proof.

The binding declares its actual AuthStepUp::assertVerified (2.0.0) and
AuditRecords::record (1.0.0) targets; no empty/fictitious target bypass is supported.
Modules retain their native dependencies, including Auth for this password-protected
administration adapter. It never requires Admin for ordinary settings publication.

`useAdminSettingsSnapshots(page: AdminPageController, form: AdminFormController,
settingsSection = 'policy', snapshotsSection = 'snapshots')` is also public. It returns
selected, previous, loadedName, catalog, changes and resource refs/controllers, plus
load(): Promise<void>, cancel(): void, capture(name: string): Promise<boolean>,
remove(): Promise<void> and clear(): void. The settings section declares a reviewed
`publish` command over all scoped writable fields. The snapshots section declares
`capture` and destructive password-protected `delete` commands as above. Snapshots
only persist values and never own business publication or effect reversal.
