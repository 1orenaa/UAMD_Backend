<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\PedagogController;
use App\Http\Controllers\Api\AdminStudentController;

// ── Public routes me rate limiting (max 5 kërkesa/minutë për IP) ─────────────
Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register',        [AuthController::class, 'register']);
    Route::post('/auth/login',           [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/auth/reset-password',  [AuthController::class, 'resetPassword']);
});

// ── Protected routes (kërkon token Sanctum) ───────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me',           [AuthController::class, 'me']);

    // ── Student routes (vetëm role: student) ─────────────────────────────────
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/statistikat',          [StudentController::class, 'statistikat']);
        Route::get('/lende',                [StudentController::class, 'lende']);
        Route::get('/provime',              [StudentController::class, 'provime']);
        Route::get('/seksione',             [StudentController::class, 'seksione']);
        Route::post('/regjistrim',          [StudentController::class, 'regjistro']);
        Route::delete('/regjistrim/{id}',   [StudentController::class, 'cregjistro'])
            ->whereNumber('id');
    });

    // ── Pedagog routes (vetëm role: pedagog) ─────────────────────────────────
    Route::middleware('role:pedagog')->prefix('pedagog')->group(function () {
        Route::get('/statistikat', [PedagogController::class, 'statistikat']);
        Route::get('/seksione',    [PedagogController::class, 'seksione']);
        Route::get('/provime',     [PedagogController::class, 'provime']);
    });

    // ── Admin routes (vetëm role: admin) ─────────────────────────────────────
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::get('/studentet',        [AdminStudentController::class, 'index']);
        Route::get('/studentet/{id}',   [AdminStudentController::class, 'show']);
        Route::post('/studentet',       [AdminStudentController::class, 'store']);
        Route::put('/studentet/{id}',   [AdminStudentController::class, 'update']);
        Route::delete('/studentet/{id}',[AdminStudentController::class, 'destroy']);
        Route::get('/departamente',     [AdminStudentController::class, 'departamente']);
    });

});
