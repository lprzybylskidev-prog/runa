<?php

use Runa\Modules\Auth\Development\Accounts;
use Runa\Modules\Auth\Development\AuthDevelopmentSeeder;
use Runa\Modules\Permissions\Development\Access;
use Runa\Modules\Permissions\Development\PermissionsDevelopmentSeeder;

return [
    // Explicit application ManagedSeeder registration only; loose files are never discovered.
    'register' => [],
    // Explicit opt-in via runa:seed:development --modules; native db:seed stays application-owned.
    'contributors' => [
        'runa.auth' => Accounts::class,
        'runa.permissions' => Access::class,
    ],
    'development' => [
        AuthDevelopmentSeeder::class => [
            'random' => (bool) env('DEV_SEED_AUTH_RANDOM', false),
            'seed' => (int) env('DEV_SEED_AUTH_SEED', 17),
            'counts' => ['extra_users' => (int) env('DEV_SEED_AUTH_USERS_COUNT', 3)],
        ],
        PermissionsDevelopmentSeeder::class => [
            'random' => (bool) env('DEV_SEED_PERMISSIONS_RANDOM', false),
            'seed' => (int) env('DEV_SEED_PERMISSIONS_SEED', 17),
            'counts' => ['extra_roles' => (int) env('DEV_SEED_PERMISSIONS_ROLES_COUNT', 2)],
        ],
    ],
];
