<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Runa\Modules\Auth\Support\CredentialPolicy;

final readonly class SecurityStatusController
{
    public function __construct(private CredentialPolicy $policy) {}

    public function __invoke(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user() ?? throw new AuthenticationException;

        return response()->json(['data' => ['required' => $this->policy->requiredMethod(),
            'email_verified' => $user->email_verified_at !== null,
            'enrolled' => $this->policy->enrolled($user),
            'totp_enabled' => $this->policy->value('totp_enabled'), 'email_otp_enabled' => $this->policy->value('email_otp_enabled'),
            'recovery_codes_enabled' => $this->policy->value('recovery_codes_enabled'),
            'password_change_factor' => $this->policy->value('password_change_factor'),
            'trusted_devices_enabled' => $this->policy->value('trusted_devices_enabled')]], headers: ['Cache-Control' => 'no-store']);
    }
}
