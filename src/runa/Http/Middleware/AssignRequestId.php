<?php

namespace Runa\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AssignRequestId
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        $candidate = $request->header('X-Request-ID');
        $id = is_string($candidate) && preg_match('/\A[a-zA-Z0-9_-]{1,64}\z/D', $candidate)
            ? $candidate : (string) Str::uuid7();
        $request->attributes->set('request_id', $id);
        Log::withContext(['request_id' => $id]);
        $response = $next($request);
        $response->headers->set('X-Request-ID', $id);

        return $response;
    }
}
