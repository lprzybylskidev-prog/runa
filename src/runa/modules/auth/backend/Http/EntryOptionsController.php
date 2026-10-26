<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Support\CredentialPolicy;

/** Public form constraints only; never project the encrypted settings document. */
final readonly class EntryOptionsController
{
    public function __construct(private CredentialPolicy $policy) {}

    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => [
            'public_welcome_enabled' => $this->policy->value('public_welcome_enabled'),
            'public_registration' => $this->policy->value('public_registration'),
            'identifier_mode' => $this->policy->value('identifier_mode'),
            'remember_enabled' => $this->policy->value('session_remember_minutes') > 0,
            'password' => [
                'min_length' => $this->policy->value('password_min_length'),
                'max_bytes' => 72,
                'mixed_case' => $this->policy->value('password_mixed_case'),
                'numbers' => $this->policy->value('password_numbers'),
                'symbols' => $this->policy->value('password_symbols'),
            ],
        ]], headers: ['Cache-Control' => 'no-store']);
    }
}
