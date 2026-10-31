<?php

namespace Runa\Settings;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
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
    public function update(array $patch, int $expectedRevision): array
    {
        if ($expectedRevision < 1 || $patch === [] || array_is_list($patch) || count($patch) > 64
            || strlen(json_encode($patch, JSON_THROW_ON_ERROR)) > 524288) {
            throw $this->reject('application_settings_invalid', 422);
        }

        $this->prepare(true);

        return $this->run(function (Connection $connection) use ($patch, $expectedRevision): array {
            $row = $this->row($connection, true);
            if ((int) $row->revision !== $expectedRevision || $expectedRevision >= 9007199254740991) {
                throw $this->reject('application_settings_revision_changed', 409);
            }
            $definitions = $this->registry->settings();
            foreach ($patch as $key => $value) {
                $definition = $definitions[$key] ?? null;
                if ($definition === null || ! $this->allowed($definition->readPermission) || ! $this->allowed($definition->writePermission)) {
                    throw $this->reject('application_settings_forbidden', 403);
                }
                if (! $definition->accepts($value)) {
                    throw $this->reject('application_settings_invalid', 422);
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
            foreach ($this->registry->settings() as $key => $definition) {
                if ($this->allowed($definition->readPermission)) {
                    $result[$key] = ['title' => $definition->title, 'type' => $definition->type,
                        'max_length' => $definition->maxLength, 'choices' => $definition->choices,
                        'writable' => $this->allowed($definition->writePermission)];
                }
            }

            return $result;
        });
    }

    /** Public presentation alone may use this read; no protected setting is projected.
     * @return array{revision:int,values:array<string,mixed>}
     */
    public function presentation(): array
    {
        try {
            $row = $this->row($this->database->connection());
            /** @var array<string,mixed> $values */
            $values = json_decode($row->values, true, 16, JSON_THROW_ON_ERROR);

            return ['revision' => (int) $row->revision, 'values' => array_intersect_key($values, ['presentation.name' => true, 'presentation.logo' => true])];
        } catch (\Throwable) {
            return ['revision' => 0, 'values' => []];
        }
    }

    /** @return object{revision:int|string,values:string} */
    private function row(Connection $connection, bool $lock = false): object
    {
        /** @var object{revision:int|string,values:string}|null $row */
        $row = $connection->table('shared.application_settings')->where('singleton', 1)->when($lock, fn ($query) => $query->lockForUpdate())->first(['revision', 'values']);

        return $row ?? throw $this->reject('application_settings_unavailable', 503);
    }

    /** @param object{revision:int|string,values:string} $row @return array{revision:int,values:array<string,string|int|bool|null>} */
    private function project(object $row): array
    {
        /** @var array<string,mixed> $stored */
        $stored = json_decode($row->values, true, 16, JSON_THROW_ON_ERROR);
        $values = [];
        foreach ($this->registry->settings() as $key => $definition) {
            if ($this->allowed($definition->readPermission)) {
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
