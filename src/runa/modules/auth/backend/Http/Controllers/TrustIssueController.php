<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\TrustedDevices;
use Runa\Modules\Auth\Http\Requests\SecurityRequest;

final readonly class TrustIssueController
{
    public function __construct(private TrustedDevices $action) {}

    public function __invoke(SecurityRequest $request): JsonResponse
    {
        $data = $this->action->issue($request);

        return response()->json(['data' => $data], 200, ['Cache-Control' => 'no-store']);
    }
}
