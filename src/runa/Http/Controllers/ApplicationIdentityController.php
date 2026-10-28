<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Http\Resources\ApplicationIdentityResource;
use Runa\Identity\ApplicationIdentity;

final class ApplicationIdentityController extends Controller
{
    public function __invoke(ApplicationIdentity $identity): JsonResponse
    {
        return (new ApplicationIdentityResource($identity))->response()->header('Cache-Control', 'no-store, private');
    }
}
