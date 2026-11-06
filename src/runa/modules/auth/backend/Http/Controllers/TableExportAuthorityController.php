<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;

/** Named authenticated route admission checks current Auth availability and export grant. */
final readonly class TableExportAuthorityController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => ['allowed' => true]], headers: ['Cache-Control' => 'no-store']);
    }
}
