<?php

namespace Runa\Modules\Admin\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Integration\Core\AdminEntry;
use Runa\Modules\Admin\Http\Requests\BootstrapRequest;

final readonly class ActivityController
{
    public function __construct(private AdminEntry $entry) {}

    public function __invoke(BootstrapRequest $request): JsonResponse
    {
        return response()->json(['data' => $this->entry->activity($request->isMethod('POST'))], 200,
            ['Cache-Control' => 'no-store, private']);
    }
}
