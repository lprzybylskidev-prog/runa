<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Avatars;
use Runa\Modules\Auth\Http\Requests\AvatarRequest;

final readonly class AdminAvatarRemoveController
{
    public function __construct(private Avatars $action) {}

    public function __invoke(AvatarRequest $request): JsonResponse
    {
        $data = $this->action->remove($request, true);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
