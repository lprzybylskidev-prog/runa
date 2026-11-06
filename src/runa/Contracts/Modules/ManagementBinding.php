<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementBinding
{
    /** @param class-string<ManagementHandler> $handler
     * @param list<ManagementApiTarget> $targets
     * @param class-string|null $participant */
    public function __construct(
        public string $key, public string $handler, public array $targets, public ?string $participant = null,
    ) {
        Rules::key($key);
        Rules::className($handler);
        if ($participant !== null) {
            Rules::className($participant);
        }
        Rules::collection($targets, ManagementApiTarget::class, 32, 'contract');
    }
}
