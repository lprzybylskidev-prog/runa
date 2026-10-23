<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\EmailAddresses;
use Runa\Modules\Auth\Http\Requests\EmailChangeRequest;

final readonly class EmailChangeController
{
    public function __construct(private EmailAddresses $action) {}

    public function __invoke(EmailChangeRequest $request): JsonResponse
    {
        $data = $this->action->ownPrimary($request);

        return (new ProfileResource($data))->response()->header('Cache-Control', 'no-store');
    }
}
