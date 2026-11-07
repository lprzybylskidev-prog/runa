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
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Source-owned adapter; native module validation/publication remains separate. */
abstract readonly class SettingsSnapshotSection implements ManagementResourceReader, ManagementWriter
{
    protected const string MODULE = '';

    protected const string SCOPE = '';

    protected const string READ_PERMISSION = '';

    protected const string WRITE_PERMISSION = '';

    protected const string DELETE_ACTION = '';

    public function __construct(private DatabaseManager $database, private SettingsSnapshots $snapshots,
        private ModuleSettings $settings, private OperationGuard $guard, private PrincipalScope $principals,
        private AuthorityLock $authority, private ModuleApis $apis, private HealthAdmissions $admissions) {}

    /** @return list<string> */
    abstract public static function keys(): array;

    public function read(ManagementRead $input): ManagementRecord
    {
        return $this->run(false, function () use ($input): ManagementRecord {
            $state = $this->settings->read(static::MODULE);

            return new ManagementRecord($input->publicId, $state['revision'].':'.$state['review_id'], [
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
            if ($input->publicId !== null || preg_match('/\A([0-9]{1,9}):([a-f0-9]{64})\z/D', $input->revision, $parts) !== 1) {
                throw $this->reject('admin_input_invalid', 422);
            }
            if ($input->command === 'capture' && array_keys($input->values) === ['name'] && is_string($input->values['name'])) {
                $this->snapshots->capture(static::MODULE, static::SCOPE, static::keys(), $input->values['name'], (int) $parts[1], $parts[2]);
            } elseif ($input->command === 'delete' && array_keys($input->values) === ['snapshot_id'] && is_string($input->values['snapshot_id']) && $input->confirmed) {
                $this->apis->resolve('runa.auth', AuthStepUp::class, '2.0.0')->assertVerified(static::DELETE_ACTION, everyAction: true);
                $this->snapshots->delete(static::MODULE, static::SCOPE, $input->values['snapshot_id']);
            } else {
                throw $this->reject('admin_input_invalid', 422);
            }

            return new ManagementOutcome('committed', $this->read(new ManagementRead));
        }, $input->command === 'delete');
    }

    /**
     * @template T
     *
     * @param Closure():T $work
     * @return T */
    private function run(bool $write, Closure $work, bool $delete = false): mixed
    {
        $principal = $this->principals->current();
        if ($principal === null || $principal->kind !== 'human') {
            throw $this->reject('authentication_required', 401);
        }
        $connection = $this->database->connection();
        $pdo = $connection->getPdo();
        $operation = new ApiOperation('settings.snapshots', $write ? static::WRITE_PERMISSION : static::READ_PERMISSION);
        $read = new ApiOperation('settings.snapshots', static::READ_PERMISSION);

        return $this->admissions->within(function () use ($operation, $read, $write, $delete): void {
            $this->guard->enter(static::MODULE, $operation);
            $this->guard->enter(static::MODULE, $read);
            if ($write) {
                $this->guard->enter('runa.audit', new ApiOperation('audit.record'));
            }
            if ($delete) {
                $this->guard->enter('runa.auth', new ApiOperation('step_up.assert'));
            }
        }, function () use ($connection, $pdo, $principal, $work, $operation, $read) {
            return $connection->transaction(function () use ($connection, $pdo, $principal, $work, $operation, $read) {
                $level = $connection->transactionLevel();
                $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->firstOrFail();
                $this->authority->acquire($connection);
                $context = $this->guard->enter(static::MODULE, $operation);
                // Write authority never substitutes read authority for the captured scope.
                $this->guard->enter(static::MODULE, $read);
                $result = $work();
                $this->guard->assertAvailable($context);
                $this->admissions->assertCurrent();
                if ($this->database->connection() !== $connection || $connection->getPdo() !== $pdo
                    || $this->principals->current() !== $principal || $connection->transactionLevel() !== $level) {
                    throw $this->reject('admission_changed', 409);
                }

                return $result;
            }, 1);
        });
    }

    private function reject(string $reason, int $status): OperationRejected
    {
        return new OperationRejected($reason, static::MODULE, 'settings.snapshots', $status);
    }
}
