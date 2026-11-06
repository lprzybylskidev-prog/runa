<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\ApplicationAssetController;
use Runa\Http\Controllers\ApplicationIdentityController;
use Runa\Http\Controllers\ApplicationPresentationController;
use Runa\Http\Controllers\HealthController;
use Runa\Http\Controllers\ModuleFrontendController;
use Runa\Http\Controllers\ReadinessController;
use Runa\Integration\Core\AdminBootstrapController;
use Runa\Integration\Core\AdminHttpGuard;

Route::get('/health', HealthController::class)->name('api.health');

Route::get('/ready', ReadinessController::class)->name('api.ready');

Route::get('/presentation/assets/{kind}/{hash}.png', ApplicationAssetController::class)->where(['kind' => 'logo|logo_icon|favicon', 'hash' => '[a-f0-9]{64}'])->name('runa.presentation.asset');

Route::get('/presentation', ApplicationPresentationController::class)->name('runa.presentation.show');

Route::get('/identity', ApplicationIdentityController::class)->name('api.identity');

Route::get('/modules/frontend', ModuleFrontendController::class)->name('api.modules.frontend');

// Fixed Core adapter: the existing module HTTP namespace remains unchanged.
Route::get('/admin', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)->name('runa.admin.bootstrap');

Route::get('/admin/activity', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)->name('runa.admin.activity.show');
Route::post('/admin/activity', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)->name('runa.admin.activity.renew');

Route::get('/admin/entry', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)->name('runa.admin.entry');
Route::get('/admin/operations/{operation}', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where('operation', '[a-z][a-z.]*')->name('runa.admin.operations.read');
Route::post('/admin/operations/{operation}', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where('operation', '[a-z][a-z.]*')->name('runa.admin.operations.execute');

Route::get('/admin/{owner}/{page}', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}'])
    ->name('runa.admin.page.read');

Route::get('/admin/{owner}/{page}/sections/{section}', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}', 'section' => '[a-z][a-z0-9_.]{0,511}'])
    ->name('runa.admin.section.read');

Route::post('/admin/{owner}/{page}/sections/{section}/commands', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}', 'section' => '[a-z][a-z0-9_.]{0,511}'])
    ->name('runa.admin.command');
Route::post('/admin/{owner}/{page}/sections/{section}/preview', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}', 'section' => '[a-z][a-z0-9_.]{0,511}'])
    ->name('runa.admin.command.preview');

Route::post('/admin/{owner}/{page}/preview', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}'])
    ->name('runa.admin.preview');
Route::post('/admin/{owner}/{page}/save', AdminBootstrapController::class)->middleware(AdminHttpGuard::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}'])
    ->name('runa.admin.save');
