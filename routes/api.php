<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\PedagogController;
use App\Http\Controllers\Api\AdminStudentController;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login',    [AuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->group(function () {
    
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/me',           [AuthController::class, 'me']);

    // ADMIN ROUTES
    // Vetëm përdoruesit me role 'admin' mund të hyjnë këtu
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::apiResource('students', AdminStudentController::class);
    });

    //  STUDENT ROUTES
    Route::middleware('role:student')->prefix('student')->group(function () {
        Route::get('/statistikat', [StudentController::class, 'statistikat']);
        Route::get('/lende',       [StudentController::class, 'lende']);
        Route::get('/seksione',    [StudentController::class, 'seksione']);
        Route::get('/provime',     [StudentController::class, 'provime']);
        Route::get('/seksione-te-lira', [StudentController::class, 'seksioneTeLira']);
        Route::post('/regjistrim',      [StudentController::class, 'regjistrim']);
        Route::delete('/regjistrim/{id}', [StudentController::class, 'cregjistrim']);
    });

    //  PEDAGOG ROUTES 
    Route::middleware('role:pedagog')->prefix('pedagog')->group(function () {
        Route::get('/statistikat', [PedagogController::class, 'statistikat']);
        Route::get('/seksione',    [PedagogController::class, 'seksione']);
        Route::get('/provime',     [PedagogController::class, 'provime']);
    });
});
