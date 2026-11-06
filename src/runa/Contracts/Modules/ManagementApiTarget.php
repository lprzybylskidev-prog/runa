<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;
use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class ManagementApiTarget
{
    /** @param list<string> $methods */
    public function __construct(
        public string $module, public string $contract, public string $version, public array $methods,
    ) {
        if (preg_match('/\A(?:runa|app)\.[a-z][a-z0-9_]{0,47}\z/D', $module) !== 1 || preg_match('/\A(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/D', $version) !== 1) {
            throw new \InvalidArgumentException('Invalid management API target.');
        }
        Rules::className($contract);
        Values::strings($methods);
        if ($methods === [] || count($methods) > 128 || count(array_unique($methods)) !== count($methods)) {
            throw new \InvalidArgumentException('Invalid target methods.');
        }
        foreach ($methods as $method) {
            if (preg_match('/\A[a-zA-Z][a-zA-Z0-9_]{0,127}\z/D', $method) !== 1) {
                throw new \InvalidArgumentException('Invalid target method.');
            }
        }
    }
}
