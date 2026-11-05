<?php

namespace Runa\Support\Modules\Settings;

use Closure;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\ManagementOutcome;
use Runa\Contracts\Modules\ManagementPageResult;
use Runa\Contracts\Modules\ManagementRead;
use Runa\Contracts\Modules\ManagementRecord;
use Runa\Contracts\Modules\ManagementResourceReader;
use Runa\Contracts\Modules\ManagementReview;
use Runa\Contracts\Modules\ManagementWrite;
use Runa\Contracts\Modules\ManagementWriter;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Source-owned adapter; native module validation/publication remains separate. */
abstract readonly class SettingsSnapshotSection implements ManagementResourceReader, ManagementWriter
{
    protected const MODULE = '';

    protected const SCOPE = '';

    protected const READ_PERMISSION = '';

    protected const WRITE_PERMISSION = '';

    protected const DELETE_ACTION = '';

    public function __construct(private DatabaseManager $database, private SettingsSnapshots $snapshots,
        private ModuleSettings $settings, private OperationGuard $guard, private PrincipalScope $principals,
        private AuthorityLock $authority, private ModuleApis $apis) {}

    /** @return list<string> */
    abstract public static function keys(): array;

    public function read(ManagementRead $input): ManagementRecord
    {
        return $this->run(false, function () use ($input): ManagementRecord {
            $state = $this->settings->read(static::MODULE);

            return new ManagementRecord($input->publicId, $state['revision'].':'.$state['generation'], [
                'snapshots' => json_encode($this->snapshots->catalog(static::MODULE, static::SCOPE), JSON_THROW_ON_ERROR),
                'name' => null, 'snapshot_id' => null,
                'values' => json_encode($input->publicId === null ? new \stdClass : $this->snapshots->load(static::MODULE, static::SCOPE, static::keys(), $input->publicId), JSON_THROW_ON_ERROR),
            ]);
        });
    }

    public function page(ManagementRead $input): ManagementPageResult
    {
        throw $this->reject('admin_operation_unavailable', 404);
    }

    public function preview(ManagementWrite $input): ManagementReview
    {
        throw $this->reject('admin_operation_unavailable', 404);
    }

    public function execute(ManagementWrite $input): ManagementOutcome
    {
        return $this->run(true, function () use ($input): ManagementOutcome {
            if ($input->publicId !== null || preg_match('/\A([0-9]{1,9}):([0-9]{1,9})\z/D', $input->revision, $parts) !== 1) {
                throw $this->reject('admin_input_invalid', 422);
            }
            if ($input->command === 'capture' && array_keys($input->values) === ['name'] && is_string($input->values['name'])) {
                $this->snapshots->capture(static::MODULE, static::SCOPE, static::keys(), $input->values['name'], (int) $parts[1], (int) $parts[2]);
            } elseif ($input->command === 'delete' && array_keys($input->values) === ['snapshot_id'] && is_string($input->values['snapshot_id']) && $input->confirmed) {
                $this->apis->resolve('runa.auth', AuthStepUp::class, '2.0.0')->assertVerified(static::DELETE_ACTION, everyAction: true);
                $this->snapshots->delete(static::MODULE, static::SCOPE, $input->values['snapshot_id']);
            } else {
                throw $this->reject('admin_input_invalid', 422);
            }

            return new ManagementOutcome('committed', $this->read(new ManagementRead));
        });
    }

    /** @template T @param Closure():T $work @return T */
    private function run(bool $write, Closure $work): mixed
    {
        if ($this->principals->current() === null) {
            throw $this->reject('authentication_required', 401);
        }
        $operation = new ApiOperation('settings.snapshots', $write ? static::WRITE_PERMISSION : static::READ_PERMISSION);
        $this->guard->enter(static::MODULE, $operation);
        if ($write) {
            $this->guard->enter('runa.audit', new ApiOperation('audit.record'));
        }

        return $this->database->connection()->transaction(function ($connection) use ($work, $operation) {
            $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->firstOrFail();
            $this->authority->acquire($connection);
            $context = $this->guard->enter(static::MODULE, $operation);
            // Write authority never substitutes read authority for the captured scope.
            $this->guard->enter(static::MODULE, new ApiOperation('settings.snapshots', static::READ_PERMISSION));
            $result = $work();
            $this->guard->assertAvailable($context);

            return $result;
        });
    }

    private function reject(string $reason, int $status): OperationRejected
    {
        return new OperationRejected($reason, static::MODULE, 'settings.snapshots', $status);
    }
}
