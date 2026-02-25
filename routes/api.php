<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    // Rate-limit login and forgot-password: 5 attempts per minute per IP
    Route::middleware('throttle:5,1')->group(function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [ForgotPasswordController::class, 'send']);
        Route::post('reset-password', [ResetPasswordController::class, 'reset']);
    });

    Route::post('register', [AuthController::class, 'register']);
});

/*
|--------------------------------------------------------------------------
| Auth Routes (Protected — require Sanctum token)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('change-password', [ChangePasswordController::class, 'update']);
});
