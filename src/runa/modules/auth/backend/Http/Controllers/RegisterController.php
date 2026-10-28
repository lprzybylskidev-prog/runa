<?php

namespace Runa\Modules\Auth\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Runa\Modules\Auth\Actions\RegisterAccount;
use Runa\Modules\Auth\Http\Requests\RegistrationRequest;

final readonly class RegisterController
{
    public function __construct(private RegisterAccount $registration) {}

    public function __invoke(RegistrationRequest $request): JsonResponse
    {
        /** @var array{email: string, password: string, name?: string|null, first_name?: string|null, surname?: string|null, login?: string|null, password_confirmation?: string} $input */
        $input = $request->validated();
        $this->registration->create($input);

        return response()->json(['data' => ['accepted' => true]], 202)->header('Cache-Control', 'no-store');
    }
}
