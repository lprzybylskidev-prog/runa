<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Runa\Modules\Auth\Actions\Profiles;

final readonly class ProfileShowController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $this->action->show($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
