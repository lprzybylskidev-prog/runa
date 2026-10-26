<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\EmailAddresses;
use Runa\Modules\Auth\Http\Requests\SecondaryRequest;

final readonly class SecondaryRemoveController
{
    public function __construct(private EmailAddresses $action) {}

    public function __invoke(SecondaryRequest $request): JsonResponse
    {
        $data = $this->action->ownSecondary($request, 'remove');

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
