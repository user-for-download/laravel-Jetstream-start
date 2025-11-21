<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Facades\Route;

// Public Routes
Route::get('/', [WelcomeController::class, 'index'])->name('home');

// Health Check (Throttled, No CSRF needed)
Route::get('/health', HealthCheckController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->middleware(['throttle:60,1'])
    ->name('health.detailed');

// Protected Routes
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function (): void {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});
