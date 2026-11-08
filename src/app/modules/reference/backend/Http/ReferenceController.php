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
        /** @var array<string,mixed> $values */
        $values = $request->validated('values', []);
        $publicId = $request->validated('public_id');
        $sample = $request->validated('sample');
        $before = $request->validated('before');
        $result = match ($request->route()?->defaults['_runa_operation'] ?? null) {
            'samples.read' => $api->samples(),
            'samples.edit' => $api->editSample($request->string('public_id')->toString(), $request->string('revision')->toString(), $request->string('code')->toString()),
            'settings.read' => $api->settings(),
            'settings.review' => $api->reviewSettings($values, $request->integer('revision'), $request->string('review_id')->toString()),
            'settings.publish' => $api->publishSettings($values, $request->integer('revision'), $request->string('review_id')->toString(), $request->string('review')->toString()),
            'snapshots.read' => $api->snapshots(is_string($publicId) ? $publicId : null),
            'snapshots.capture' => $api->captureSnapshot($request->string('name')->toString(), $request->integer('revision'), $request->string('review_id')->toString()),
            'snapshots.delete' => $api->deleteSnapshot($request->string('public_id')->toString(), $request->integer('revision'), $request->string('review_id')->toString()),
            'history.read' => $api->history(is_string($sample) ? $sample : null, is_string($before) ? $before : null),
            'work.dispatch' => $api->dispatch(),
            'work.read' => $api->work(),
            default => throw new \LogicException('Unknown reference operation.'),
        };

        return (new ReferenceResource($result))->response()->header('Cache-Control', 'no-store');
    }
}
