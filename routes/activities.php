<?php

use App\Http\Controllers\ActivityAttachmentController;
use App\Http\Controllers\ActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('activities', ActivityController::class)->only(['index', 'store']);

    Route::get('activity-attachments/{attachment}', [ActivityAttachmentController::class, 'show'])
        ->name('activity-attachments.show');
});
