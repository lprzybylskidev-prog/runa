<?php

use Illuminate\Support\Facades\Route;
use Runa\Http\Controllers\WelcomePageController;

Route::get('/', WelcomePageController::class)->name('spa');
