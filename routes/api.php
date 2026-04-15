<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\PedagogController;

// Public routes me rate limiting (max 5 kërkesa në minutë)
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me',           [AuthController::class, 'me']);

    // Student routes
    Route::prefix('student')->group(function () {
        Route::get('/statistikat', [StudentController::class, 'statistikat']);
        Route::get('/lende',       [StudentController::class, 'lende']);
        Route::get('/seksione',    [StudentController::class, 'seksione']);
        Route::post('/seksione/{sekId}/regjistrohu', [StudentController::class, 'regjistrohu']);
        Route::get('/provime',     [StudentController::class, 'provime']);
    });

    // Pedagog routes
    Route::prefix('pedagog')->group(function () {
        Route::get('/statistikat', [PedagogController::class, 'statistikat']);
        Route::get('/seksione',    [PedagogController::class, 'seksione']);
        Route::get('/provime',     [PedagogController::class, 'provime']);
    });
});
