<?php

namespace Runa\Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Http\Requests\BootstrapRequest;
use Runa\Modules\Admin\Http\Resources\AdminResource;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class EntryController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(BootstrapRequest $request): JsonResponse
    {
        $data = $this->apis->resolve('runa.admin', AdminAccess::class, '1.0.0')->entry();

        return (new AdminResource($data))->response()->header('Cache-Control', 'no-store, private');
    }
}
