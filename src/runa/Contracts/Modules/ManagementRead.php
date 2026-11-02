<?php

namespace Runa\Contracts\Modules;

use Runa\ModuleFramework\Management\DeclarationRules as Rules;

final readonly class ManagementRead
{
    /** @param array<string, scalar|null> $filters
     * @param list<array{key:string,direction:string}> $sort */
    public function __construct(
        public ?string $publicId = null, public ?string $cursor = null,
        public int $limit = 25, public array $filters = [], public array $sort = [],
    ) {
        Rules::publicId($publicId);
        if ($limit < 1 || $limit > 100 || ($cursor !== null && strlen($cursor) > 2048) || count($filters) > 64 || ! array_is_list($sort) || count($sort) > 4) {
            throw new \InvalidArgumentException('Management query limit.');
        }
        Rules::json($filters);
        foreach ($filters as $value) {
            if (! is_scalar($value) && $value !== null) {
                throw new \InvalidArgumentException('Invalid management filter.');
            }
        }
        $seen = [];
        foreach ($sort as $item) {
            if (! is_array($item) || count($item) !== 2 || ! isset($item['key'], $item['direction']) || ! is_string($item['key']) || ! in_array($item['direction'], ['asc', 'desc'], true) || isset($seen[$item['key']])) {
                throw new \InvalidArgumentException('Invalid management sort.');
            }
            Rules::key($item['key']);
            $seen[$item['key']] = true;
        }
    }
}
