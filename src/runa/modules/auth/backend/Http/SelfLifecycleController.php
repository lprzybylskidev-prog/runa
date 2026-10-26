<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\SelfLifecycleRequest;

final readonly class SelfLifecycleController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(SelfLifecycleRequest $request): JsonResponse
    {
        $data = $this->action->selfLifecycle($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
