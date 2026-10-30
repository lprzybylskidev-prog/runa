<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\ApplicationIdentityController;
use Runa\Http\Controllers\HealthController;
use Runa\Http\Controllers\ModuleFrontendController;
use Runa\Http\Controllers\ReadinessController;
use Runa\Integration\Core\AdminBootstrapController;
use Runa\Integration\Core\AdminHttpGuard;

Route::get('/health', HealthController::class)->name('api.health');

Route::get('/ready', ReadinessController::class)->name('api.ready');

Route::get('/identity', ApplicationIdentityController::class)->name('api.identity');

Route::get('/modules/frontend', ModuleFrontendController::class)->name('api.modules.frontend');

// Fixed Core adapter: the existing module HTTP namespace remains unchanged.
Route::get('/admin', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)->name('runa.admin.bootstrap');
