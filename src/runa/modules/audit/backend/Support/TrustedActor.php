<?php

namespace Runa\Modules\Audit\Support;

use Runa\Logging\QueueLogContext;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Modules\Audit\Contracts\AuditActor;
use Runa\Modules\Audit\Contracts\AuditActorResolver;
use Runa\Support\Logging\LogContext;

final readonly class TrustedActor implements AuditActorResolver
{
    public function __construct(private PrincipalScope $principals, private LogContext $logs, private QueueLogContext $queue) {}

    public function resolve(): AuditActor
    {
        $principal = $this->principals->current();
        if ($principal !== null && $this->queue->current() === []) {
            return new AuditActor($principal->kind, $principal->publicId);
        }
        $context = $this->logs->current();
        $kind = $this->queue->current() !== [] ? 'queue' : (isset($context['request_id']) ? 'anonymous' : (str_starts_with($context['command'] ?? '', 'schedule:') ? 'scheduler' : (isset($context['command']) ? 'cli' : 'system')));

        return new AuditActor($kind, label: $kind);
    }
}
