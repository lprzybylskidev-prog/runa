<?php

namespace Runa\Modules\Admin\Queries;

use Runa\Modules\Admin\Contracts\AdminOperations;

final readonly class Operations implements AdminOperations
{
    public function __construct(private \Runa\Integration\Core\AdminOperations $operations) {}

    public function read(string $view, array $input = []): array
    {
        return $this->operations->read($view, $input);
    }

    public function execute(string $command, array $input): array
    {
        return $this->operations->execute($command, $input);
    }
}
