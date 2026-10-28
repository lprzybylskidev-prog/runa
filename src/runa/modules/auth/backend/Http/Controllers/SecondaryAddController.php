<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\EmailAddresses;
use Runa\Modules\Auth\Http\Requests\SecondaryRequest;
use Runa\Modules\Auth\Http\Resources\ProfileResource;

final readonly class SecondaryAddController
{
    public function __construct(private EmailAddresses $action) {}

    public function __invoke(SecondaryRequest $request): JsonResponse
    {
        $data = $this->action->ownSecondary($request, 'add');

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
