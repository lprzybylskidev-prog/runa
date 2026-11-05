<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Runa\Modules\Auth\Actions\SessionStore;
use Runa\Modules\Auth\Support\CredentialPolicy;

final readonly class SecurityStatusController
{
    public function __construct(private CredentialPolicy $policy, private SessionStore $sessions) {}

    public function __invoke(Request $request): JsonResponse
    {
        return DB::connection()->transaction(fn (): JsonResponse => $this->response($request));
    }

    private function response(Request $request): JsonResponse
    {
        $user = Auth::guard('web')->user() ?? throw new AuthenticationException;

        $session = $this->sessions->current($request, $user);
        $method = $session?->factor_method;
        $trustEligible = $method !== null && $this->policy->accepts($method) && in_array($method === 'recovery' ? 'totp' : $method, $this->policy->enrolled($user), true);

        return response()->json(['data' => ['required' => $this->policy->requiredMethod(),
            'email_verified' => $user->email_verified_at !== null,
            'enrolled' => $this->policy->enrolled($user),
            'totp_enabled' => $this->policy->value('totp_enabled'), 'email_otp_enabled' => $this->policy->value('email_otp_enabled'),
            'recovery_codes_enabled' => $this->policy->value('recovery_codes_enabled'),
            'trusted_device_eligible' => $trustEligible, 'trusted_devices_enabled' => $this->policy->value('trusted_devices_enabled')]], headers: ['Cache-Control' => 'no-store']);
    }
}
