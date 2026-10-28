<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\SelfLifecycleRequest;
use Runa\Modules\Auth\Http\Resources\ProfileResource;

final readonly class SelfLifecycleController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(SelfLifecycleRequest $request): JsonResponse
    {
        $data = $this->action->selfLifecycle($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
