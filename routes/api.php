<?php

use App\Http\Controllers\Api\HealthReportController;
use App\Http\Controllers\DashboardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Health monitoring API routes
Route::post('health-report', [HealthReportController::class, 'store'])->name('api.health-report');
Route::post('backup-notification', [HealthReportController::class, 'backupNotification'])->name('api.backup-notification');
Route::post('force-check/{server}', [HealthReportController::class, 'forceCheck'])->name('api.force-check');

// Dashboard force check route (no CSRF)
Route::post('dashboard/force-check/{server}', [DashboardController::class, 'forceHealthCheck'])->name('api.dashboard.force-check');
