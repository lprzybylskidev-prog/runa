<?php

namespace Runa\Integration\Core;

use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\TransactionSecurity as SecurityContract;
use Runa\ModuleFramework\Api\ModuleDeclarations;
use Runa\ModuleFramework\ModuleRegistry;
use Runa\Support\Modules\Api\OperationRejected;

/** Named lazy integration; cold boot does not resolve any module class. */
final readonly class TransactionSecurity implements SecurityContract
{
    public function __construct(private ModuleRegistry $registry, private ModuleDeclarations $declarations) {}

    private function owning(): SecurityContract
    {
        $record = $this->registry->snapshot()->modules['runa.auth'] ?? throw new OperationRejected('module_unavailable', 'runa.auth', 'transaction');
        $instance = $this->declarations->make($record, 'Runa\\Modules\\Auth\\Support\\TransactionSecurity', SecurityContract::class);

        return $instance instanceof SecurityContract ? $instance : throw new OperationRejected('module_unavailable', 'runa.auth', 'transaction');
    }

    public function lockSubjects(array $publicIds): void
    {
        if ($publicIds !== []) {
            $this->owning()->lockSubjects($publicIds);
        }
    }

    public function proof(MutationProof $proof, bool $consume): void
    {
        $this->owning()->proof($proof, $consume);
    }
}
