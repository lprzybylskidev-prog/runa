<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Runa\Support\Logging\LogContext;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    public function __construct(private readonly LogContext $context) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $candidate = $request->header('X-Request-ID');
        $id = is_string($candidate) && preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $candidate)
            ? $candidate : (string) Str::uuid7();
        $request->attributes->set('request_id', $id);
        $applicationId = config('branding.application_id');
        $response = $this->context->run(['origin' => is_string($applicationId) ? 'application:'.$applicationId : 'platform', 'request_id' => $id, 'correlation_id' => $id], fn (): Response => $next($request));
        $response->headers->set('X-Request-ID', $id);

        return $response;
    }
}
