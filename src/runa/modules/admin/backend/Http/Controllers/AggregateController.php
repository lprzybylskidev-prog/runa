<?php

namespace Runa\Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Http\Requests\AggregateRequest;
use Runa\Modules\Admin\Http\Resources\AdminResource;
use Runa\Support\Modules\Api\ModuleApis;

/** Transport only; native multi-owner execution is supplied by roadmap point 6. */
final readonly class AggregateController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(AggregateRequest $request): JsonResponse
    {
        $access = $this->apis->resolve('runa.admin', AdminAccess::class, '1.0.0');
        $method = $request->routeIs('runa.admin.preview') ? 'preview' : 'save';
        $data = $access->$method((string) $request->route('owner'), (string) $request->route('page'), $request->all());

        return (new AdminResource($data))->response()->header('Cache-Control', 'no-store, private');
    }
}
