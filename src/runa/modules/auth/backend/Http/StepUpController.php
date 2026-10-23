<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\StepUp;
use Runa\Modules\Auth\Http\Requests\SecurityRequest;

final readonly class StepUpController
{
    public function __construct(private StepUp $action) {}

    public function __invoke(SecurityRequest $request): JsonResponse
    {
        $data = $this->action->confirm($request);

        return response()->json(['data' => $data], 200, ['Cache-Control' => 'no-store']);
    }
}
