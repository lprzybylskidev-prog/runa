<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\AuthEntryPageController;

foreach (['/app' => 'application', '/account' => 'account'] as $path => $name) {
    Route::get($path, AuthEntryPageController::class.'@__invoke')->name('runa.auth.pages.'.$name);
}

foreach (['login', 'register', 'forgot-password', 'reset-password', 'invitation', 'verify-email', 'confirm-address', 'security'] as $page) {
    Route::get('/auth/'.$page, AuthEntryPageController::class.'@__invoke')->name('runa.auth.pages.'.str_replace('-', '_', $page));
}

// Presentation only; the document contains no protected Admin projection.
Route::get('/admin', AuthEntryPageController::class)->name('runa.admin.dashboard');
Route::get('/admin/{owner}/{page}', AuthEntryPageController::class)
    ->where(['owner' => 'app|(?:app|runa)\.[a-z][a-z0-9_]{0,47}', 'page' => '[a-z][a-z0-9_]{0,63}'])
    ->name('runa.admin.page');
