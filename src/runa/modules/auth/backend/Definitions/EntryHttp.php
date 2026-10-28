<?php

namespace Runa\Modules\Auth\Definitions;

use Runa\Contracts\Modules\ApiOperation;
use Runa\Contracts\Modules\HttpAccess;
use Runa\Contracts\Modules\HttpEndpoint;
use Runa\Contracts\Modules\HttpProvider;
use Runa\Modules\Auth\Http\Controllers as Http;

final class EntryHttp implements HttpProvider
{
    public function schemaVersion(): int
    {
        return 1;
    }

    public function endpoints(): array
    {
        return [
            new HttpEndpoint('GET', 'entry-options', new ApiOperation('entry.options'), Http\EntryOptionsController::class),
            new HttpEndpoint('POST', 'security/challenge', new ApiOperation('security.challenge'), Http\ChallengeController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'security/email-send', new ApiOperation('security.email_send'), Http\EmailSendController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'security/totp-begin', new ApiOperation('security.totp_begin'), Http\TotpBeginController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'security/totp-confirm', new ApiOperation('security.totp_confirm'), Http\TotpConfirmController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'security/email-enable', new ApiOperation('security.email_enable'), Http\EmailEnableController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'security/disable', new ApiOperation('security.disable'), Http\DisableController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'security/recovery-rotate', new ApiOperation('security.recovery_rotate'), Http\RecoveryRotateController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'password/change', new ApiOperation('password.change'), Http\PasswordChangeController::class, HttpAccess::Restricted),
            new HttpEndpoint('POST', 'password/reset', new ApiOperation('password.reset'), Http\PasswordResetController::class, HttpAccess::Public),
            new HttpEndpoint('POST', 'password/forgot', new ApiOperation('password.forgot'), Http\PasswordForgotController::class, HttpAccess::Public),
            new HttpEndpoint('POST', 'step-up', new ApiOperation('step_up.confirm'), Http\StepUpController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'trusted-devices', new ApiOperation('trust.issue'), Http\TrustIssueController::class, HttpAccess::Authenticated),
            new HttpEndpoint('GET', 'security', new ApiOperation('security.status'), Http\SecurityStatusController::class, HttpAccess::Restricted),
            new HttpEndpoint('GET', 'trusted-devices', new ApiOperation('trust.list'), Http\TrustListController::class, HttpAccess::Authenticated),
            new HttpEndpoint('POST', 'trusted-devices/revoke', new ApiOperation('trust.revoke'), Http\TrustRevokeController::class, HttpAccess::Authenticated),
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
