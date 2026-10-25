<?php

namespace Runa\Http;

use Illuminate\Http\Request;
use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpAccountPolicy;
use Runa\Contracts\Modules\TrustedPrincipal;
use Runa\Modules\Auth\Support\AccountPolicy;
use Runa\Support\Modules\Api\OperationGuard;
use Runa\Support\Modules\Api\OperationRejected;
use Runa\Support\Modules\ModuleRegistry;

/** Lazy boundary keeps cold setup and independent health endpoints bootable. */
final readonly class AuthAccountBridge implements HttpAccountPolicy
{
    public function __construct(private ModuleRegistry $registry) {}

    public function principal(Request $request, ?string $module, string $operation, HttpAccess $access): ?TrustedPrincipal
    {
        if ($module !== 'runa.auth' && $access === HttpAccess::Public
            && (! $request->hasSession() || ! $request->session()->has(app('auth')->guard('web')->getName()))) {
            return null;
        }
        $record = $this->registry->snapshot()->modules['runa.auth'] ?? null;
        if ($record === null || ! $record->registered) {
            if ($access === HttpAccess::Public && ! $request->hasCookie(config()->string('session.cookie'))) {
                return null;
            }
            throw new OperationRejected('authentication_required', $module ?? 'application', $operation, 401);
        }
        // Module HTTP already admitted Auth; ordinary APIs must independently fence it.
        if ($module !== 'runa.auth') {
            app(OperationGuard::class)->enter('runa.auth', new ApiOperation('session.ingress'));
        }

        return app(AccountPolicy::class)->principal($request, $module, $operation, $access);
    }
}
