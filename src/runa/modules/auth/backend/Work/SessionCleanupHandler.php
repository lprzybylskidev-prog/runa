<?php

namespace Runa\Modules\Auth\Work;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\CleanupSessions;
use Runa\Support\Modules\Work\WorkContext;

final readonly class SessionCleanupHandler implements WorkHandler
{
    public function __construct(private CleanupSessions $cleanup) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if ($payload !== []) {
            throw new \InvalidArgumentException('Auth session cleanup accepts no payload.');
        }
        $this->cleanup->run();
    }
}
