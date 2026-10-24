<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\AuthEntryPageController;

foreach (['login', 'register', 'forgot-password', 'reset-password', 'invitation', 'verify-email', 'confirm-address', 'security'] as $page) {
    Route::get('/auth/'.$page, AuthEntryPageController::class.'@__invoke')->name('runa.auth.pages.'.str_replace('-', '_', $page));
}
