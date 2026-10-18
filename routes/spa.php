<?php

use App\Http\Controllers\EmailReviewController;
use App\Http\Controllers\SpaController;
use App\Http\Controllers\UiReviewController;
use Illuminate\Support\Facades\Route;

Route::get('/', SpaController::class)->name('spa');

// Temporary visual review infrastructure; no configuration flag can enable it in production.
if (app()->environment('local', 'testing')) {
    Route::get('/__review/email', EmailReviewController::class)->name('review.email');

    foreach (['overview', 'projects', 'activity', 'archive', 'team'] as $page) {
        Route::get('/__review/application/'.$page, UiReviewController::class)
            ->name('review.application.'.$page);
    }

    Route::get('/__review/ui', UiReviewController::class)
        ->name('review.ui');
}
