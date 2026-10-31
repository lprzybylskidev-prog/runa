<?php

namespace Runa\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Support\Settings\ApplicationPresentation;

final class ApplicationPresentationController extends Controller
{
    public function __invoke(ApplicationPresentation $presentation): JsonResponse
    {
        return response()->json(['data' => $presentation->read()], headers: ['Cache-Control' => 'no-store, private']);
    }
}
