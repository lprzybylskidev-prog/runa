<?php

use Runa\Modules\Auth\Models\User;

return [
    'defaults' => ['guard' => 'web'],
    'guards' => ['web' => ['driver' => 'session', 'provider' => 'runa_users']],
    'providers' => ['runa_users' => ['driver' => 'eloquent', 'model' => User::class]],
];
