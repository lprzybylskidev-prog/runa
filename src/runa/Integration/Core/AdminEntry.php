<?php

namespace Runa\Integration\Core;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\OperationContext;
use Runa\ModuleFramework\Api\OperationGuard;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\ModuleFramework\Data\AuthorityLock;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

/** Named trusted Admin integration. Never binds or accepts an owning business operation. */
final readonly class AdminEntry
{
    public function __construct(private DatabaseManager $database, private AuthorityLock $authority,
        private OperationGuard $guard, private PrincipalScope $principals, private ModuleApis $apis) {}

    /** Minimal proof-free advertisement. Never returns authority or protected values. */
    public function available(): bool
    {
        if ($this->principals->current() === null) {
            throw new OperationRejected('authentication_required', 'runa.admin', 'entry', 401);
        }
        try {
            $context = $this->guard->enter('runa.admin', new ApiOperation('entry', 'runa.admin.enter'));
            $this->guard->assertAvailable($context);

            return true;
        } catch (OperationRejected $error) {
            if ($error->reason === 'platform_unavailable') {
                throw $error;
            }

            return false;
        }
    }

    /** @return array{remaining_seconds: float, heartbeat_seconds: float} */
    public function activity(bool $renew): array
    {
        return $this->read(fn (): array => $this->apis->resolve('runa.auth', AuthStepUp::class, '2.0.0')
            ->activity('runa.admin.enter', 300, $renew));
    }

    /**
     * @template T
     *
     * @param  Closure(OperationContext): T  $read
     * @return T
     */
    public function read(Closure $read): mixed
    {
        return $this->run($read, false);
    }

    /** Trusted synchronous configuration may deliberately publish a registry generation.
     *
     * @template T
     *
     * @param  Closure(OperationContext):T  $write
     * @return T
     */
    public function write(Closure $write): mixed
    {
        return $this->run($write, true);
    }

    /**
     * @template T
     *
     * @param Closure(OperationContext):T $read
     * @return T */
    private function run(Closure $read, bool $publishes): mixed
    {
        if ($this->principals->current() === null) {
            throw new OperationRejected('authentication_required', 'runa.admin', 'enter', 401);
        }
        // Applicable on-use health checks run before acquiring the entry transaction.
        $this->guard->enter('runa.admin', new ApiOperation('enter', 'runa.admin.enter'));

        return $this->database->connection()->transaction(function (Connection $connection) use ($read, $publishes): mixed {
            $connection->table('shared.module_registry_state')->where('id', 1)->lockForUpdate()->first()
                ?? throw new OperationRejected('module_unavailable', 'runa.admin', 'enter');
            $this->authority->acquire($connection);
            $context = $this->guard->enter('runa.admin', new ApiOperation('enter', 'runa.admin.enter'));
            $this->apis->resolve('runa.auth', AuthStepUp::class, '2.0.0')
                ->assertActive('runa.admin.enter', 300);
            $result = $read($context);
            if ($publishes) {
                // Re-admit the resulting registry while authority/session locks remain held.
                $current = $this->guard->enter('runa.admin', new ApiOperation('enter', 'runa.admin.enter'));
                $this->guard->assertCurrent($current);
            } else {
                // A long read cannot return protected values after entry proof expires.
                $this->apis->resolve('runa.auth', AuthStepUp::class, '2.0.0')
                    ->assertActive('runa.admin.enter', 300);
                $this->guard->assertCurrent($context);
            }

            return $result;
        });
    }
}
