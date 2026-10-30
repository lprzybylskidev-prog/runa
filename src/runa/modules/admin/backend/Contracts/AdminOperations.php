<?php

namespace Runa\Modules\Admin\Contracts;

/** Guarded fixed operational catalogue; never a generic engine dispatcher. */
interface AdminOperations
{
    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function read(string $view, array $input = []): array;

    /** @param array<string,mixed> $input @return array<string,mixed> */
    public function execute(string $command, array $input): array;
}
