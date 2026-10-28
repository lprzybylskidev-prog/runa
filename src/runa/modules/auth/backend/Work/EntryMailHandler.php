<?php

namespace Runa\Modules\Auth\Work;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\DeliverEntryMail;
use Runa\Support\Modules\Work\WorkContext;

final readonly class EntryMailHandler implements WorkHandler
{
    public function __construct(private DeliverEntryMail $action) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if (array_keys($payload) !== ['token_id'] || ! is_string($payload['token_id'])) {
            throw new \InvalidArgumentException('Invalid Auth delivery reference.');
        }
        $this->action->deliver($payload['token_id']);
    }
}
