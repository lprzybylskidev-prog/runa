<?php

namespace Runa\Modules\Auth\Work;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\EntryTokens;
use Runa\Support\Modules\Work\WorkContext;

final readonly class EntryReminderHandler implements WorkHandler
{
    public function __construct(private EntryTokens $action) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if ($payload !== []) {
            throw new \InvalidArgumentException('Auth reminders accept no payload.');
        }
        $this->action->remind();
    }
}
