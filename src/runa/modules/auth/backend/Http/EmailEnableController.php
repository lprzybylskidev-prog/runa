<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\CredentialFactors;
use Runa\Modules\Auth\Http\Requests\SecurityRequest;

final readonly class EmailEnableController
{
    public function __construct(private CredentialFactors $factors) {}

    public function __invoke(SecurityRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->factors->handle($request, 'email_enable')], headers: ['Cache-Control' => 'no-store']);
    }
}
