<?php

namespace Runa\Modules\Auth\Http;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Runa\Modules\Auth\Http\Requests\RegistrationRequest;

final readonly class RegisterController
{
    public function __construct(private CreatesNewUsers $registration) {}

    public function __invoke(RegistrationRequest $request): JsonResponse
    {
        $this->registration->create($request->validated());

        return response()->json(['data' => ['accepted' => true]], 202)->header('Cache-Control', 'no-store');
    }
}
