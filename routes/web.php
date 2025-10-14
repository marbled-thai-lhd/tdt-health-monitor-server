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
Route::get('/dashboard/servers/{server}/edit', [DashboardController::class, 'editServer'])->name('dashboard.servers.edit');
Route::put('/dashboard/servers/{server}', [DashboardController::class, 'updateServer'])->name('dashboard.servers.update');
Route::delete('/dashboard/servers/{server}', [DashboardController::class, 'deleteServer'])->name('dashboard.servers.delete');
Route::post('/dashboard/servers/{server}/force-check', [DashboardController::class, 'forceHealthCheck'])->name('dashboard.servers.force-check');
Route::get('/dashboard/servers/{server}/backup/{filename}', [DashboardController::class, 'downloadBackup'])->name('dashboard.servers.backup.download');

// Soft delete management routes
Route::get('/dashboard/servers-archive', [DashboardController::class, 'deletedServers'])->name('dashboard.servers.archived');
Route::post('/dashboard/servers/{serverId}/restore', [DashboardController::class, 'restoreServer'])->name('dashboard.servers.restore');
Route::delete('/dashboard/servers/{serverId}/force-delete', [DashboardController::class, 'forceDeleteServer'])->name('dashboard.servers.force-delete');

// Alert routes
Route::get('/dashboard/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
Route::get('/dashboard/alerts/export', [DashboardController::class, 'exportAlerts'])->name('dashboard.alerts.export');
Route::post('/dashboard/alerts/resolve-all', [DashboardController::class, 'resolveAllAlerts'])->name('dashboard.alerts.resolve-all');
Route::get('/dashboard/alerts/{alert}/detail', [DashboardController::class, 'getAlertDetail'])->name('dashboard.alerts.detail');
Route::post('/dashboard/alerts/{alert}/resolve', [DashboardController::class, 'resolveAlert'])->name('dashboard.resolve-alert');
