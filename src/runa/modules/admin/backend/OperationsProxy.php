<?php

namespace Runa\Modules\Admin;

use Runa\Modules\Admin\Contracts\AdminOperations;
use Runa\Support\Modules\Api\GuardedApi;

final class OperationsProxy extends GuardedApi implements AdminOperations
{
    public function read(string $view, array $input = []): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$view, $input]);

        return $result;
    }

    public function execute(string $command, array $input): array
    {
        /** @var array<string,mixed> $result */
        $result = $this->invoke(__FUNCTION__, [$command, $input]);

        return $result;
    }
}
