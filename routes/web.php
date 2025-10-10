<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Dashboard routes
Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/dashboard', [DashboardController::class, 'index']);

// Server routes
Route::get('/dashboard/servers', [DashboardController::class, 'servers'])->name('dashboard.servers');
Route::get('/dashboard/servers/create', [DashboardController::class, 'createServer'])->name('dashboard.servers.create');
Route::post('/dashboard/servers', [DashboardController::class, 'storeServer'])->name('dashboard.servers.store');
Route::get('/dashboard/servers/{server}/setup', [DashboardController::class, 'setupServer'])->name('dashboard.servers.setup');
Route::get('/dashboard/servers/{server}', [DashboardController::class, 'serverDetail'])->name('dashboard.server-detail');
Route::post('/dashboard/servers/{server}/force-check', [DashboardController::class, 'forceHealthCheck'])->name('dashboard.servers.force-check');

// Alert routes
Route::get('/dashboard/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
Route::get('/dashboard/alerts/export', [DashboardController::class, 'exportAlerts'])->name('dashboard.alerts.export');
Route::post('/dashboard/alerts/resolve-all', [DashboardController::class, 'resolveAllAlerts'])->name('dashboard.alerts.resolve-all');
Route::get('/dashboard/alerts/{alert}/detail', [DashboardController::class, 'getAlertDetail'])->name('dashboard.alerts.detail');
Route::post('/dashboard/alerts/{alert}/resolve', [DashboardController::class, 'resolveAlert'])->name('dashboard.resolve-alert');
