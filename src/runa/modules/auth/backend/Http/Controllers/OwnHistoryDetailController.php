<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\HistoryDetailRequest;
use Runa\Modules\Auth\Http\Resources\ProfileResource;

final readonly class OwnHistoryDetailController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(HistoryDetailRequest $request): JsonResponse
    {
        return (new ProfileResource($this->action->historyDetail($request)))->response()->header('Cache-Control', 'no-store');
    }
}
