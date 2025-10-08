<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/servers', [DashboardController::class, 'servers'])->name('servers');
Route::get('/servers/{server}', [DashboardController::class, 'serverDetail'])->name('server.detail');
Route::get('/alerts', [DashboardController::class, 'alerts'])->name('alerts');
Route::post('/alerts/{alert}/resolve', [DashboardController::class, 'resolveAlert'])->name('alert.resolve');
