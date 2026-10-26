<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Database\Connection;
use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\DeliveryRouting;
use Runa\Modules\Auth\Http\Requests\DeliveryRequest;
use Runa\Modules\Auth\Models\User;
use Runa\Modules\Auth\Support\SecurityContext;

final readonly class DeliveryController
{
    public function __construct(private SecurityContext $context, private DeliveryRouting $routing) {}

    public function __invoke(DeliveryRequest $request): JsonResponse
    {
        $data = $this->context->run($request, fn (Connection $connection, User $user): array => $request->input('operation') === 'read'
            ? $this->routing->own($connection, $user) : $this->routing->configure($connection, $user, $request->validated()), count: false, unrestricted: true);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
