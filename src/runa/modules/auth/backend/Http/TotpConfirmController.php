<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\CredentialFactors;
use Runa\Modules\Auth\Http\Requests\SecurityRequest;

final readonly class TotpConfirmController
{
    public function __construct(private CredentialFactors $factors) {}

    public function __invoke(SecurityRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->factors->handle($request, 'totp_confirm')], headers: ['Cache-Control' => 'no-store']);
    }
}
