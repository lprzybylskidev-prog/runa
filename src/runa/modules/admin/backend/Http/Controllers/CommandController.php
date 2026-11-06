<?php

namespace Runa\Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Admin\Contracts\AdminAccess;
use Runa\Modules\Admin\Http\Requests\CommandRequest;
use Runa\Modules\Admin\Http\Resources\AdminResource;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class CommandController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(CommandRequest $request): JsonResponse
    {
        $access = $this->apis->resolve('runa.admin', AdminAccess::class, '1.0.0');
        $method = $request->routeIs('runa.admin.command.preview') ? 'previewCommand' : 'command';
        /** @var array<string,mixed> $input HTTP JSON fields, including unknown keys for owning validation. */
        $input = $request->all();
        $data = $access->$method((string) $request->route('owner'), (string) $request->route('page'),
            (string) $request->route('section'), $input);

        return (new AdminResource($data))->response()->header('Cache-Control', 'no-store, private');
    }
}
