<?php

namespace Runa\Integration\Core;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\ModuleFramework\Api\PrincipalScope;
use Runa\Support\Modules\Api\OperationRejected;
use Symfony\Component\HttpFoundation\Response;

/** Fixed platform route adapter; uses Auth's existing session and eligibility policy. */
final readonly class AdminHttpGuard
{
    public function __construct(private HttpAccountPolicy $accounts, private PrincipalScope $principals, private AdminEntry $entry) {}

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $this->admit($request, $next);
        } catch (OperationRejected $error) {
            return response()->json(['message' => 'Administration request refused.', 'reason' => $error->reason], $error->status, ['Cache-Control' => 'no-store, private']);
        } catch (ValidationException $error) {
            return response()->json(['message' => 'Invalid administration input.', 'reason' => 'validation_failed',
                'errors' => array_fill_keys(array_keys($error->errors()), ['Invalid input.'])], 422, ['Cache-Control' => 'no-store, private']);
        } catch (\Throwable $error) {
            report($error);

            return response()->json(['message' => 'Administration is unavailable.', 'reason' => 'admin_unavailable'], 503, ['Cache-Control' => 'no-store, private']);
        }
    }

    /** @param Closure(Request): Response $next */
    private function admit(Request $request, Closure $next): Response
    {
        $principal = $this->principals->resolve(fn () => $this->accounts->principal($request, 'runa.admin', 'enter', HttpAccess::Authenticated));
        if ($principal === null) {
            throw new OperationRejected('authentication_required', 'runa.admin', 'enter', 401);
        }

        return $this->principals->run($principal, function () use ($request, $next): Response {
            // Admit before controller construction or Form Request validation. Do not retain
            // a transaction around independent owner workflows executed by later commands.
            if ($request->routeIs('runa.admin.entry')) {
                if (! $this->entry->available()) {
                    return response()->json(['data' => ['available' => false]], 200, ['Cache-Control' => 'no-store, private']);
                }
            } else {
                $this->entry->read(static fn () => null);
            }

            $response = $next($request);
            $response->headers->set('Cache-Control', 'no-store, private');

            return $response;
        });
    }
}
