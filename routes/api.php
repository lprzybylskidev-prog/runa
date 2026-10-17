<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\ReadinessController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class)->name('api.health');

Route::get('/ready', ReadinessController::class)->name('api.ready');
