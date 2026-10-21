<?php

use App\Http\Controllers\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', SpaController::class)->name('spa');
