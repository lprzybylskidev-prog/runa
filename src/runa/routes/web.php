<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\AuthEntryPageController;

foreach (['/app' => 'application', '/account' => 'account'] as $path => $name) {
    Route::get($path, AuthEntryPageController::class.'@__invoke')->name('runa.auth.pages.'.$name);
}

foreach (['login', 'register', 'forgot-password', 'reset-password', 'invitation', 'verify-email', 'confirm-address', 'security'] as $page) {
    Route::get('/auth/'.$page, AuthEntryPageController::class.'@__invoke')->name('runa.auth.pages.'.str_replace('-', '_', $page));
}
