<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\ApplicationIdentityController;
use Runa\Http\Controllers\HealthController;
use Runa\Http\Controllers\ReadinessController;

Route::get('/health', HealthController::class)->name('api.health');

Route::get('/ready', ReadinessController::class)->name('api.ready');

Route::get('/identity', ApplicationIdentityController::class)->name('api.identity');
