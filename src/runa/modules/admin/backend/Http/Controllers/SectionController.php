<?php

namespace Runa\Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Http\Requests\SectionReadRequest;
use Runa\Modules\Admin\Http\Resources\AdminResource;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class SectionController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(SectionReadRequest $request): JsonResponse
    {
        $data = $this->apis->resolve('runa.admin', AdminAccess::class, '1.0.0')->read(
            (string) $request->route('owner'), (string) $request->route('page'),
            (string) $request->route('section'), $request->readQuery(),
        );

        return (new AdminResource($data))->response()->header('Cache-Control', 'no-store, private');
    }
}
