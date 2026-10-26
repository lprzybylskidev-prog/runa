<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\Profiles;
use Runa\Modules\Auth\Http\Requests\HistoryRequest;

final readonly class OwnHistoryController
{
    public function __construct(private Profiles $action) {}

    public function __invoke(HistoryRequest $request): JsonResponse
    {
        $data = $this->action->history($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
