<?php

use App\Http\Controllers\Api\HealthReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health monitoring API routes
Route::prefix('health')->group(function () {
    Route::post('report', [HealthReportController::class, 'store'])->name('health.report');
    Route::post('backup-notification', [HealthReportController::class, 'backupNotification'])->name('health.backup-notification');
});
