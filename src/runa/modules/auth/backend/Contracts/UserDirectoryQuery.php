<?php

namespace Runa\Modules\Auth\Contracts;

final readonly class UserDirectoryQuery
{
    public function __construct(public ?string $after = null, public int $limit = 25, public ?string $search = null, public string $state = 'current')
    {
        if ($limit < 1 || $limit > 100 || ($after !== null && strlen($after) > 2048)
            || ! in_array($state, ['current', 'deleted', 'all'], true)
            || ($search !== null && ($search === '' || trim($search) !== $search || mb_strlen($search) > 160 || preg_match('/[\p{C}]/u', $search)))) {
            throw new \InvalidArgumentException('Invalid user directory query.');
        }
    }
}
