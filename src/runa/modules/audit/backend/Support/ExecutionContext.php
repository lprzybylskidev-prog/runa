<?php

namespace Runa\Modules\Audit\Support;

use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Runa\Logging\QueueLogContext;
use Runa\ModuleFramework\Api\OperationScope;
use Runa\Support\Logging\LogContext;

final readonly class ExecutionContext
{
    public function __construct(private LogContext $logs, private OperationScope $operations, private Container $container, private QueueLogContext $queue) {}

    /** @return array<string, mixed> */
    public function capture(): array
    {
        $log = $this->logs->current();
        $operation = $this->operations->current();
        $context = array_intersect_key($log, array_flip(['request_id', 'correlation_id', 'operation_id', 'command', 'work_id']));
        if ($operation !== null) {
            $context['correlation_id'] = $operation->correlationId;
            $context['operation_id'] = $operation->operationId;
            $context['origin_module'] = $operation->originModule ?? $operation->moduleId;
        }
        $context = array_replace($context, $this->operations->diagnostics(), $this->queue->current());
        if ($this->queue->current() !== []) {
            unset($context['request_id'], $context['command']);
        }
        // No stale console-bound request or request bodies/headers/query parameters.
        if (isset($context['request_id']) && $this->container->bound('request')) {
            $request = $this->container->make('request');
            if ($request instanceof Request) {
                $ip = $request->ip();
                $context['ip'] = is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
                $context['method'] = substr($request->method(), 0, 16);
                $context['path'] = substr($request->route()?->uri() ?? '[unrouted]', 0, 512);
                // Arbitrary client User-Agent text may carry credentials: retain only known family.
                $agent = $request->userAgent() ?? '';
                preg_match('/\b(Firefox|Chrome|Safari|Edg|curl)\/[0-9.]{1,24}\b/', $agent, $family);
                $context['user_agent'] = $family[0] ?? '[unrecognized]';
            }
        }

        return SafePayload::clean($context);
    }
}
