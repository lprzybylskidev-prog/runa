<?php

namespace Runa\Settings;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Settings\ApplicationImages;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\Modules\Audit\Contracts\AuditEvent;
use Runa\Modules\Audit\Contracts\AuditRecords;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** The singleton row and explicit redacted Audit share the default root transaction. */
final readonly class SettingStore
{
    public function __construct(private DatabaseManager $database, private SettingRegistry $registry,
        private OperationGuard $guard, private PrincipalScope $principals, private AuthorityLock $authority,
        private ModuleApis $apis) {}

    /** @return array{revision:int,values:array<string,string|int|bool|null>} */
    public function read(): array
    {
        return $this->run(function (Connection $connection): array {
            $row = $this->row($connection);

            return $this->project($row);
        });
    }

    /** @param array<string,mixed> $patch @return array{revision:int,values:array<string,string|int|bool|null>} */
    public function update(array $patch, int $expectedRevision, ?ApplicationImages $images = null): array
    {
        if ($expectedRevision < 1 || $patch === [] || array_is_list($patch) || count($patch) > 64
            || strlen(json_encode($patch, JSON_THROW_ON_ERROR)) > 524288) {
            throw $this->reject('application_settings_invalid', 422);
        }

        $this->prepare(true);

        return $this->run(function (Connection $connection) use ($patch, $expectedRevision, $images): array {
            $row = $this->row($connection, true);
            if ((int) $row->revision !== $expectedRevision || $expectedRevision >= 9007199254740991) {
                throw $this->reject('application_settings_revision_changed', 409);
            }
            $definitions = $this->registry->settings();
            $permissions = [];
            foreach ($patch as $key => $value) {
                $definition = $definitions[$key] ?? null;
                if ($definition === null || ! ($permissions[$definition->readPermission] ??= $this->allowed($definition->readPermission))
                    || ! ($permissions[$definition->writePermission] ??= $this->allowed($definition->writePermission))) {
                    throw $this->reject('application_settings_forbidden', 403);
                }
                if (! $definition->accepts($value)) {
                    throw $this->reject('application_settings_invalid', 422);
                }
            }
            foreach (['logo', 'favicon'] as $kind) {
                $key = 'presentation.'.$kind;
                $uploaded = $patch[$key] ?? null;
                if (isset($images?->png[$kind]) && $uploaded !== 'uploaded') {
                    throw $this->reject('application_settings_invalid', 422);
                }
                if ($uploaded === 'uploaded' && ! isset($images?->png[$kind]) && ! $row->{'has_'.$kind}) {
                    throw $this->reject('application_settings_invalid', 422);
                }
            }
            if ($images !== null) {
                $assignments = $bindings = [];
                foreach ($images->png as $kind => $bytes) {
                    // Identifiers come only from DTO keys; publish the paired images in one statement.
                    $column = match ($kind) {
                        'logo' => 'branding_logo', 'logo_icon' => 'branding_logo_icon', 'favicon' => 'branding_favicon'
                    };
                    $assignments[] = $column." = decode(?, 'base64')";
                    $bindings[] = base64_encode($bytes);
                }
                $connection->update('UPDATE shared.application_settings SET '.implode(', ', $assignments).' WHERE singleton = 1', $bindings);
            }
            foreach (['logo' => ['branding_logo', 'branding_logo_icon'], 'favicon' => ['branding_favicon']] as $kind => $columns) {
                if (array_key_exists('presentation.'.$kind, $patch) && $patch['presentation.'.$kind] !== 'uploaded') {
                    $connection->table('shared.application_settings')->where('singleton', 1)->update(array_fill_keys($columns, null));
                }
            }
            /** @var array<string,mixed> $values */
            $values = json_decode($row->values, true, 16, JSON_THROW_ON_ERROR);
            // Preserve values for temporarily removed App definitions across deployment.
            foreach ($patch as $key => $value) {
                if ($value === null) {
                    unset($values[$key]);
                } else {
                    $values[$key] = $value;
                }
            }
            $connection->table('shared.application_settings')->where('singleton', 1)->update([
                'values' => json_encode((object) $values, JSON_THROW_ON_ERROR),
                'revision' => $expectedRevision + 1, 'updated_at' => now('UTC'),
            ]);
            try {
                $this->apis->resolve('runa.audit', AuditRecords::class, '1.0.0')->record(new AuditEvent(
                    'runa.audit', 'application.settings.changed', context: ['keys' => array_keys($patch), 'revision' => $expectedRevision + 1],
                ));
            } catch (\Throwable) {
                throw $this->reject('application_settings_audit_unavailable', 503);
            }

            return $this->project($this->row($connection));
        });
    }

    /** Only permission-filtered metadata; never private grant names or hidden choices.
     * @return array<string,array<string,mixed>>
     */
    public function definitions(): array
    {
        return $this->run(function (): array {
            $result = [];
            $permissions = [];
            foreach ($this->registry->settings() as $key => $definition) {
                if ($permissions[$definition->readPermission] ??= $this->allowed($definition->readPermission)) {
                    $result[$key] = ['title' => $definition->title, 'type' => $definition->type,
                        'max_length' => $definition->maxLength, 'default' => $definition->default,
                        'choices' => $definition->type === 'timezone' ? \DateTimeZone::listIdentifiers() : $definition->choices,
                        'writable' => $permissions[$definition->writePermission] ??= $this->allowed($definition->writePermission)];
                }
            }

            return $result;
        });
    }

    /** Public presentation alone may use this read; no protected setting is projected.
     * @return array{revision:int,values:array<string,mixed>,assets:array<string,string>}
     */
    public function presentation(): array
    {
        try {
            /** @var object{revision:int|string,values:string,logo:?string,logo_icon:?string,favicon:?string}|null $row */
            $row = $this->database->connection()->table('shared.application_settings')->where('singleton', 1)->select(['revision', 'values'])
                ->selectRaw("encode(sha256(branding_logo), 'hex') as logo, encode(sha256(branding_logo_icon), 'hex') as logo_icon, encode(sha256(branding_favicon), 'hex') as favicon")->first();
            if ($row === null) {
                throw $this->reject('application_settings_unavailable', 503);
            }
            /** @var array<string,mixed> $values */
            $values = json_decode($row->values, true, 16, JSON_THROW_ON_ERROR);

            $public = array_intersect_key($values, array_fill_keys(['presentation.name', 'presentation.logo', 'presentation.favicon', 'presentation.locale', 'presentation.theme', 'presentation.timezone'], true));
            $assets = [];
            foreach (['logo', 'logo_icon', 'favicon'] as $kind) {
                if (is_string($row->$kind)) {
                    $assets[$kind] = $row->$kind;
                }
            }

            return ['revision' => (int) $row->revision, 'values' => $public, 'assets' => $assets];
        } catch (\Throwable) {
            return ['revision' => 0, 'values' => [], 'assets' => []];
        }
    }

    /** Public current assets only; immutable hash URLs never expose arbitrary files. */
    public function asset(string $kind, string $hash): ?string
    {
        if (! in_array($kind, ['logo', 'logo_icon', 'favicon'], true) || ! preg_match('/\A[a-f0-9]{64}\z/D', $hash)) {
            return null;
        }
        try {
            // The validated kind selects one fixed column; unrelated images and
            // the settings document are never transferred for an asset request.
            $encoded = $this->database->connection()->table('shared.application_settings')->where('singleton', 1)
                ->selectRaw("encode(branding_{$kind}, 'base64') as image")->value('image');
            $bytes = is_string($encoded) ? base64_decode($encoded, true) : false;

            return $bytes !== false && hash_equals(hash('sha256', $bytes), $hash) ? $bytes : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return object{revision:int|string,values:string,has_logo:bool,has_favicon:bool} */
    private function row(Connection $connection, bool $lock = false): object
    {
        /** @var object{revision:int|string,values:string,has_logo:bool,has_favicon:bool}|null $row */
        $row = $connection->table('shared.application_settings')->where('singleton', 1)->when($lock, fn ($query) => $query->lockForUpdate())->select(['revision', 'values'])
            ->selectRaw('branding_logo IS NOT NULL as has_logo, branding_favicon IS NOT NULL as has_favicon')->first();

        return $row ?? throw $this->reject('application_settings_unavailable', 503);
    }

    /** @param object{revision:int|string,values:string} $row @return array{revision:int,values:array<string,string|int|bool|null>} */
    private function project(object $row): array
    {
        /** @var array<string,mixed> $stored */
        $stored = json_decode($row->values, true, 16, JSON_THROW_ON_ERROR);
        $values = [];
        $permissions = [];
        foreach ($this->registry->settings() as $key => $definition) {
            if ($permissions[$definition->readPermission] ??= $this->allowed($definition->readPermission)) {
                $value = $stored[$key] ?? $definition->default;
                $values[$key] = ($value === null || is_string($value) || is_int($value) || is_bool($value)) && $definition->accepts($value) ? $value : $definition->default;
            }
        }

        return ['revision' => (int) $row->revision, 'values' => $values];
    }

    private function allowed(string $permission): bool
    {
        try {
            $this->guard->enter('runa.permissions', new ApiOperation('application.settings', $permission));

            return true;
        } catch (OperationRejected $error) {
            if ($error->status !== 403) {
                throw $error;
            }

            return false;
        }
    }

    /** Reserved preflight for enclosing framework transactions; always rechecked inside. */
    public function prepare(bool $write = false): void
    {
        $this->guard->enter('runa.permissions', new ApiOperation('application.settings'));
        if ($write) {
            $this->guard->enter('runa.audit', new ApiOperation('audit.record'));
        }
    }

    /** @template T @param Closure(Connection):T $work @return T */
    private function run(Closure $work): mixed
    {
        if ($this->principals->current() === null) {
            throw $this->reject('authentication_required', 401);
        }
        $this->guard->enter('runa.permissions', new ApiOperation('application.settings'));
        try {
            return $this->database->connection()->transaction(function (Connection $connection) use ($work): mixed {
                $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first()
                    ?? throw $this->reject('application_settings_unavailable', 503);
                $this->authority->acquire($connection);
                $context = $this->guard->enter('runa.permissions', new ApiOperation('application.settings'));
                $result = $work($connection);
                $this->guard->assertAvailable($context);

                return $result;
            });
        } catch (OperationRejected $error) {
            throw $error;
        } catch (\Throwable) {
            throw $this->reject('application_settings_unavailable', 503);
        }
    }

    private function reject(string $reason, int $status): OperationRejected
    {
        return new OperationRejected($reason, 'runa.permissions', 'application.settings', $status);
    }
}
