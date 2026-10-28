<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\HistoryRequest;
use Runa\Modules\Auth\Http\Resources\ProfileResource;

final readonly class OwnHistoryController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(HistoryRequest $request): JsonResponse
    {
        $data = $this->action->history($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
