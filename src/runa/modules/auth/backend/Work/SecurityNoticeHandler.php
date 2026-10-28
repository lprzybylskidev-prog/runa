<?php

namespace Runa\Modules\Auth\Work;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\SecurityNotices;
use Runa\Support\Modules\Work\WorkContext;

final readonly class SecurityNoticeHandler implements WorkHandler
{
    public function __construct(private SecurityNotices $notices) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if (array_keys($payload) !== ['notice_id'] || ! is_string($payload['notice_id'])) {
            throw new \InvalidArgumentException('Invalid security notice reference.');
        }
        $this->notices->deliver($payload['notice_id']);
    }
}
