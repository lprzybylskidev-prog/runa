<?php

namespace Runa\Contracts\Modules;

/** Reserved framework/Core integration. Never an ordinary App identity/proof resolver. */
interface TransactionSecurity
{
    /** @param list<string> $publicIds */
    public function lockSubjects(array $publicIds): void;

    public function proof(MutationProof $proof, bool $consume): void;
}
