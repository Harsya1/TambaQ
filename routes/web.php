<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AnalyticsController;

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password Reset Routes
Route::get('/forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.forgot');
Route::post('/forgot-password', [AuthController::class, 'verifyResetPassword'])->name('password.verify');
Route::get('/reset-password', [AuthController::class, 'showResetPasswordForm'])->name('password.reset.form');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');

// Protected Routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/history', [DashboardController::class, 'history'])->name('history');
    
    // API Routes untuk real-time data
    Route::get('/api/sensor/latest', [DashboardController::class, 'getLatestSensorData'])->name('api.sensor.latest');
    Route::get('/api/sensor/chart', [DashboardController::class, 'getChartData'])->name('api.sensor.chart');
    Route::get('/api/history-stats', [DashboardController::class, 'getHistoryStats'])->name('api.history.stats');
    Route::get('/api/history/data', [DashboardController::class, 'getHistoryData'])->name('api.history.data');
    Route::get('/api/response-time/24hours', [DashboardController::class, 'getResponseTime24Hours'])->name('api.response.time.24hours');
    
    // Analytics API Routes
    Route::get('/api/trend/7days', [AnalyticsController::class, 'getTrend7Days'])->name('api.trend.7days');
    Route::get('/api/trend/30days', [AnalyticsController::class, 'getTrend30Days'])->name('api.trend.30days');
    Route::get('/api/correlation', [AnalyticsController::class, 'getCorrelation'])->name('api.correlation');
    Route::get('/api/forecast', [AnalyticsController::class, 'getForecast'])->name('api.forecast');
    Route::get('/api/export/csv', [AnalyticsController::class, 'exportCsv'])->name('api.export.csv');
    Route::get('/api/export/pdf', [AnalyticsController::class, 'exportPdf'])->name('api.export.pdf');
});
