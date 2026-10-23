<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;
use Runa\Modules\Auth\Http;

final class EntryHttp implements HttpProvider
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function endpoints(): array
    {
        return [
            new HttpEndpoint('GET', 'sessions', new ApiOperation('sessions.list'), Http\SessionsController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'sessions/revoke', new ApiOperation('sessions.revoke'), Http\RevokeSessionController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'session/activity', new ApiOperation('session.activity'), Http\ActivityController::class, HttpAccess::Restricted),
            new HttpEndpoint('GET', 'csrf', new ApiOperation('csrf'), Http\CsrfController::class),
            new HttpEndpoint('POST', 'login', new ApiOperation('login'), Http\LoginController::class),
            new HttpEndpoint('POST', 'logout', new ApiOperation('logout'), Http\LogoutController::class),
            new HttpEndpoint('GET', 'session', new ApiOperation('session'), Http\SessionController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'register', new ApiOperation('register'), Http\RegisterController::class),
            new HttpEndpoint('POST', 'invitations/accept', new ApiOperation('invitation.accept'), Http\AcceptInvitationController::class),
            new HttpEndpoint('POST', 'verification/complete', new ApiOperation('verification.complete'), Http\CompleteVerificationController::class),
            new HttpEndpoint('POST', 'verification/resend', new ApiOperation('verification.resend'), Http\ResendVerificationController::class, HttpAccess::Restricted),
        ];
    }
}
