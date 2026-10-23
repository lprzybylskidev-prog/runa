<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\SecurityNotices;
use Runa\Support\Modules\Work\WorkContext;

final readonly class SecurityReminderHandler implements WorkHandler
{
    public function __construct(private SecurityNotices $notices) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if ($payload !== []) {
            throw new \InvalidArgumentException('Invalid security reminder payload.');
        }
        $this->notices->reminders();
    }
}
