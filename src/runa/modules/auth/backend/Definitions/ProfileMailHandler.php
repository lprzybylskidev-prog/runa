<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\WorkHandler;
use Runa\Modules\Auth\Actions\ProfileMail;
use Runa\Support\Modules\Work\WorkContext;

final readonly class ProfileMailHandler implements WorkHandler
{
    public function __construct(private ProfileMail $action) {}

    public function handle(WorkContext $context, array $payload): void
    {
        if (array_keys($payload) !== ['mail_id'] || ! is_string($payload['mail_id'])) {
            throw new \InvalidArgumentException('Invalid profile delivery reference.');
        }
        $this->action->deliver($payload['mail_id']);
    }
}
