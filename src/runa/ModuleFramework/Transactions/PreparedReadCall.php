<?php

namespace Runa\ModuleFramework\Transactions;

use PDO;

/** Internal in-process health evidence for one invocation occurrence. */
final class PreparedReadCall
{
    public bool $claimed = false;

    /** @param array{string,string,string,string} $identity
     * @param array<string,array{source:string,policy:string,expiry:int}> $evidence */
    public function __construct(public readonly array $identity, public readonly array $evidence, public readonly PDO $pdo, public readonly ?string $definitionFingerprint = null) {}
}
