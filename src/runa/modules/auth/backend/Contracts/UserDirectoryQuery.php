<?php

namespace Runa\Modules\Auth\Contracts;

use Runa\ModuleFramework\Management\InputValues as Values;

final readonly class UserDirectoryQuery
{
    /** @param array<string,bool|int|string|null> $filters */
    public function __construct(public ?string $after = null, public int $limit = 25, public ?string $search = null, public string $state = 'current', public array $filters = [], public ?string $sort = null, public string $direction = 'asc')
    {
        $checked = Values::map($filters);
        if (! in_array($direction, ['asc', 'desc'], true) || ($sort !== null && ! preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $sort)) || count($filters) > 64 || $limit < 1 || $limit > 100 || ($after !== null && strlen($after) > 2048)
            || ! in_array($state, ['current', 'deleted', 'all'], true)
            || ($search !== null && ($search === '' || trim($search) !== $search || mb_strlen($search) > 160 || preg_match('/[\p{C}]/u', $search)))) {
            throw new \InvalidArgumentException('Invalid user directory query.');
        }
        foreach ($checked as $key => $value) {
            if (! preg_match('/\A[a-z][a-z0-9_]{0,63}\z/D', $key) || (! is_null($value) && ! is_bool($value) && ! is_int($value) && ! is_string($value)) || (is_string($value) && (strlen($value) > 2048 || preg_match('/[\p{C}]/u', $value)))) {
                throw new \InvalidArgumentException('Invalid directory filter.');
            }
        }
    }
}
