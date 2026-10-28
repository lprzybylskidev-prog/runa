<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\ProfileRequest;
use Runa\Modules\Auth\Http\Resources\ProfileResource;

final readonly class ProfileEditController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(ProfileRequest $request): JsonResponse
    {
        $data = $this->action->edit($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
