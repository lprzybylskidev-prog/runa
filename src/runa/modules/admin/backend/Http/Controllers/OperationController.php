<?php

namespace Runa\Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Admin\Contracts\AdminOperations;
use Runa\Modules\Admin\Http\Requests\OperationRequest;
use Runa\Modules\Admin\Http\Resources\AdminResource;
use Runa\Support\Modules\Api\ModuleApis;
use Runa\Support\Modules\Api\OperationRejected;

final readonly class OperationController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(OperationRequest $request): JsonResponse
    {
        $api = $this->apis->resolve('runa.admin', AdminOperations::class, '1.0.0');
        $operation = (string) $request->route('operation');
        try {
            $data = $request->isMethod('GET') ? $api->read($operation, $request->operationInput()) : $api->execute($operation, $request->operationInput());

            return (new AdminResource($data))->response()->header('Cache-Control', 'no-store, private');
        } catch (OperationRejected $error) {
            return response()->json(['message' => 'Administration operation refused.', 'reason' => $error->reason, 'scope' => 'operation'], $error->status, ['Cache-Control' => 'no-store, private']);
        }
    }
}
