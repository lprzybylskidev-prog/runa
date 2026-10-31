<?php

namespace Runa\Modules\Auth\Support;

use Illuminate\Database\DatabaseManager;
use Runa\Contracts\Modules\MutationProof;
use Runa\Contracts\Modules\TransactionSecurity as SecurityContract;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Contracts\AuthStepUp;
use Runa\Modules\Auth\Contracts\IdentityConflict;
use Runa\Modules\Auth\Models\User;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class TransactionSecurity implements SecurityContract
{
    public function __construct(private DatabaseManager $database, private ModuleApis $apis) {}

    public function lockSubjects(array $publicIds): void
    {
        if ($this->database->connection()->transactionLevel() < 1 || ! array_is_list($publicIds) || count($publicIds) > 100) {
            throw new \LogicException('Subject locks require a bounded owning transaction.');
        }
        $sorted = $publicIds;
        sort($sorted);
        if ($sorted !== $publicIds || count(array_unique($sorted)) !== count($sorted)) {
            throw new \LogicException('Subject locks require canonical order.');
        }
        foreach ($publicIds as $id) {
            new TrustedPrincipal($id);
            User::where('public_id', $id)->lockForUpdate()->first() ?? throw new IdentityConflict('identity_missing');
        }
    }

    public function proof(MutationProof $proof, bool $consume): void
    {
        $api = $this->apis->resolve('runa.auth', AuthStepUp::class, '1.1.0');
        if ($proof->idle) {
            $api->assertActive($proof->action, $proof->requirement, $proof->ttlSeconds);

            return;
        }
        $api->assertVerified($proof->action, $proof->requirement, $proof->ttlSeconds, $consume && $proof->everyAction);
    }
}
