<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/history', [DashboardController::class, 'history'])->name('history');
    
    // API Routes untuk real-time data
    Route::get('/api/sensor/latest', [DashboardController::class, 'getLatestSensorData'])->name('api.sensor.latest');
    Route::get('/api/sensor/chart', [DashboardController::class, 'getChartData'])->name('api.sensor.chart');
    Route::get('/api/history-stats', [DashboardController::class, 'getHistoryStats'])->name('api.history.stats');
});
