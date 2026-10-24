<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\ScheduledLifecycle;
use Runa\Support\Modules\Work\WorkContext;

final readonly class ScheduledLifecycleHandler implements WorkHandler
{
    public function __construct(private ScheduledLifecycle $lifecycle) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if ($payload !== []) {
            throw new \InvalidArgumentException('Scheduled Auth lifecycle accepts no payload.');
        }
        $this->lifecycle->run();
    }
}
