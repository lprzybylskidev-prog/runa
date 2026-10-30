<?php

namespace Runa\Modules\Admin;

use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Support\Modules\Api\GuardedApi;

final class AccessProxy extends GuardedApi implements AdminAccess
{
    public function bootstrap(): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->invoke(__FUNCTION__, []);

        return $result;
    }

    public function page(string $owner, string $page): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$owner, $page]);

        return $result;
    }

    public function read(string $owner, string $page, string $section, array $query): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$owner, $page, $section, $query]);

        return $result;
    }

    public function preview(string $owner, string $page, array $input): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$owner, $page, $input]);

        return $result;
    }

    public function save(string $owner, string $page, array $input): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$owner, $page, $input]);

        return $result;
    }

    public function command(string $owner, string $page, string $section, array $input): array
    {
        /** @var array<string, mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$owner, $page, $section, $input]);

        return $result;
    }
}
