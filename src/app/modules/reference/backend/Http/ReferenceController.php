<?php

namespace App\Modules\Reference\Http;

use App\Modules\Reference\Contracts\ReferenceTools;
use Illuminate\Http\JsonResponse;
use Runa\Support\Modules\Api\ModuleApis;

final readonly class ReferenceController
{
    public function __construct(private ModuleApis $apis) {}

    public function __invoke(ReferenceRequest $request): JsonResponse
    {
        $api = $this->apis->resolve('app.reference', ReferenceTools::class, '1.0.0');
        $input = $request->validated();
        $result = match ($request->route()?->defaults['_runa_operation'] ?? null) {
            'samples.read' => $api->samples(),
            'samples.edit' => $api->editSample($input['public_id'], $input['revision'], $input['code']),
            'settings.read' => $api->settings(),
            'settings.review' => $api->reviewSettings($input['values'], (int) $input['revision'], $input['review_id']),
            'settings.publish' => $api->publishSettings($input['values'], (int) $input['revision'], $input['review_id'], $input['review']),
            'snapshots.read' => $api->snapshots($input['public_id'] ?? null),
            'snapshots.capture' => $api->captureSnapshot($input['name'], (int) $input['revision'], $input['review_id']),
            'snapshots.delete' => $api->deleteSnapshot($input['public_id'], (int) $input['revision'], $input['review_id']),
            'history.read' => $api->history($input['sample'] ?? null, $input['before'] ?? null),
            'work.dispatch' => $api->dispatch(),
            'work.read' => $api->work(),
            default => throw new \LogicException('Unknown reference operation.'),
        };

        return (new ReferenceResource($result))->response()->header('Cache-Control', 'no-store');
    }
}
