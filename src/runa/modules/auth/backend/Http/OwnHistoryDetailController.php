<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\HistoryDetailRequest;

final readonly class OwnHistoryDetailController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(HistoryDetailRequest $request): JsonResponse
    {
        return (new ProfileResource($this->action->historyDetail($request)))->response()->header('Cache-Control', 'no-store');
    }
}
