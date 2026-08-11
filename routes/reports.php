<?php

use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('reports/weekly', [WeeklyReportController::class, 'index'])->name('reports.weekly');
    Route::get('reports/weekly/pdf', [WeeklyReportController::class, 'pdf'])->name('reports.weekly.pdf');
    Route::get('reports/weekly/excel', [WeeklyReportController::class, 'excel'])->name('reports.weekly.excel');
});
