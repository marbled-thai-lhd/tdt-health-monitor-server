<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes - require authentication
Route::middleware(['auth.check'])->group(function () {
    // Dashboard routes
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Server routes (read-only for viewers)
    Route::get('/dashboard/servers', [DashboardController::class, 'servers'])->name('dashboard.servers');

    // Server management routes (admin only) - MUST be before {server} parameter routes
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/dashboard/servers/create', [DashboardController::class, 'createServer'])->name('dashboard.servers.create');
        Route::post('/dashboard/servers', [DashboardController::class, 'storeServer'])->name('dashboard.servers.store');
        Route::get('/dashboard/servers/{server}/setup', [DashboardController::class, 'setupServer'])->name('dashboard.servers.setup');
        Route::get('/dashboard/servers/{server}/edit', [DashboardController::class, 'editServer'])->name('dashboard.servers.edit');
        Route::put('/dashboard/servers/{server}', [DashboardController::class, 'updateServer'])->name('dashboard.servers.update');
        Route::delete('/dashboard/servers/{server}', [DashboardController::class, 'deleteServer'])->name('dashboard.servers.delete');
        Route::post('/dashboard/servers/{server}/force-check', [DashboardController::class, 'forceHealthCheck'])->name('dashboard.servers.force-check');
    });

    // Server detail routes (available for all authenticated users)
    Route::get('/dashboard/servers/{server}', [DashboardController::class, 'serverDetail'])->name('dashboard.server-detail');
    Route::get('/dashboard/servers/{server}/backup/{filename}', [DashboardController::class, 'downloadBackup'])->name('dashboard.servers.backup.download');

    // Soft delete management routes (admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/dashboard/servers-archive', [DashboardController::class, 'deletedServers'])->name('dashboard.servers.archived');
        Route::post('/dashboard/servers/{serverId}/restore', [DashboardController::class, 'restoreServer'])->name('dashboard.servers.restore');
        Route::delete('/dashboard/servers/{serverId}/force-delete', [DashboardController::class, 'forceDeleteServer'])->name('dashboard.servers.force-delete');
    });

    // Alert routes (read-only for viewers)
    Route::get('/dashboard/alerts', [DashboardController::class, 'alerts'])->name('dashboard.alerts');
    Route::get('/dashboard/alerts/export', [DashboardController::class, 'exportAlerts'])->name('dashboard.alerts.export');
    Route::get('/dashboard/alerts/{alert}/detail', [DashboardController::class, 'getAlertDetail'])->name('dashboard.alerts.detail');

    // Alert management routes (admin only)
    Route::middleware(['role:admin'])->group(function () {
        Route::post('/dashboard/alerts/resolve-all', [DashboardController::class, 'resolveAllAlerts'])->name('dashboard.alerts.resolve-all');
        Route::post('/dashboard/alerts/{alert}/resolve', [DashboardController::class, 'resolveAlert'])->name('dashboard.resolve-alert');
    });
});
