<?php

use Runa\Modules\Auth\Models\User;

return [
    'operator_recovery_enabled' => env('AUTH_OPERATOR_RECOVERY_ENABLED', true),
    'defaults' => ['guard' => 'web'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'runa_users']],
    'providers' => ['runa_users' => ['driver' => 'eloquent', 'model' => User::class]],
];
