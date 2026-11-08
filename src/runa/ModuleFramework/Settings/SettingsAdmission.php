<?php

namespace Runa\ModuleFramework\Settings;

use PDO;
use Runa\Contracts\Modules\SettingsAccess;
use Runa\Contracts\Modules\SettingsActor;
use Runa\Contracts\Modules\TrustedPrincipal;

/** Internal, synchronous publication scope; never persisted or accepted from callers. */
final readonly class SettingsAdmission
{
    /** @param list<string> $keys */
    public function __construct(
        public string $module, public SettingsAccess $access, public array $keys,
        public ?TrustedPrincipal $principal, public ?SettingsActor $actor,
        public PDO $pdo, public int $generation,
    ) {}
}
