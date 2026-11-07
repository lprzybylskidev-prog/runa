<?php

namespace Runa\ModuleFramework\Settings;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Connection;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\SettingDefinition;
use Runa\Contracts\Modules\SettingsAccess;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\TransactionSecurity;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\ModuleFramework\Transactions\HealthAdmissions;
use Runa\Support\Modules\Api\OperationRejected;

/** Native settings security, sharing the existing Auth/Permissions/Audit protocol. */
final readonly class SettingsSecurity
{
    public function __construct(
        private Connection $connection, private ModuleRegistry $registry,
        private OperationGuard $guard, private PrincipalScope $principals,
        private AuthorityLock $authority, private TransactionSecurity $security,
        private HealthAdmissions $admissions, private Application $app,
    ) {}

    /** @template T
     * @param list<string> $keys
     * @param Closure(SettingsAdmission):T $work
     * @return T */
    public function publication(string $module, SettingsAccess $access, array $keys, ?SettingsActor $actor, Closure $work): mixed
    {
        if ($this->connection->transactionLevel() !== 0) {
            throw new OperationRejected('transaction_boundary_invalid', $module, 'settings.publish', 409);
        }
        $principal = $this->principals->current();
        if ($principal === null) {
            // A real HTTP request, including an HTTP-kernel request in a CLI test,
            // never selects operator authority by omitting its actor metadata.
            if (! $this->app->runningInConsole() || $this->app->make('request')->route() !== null || $actor?->kind === 'actor') {
                throw new OperationRejected('authentication_required', $module, 'settings.publish', 401);
            }
        } else {
            if ($principal->kind !== 'human' || ($actor !== null && ($actor->kind !== 'actor' || $actor->id !== $principal->publicId))) {
                throw new OperationRejected('settings_actor_invalid', $module, 'settings.publish', 403);
            }
            $actor = new SettingsActor('actor', $principal->publicId);
        }
        $scope = new SettingsAdmission($module, $access, $keys, $principal, $actor, $this->connection->getPdo(), $this->registry->snapshot()->generation);
        if ($principal === null) {
            return $work($scope);
        }

        return $this->admissions->run(function () use ($scope): void {
            $this->authorize($scope);
            if ($scope->access->proof !== null) {
                $this->guard->enter('runa.auth', new ApiOperation('step_up.assert'));
            }
            $this->guard->enter('runa.audit', new ApiOperation('audit.record'));
        }, fn (): mixed => $work(new SettingsAdmission($scope->module, $scope->access, $scope->keys,
            $scope->principal, $scope->actor, $scope->pdo, $this->registry->snapshot()->generation)));
    }

    public function lock(SettingsAdmission $scope): void
    {
        $this->assertCurrent($scope);
        if ($scope->principal !== null) {
            $this->authority->acquire($this->connection);
            $this->security->lockSubjects([$scope->principal->publicId]);
            $this->authorize($scope);
        }
    }

    public function consume(SettingsAdmission $scope): void
    {
        $this->assertCurrent($scope);
        if ($scope->principal !== null && $scope->access->proof !== null) {
            $this->security->proof($scope->access->proof, true);
        }
    }

    /** Final check before the native generation bump, while source is still available. */
    public function finish(SettingsAdmission $scope): void
    {
        $this->assertCurrent($scope);
        if ($scope->principal !== null) {
            $this->authorize($scope);
            $this->admissions->assertCurrent();
        }
    }

    public function assertCurrent(SettingsAdmission $scope): void
    {
        if ($this->connection !== $this->app->make('db')->connection() || $this->connection->transactionLevel() !== 1
            || $this->connection->getPdo() !== $scope->pdo || $this->principals->current() !== $scope->principal
            || $this->registry->snapshot()->generation !== $scope->generation) {
            throw new OperationRejected('settings_authority_changed', $scope->module, 'settings.publish', 409);
        }
    }

    private function authorize(SettingsAdmission $scope): void
    {
        $this->guard->enter($scope->module, $scope->access->write);
        foreach ($scope->keys as $key) {
            $permission = $scope->access->writeFields[$key] ?? null;
            if ($permission !== null) {
                $this->guard->enter($scope->module, new ApiOperation($scope->access->write->key, $permission));
            }
        }
    }

    /** Authorized projection only. Owning runtime reads remain separate.
     * @param  Closure():array{generation:int,review_id:string,revision:int,settings:array<string,array{definition:SettingDefinition,value?:mixed,set?:bool}>}  $read
     * @return array{generation:int,review_id:string,revision:int,settings:array<string,array{definition:SettingDefinition,value?:mixed,set?:bool}>}
     */
    public function read(string $module, SettingsAccess $access, Closure $read): array
    {
        $principal = $this->principals->current();
        if ($principal === null || $principal->kind !== 'human' || $this->connection->transactionLevel() !== 0 || $this->connection !== $this->app->make('db')->connection()) {
            throw new OperationRejected('authentication_required', $module, 'settings.read', 401);
        }
        $pdo = $this->connection->getPdo();

        return $this->admissions->run(fn () => $this->guard->enter($module, $access->read), function () use ($module, $access, $read, $principal, $pdo): array {
            return $this->connection->transaction(function () use ($module, $access, $read, $principal, $pdo): array {
                $this->registry->snapshot();
                $this->authority->acquire($this->connection);
                $this->security->lockSubjects([$principal->publicId]);
                $context = $this->guard->enter($module, $access->read);
                $result = $read();
                foreach ($access->readFields as $key => $permission) {
                    try {
                        $this->guard->enter($module, new ApiOperation($access->read->key, $permission));
                    } catch (OperationRejected $error) {
                        if ($error->status !== 403) {
                            throw $error;
                        }
                        unset($result['settings'][$key]);
                    }
                }
                $this->guard->assertAvailable($context);
                $this->admissions->assertCurrent();
                if ($this->principals->current() !== $principal || $this->connection !== $this->app->make('db')->connection() || $this->connection->getPdo() !== $pdo || $this->connection->transactionLevel() !== 1) {
                    throw new OperationRejected('settings_authority_changed', $module, 'settings.read', 409);
                }

                return $result;
            }, 1);
        });
    }
}
