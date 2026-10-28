<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Runa\Runtime\MaintenanceState;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnforceEffectiveMaintenance
{
    public function __construct(private MaintenanceState $maintenance) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/health', 'api/ready')) {
            $this->maintenance->assertOperational();
        }

        return $next($request);
    }
}
