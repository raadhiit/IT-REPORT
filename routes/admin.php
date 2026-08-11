<?php

use App\Http\Controllers\Admin\ComplianceController;
use App\Http\Controllers\Admin\ReportSettingController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('compliance', [ComplianceController::class, 'index'])->name('compliance.index');
    Route::get('report-settings', [ReportSettingController::class, 'edit'])->name('report-settings.edit');
    Route::put('report-settings', [ReportSettingController::class, 'update'])->name('report-settings.update');
});
