<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;
use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class ManagementRead
{
    /**
     * @param array<string, scalar|null> $filters
     * @param list<array{key:string,direction:string}> $sort */
    public function __construct(
        public ?string $publicId = null, public ?string $cursor = null,
        public int $limit = 25, public array $filters = [], public array $sort = [],
    ) {
        Rules::publicId($publicId);
        if ($limit < 1 || $limit > 100 || ($cursor !== null && strlen($cursor) > 2048) || count($filters) > 64 || count($sort) > 4) {
            throw new \InvalidArgumentException('Management query limit.');
        }
        Rules::json($filters);
        Values::filters($filters);
        Values::sort($sort);
    }
}
