<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Resources\ProfileResource;

final readonly class ProfileShowController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->action->show($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
