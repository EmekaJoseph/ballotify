<?php

/*
| Sign-up, sign-in, two-step code, password reset and email verification.
| Loaded by routes/api.php under the /api/auth prefix.
*/

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('two-factor', [AuthController::class, 'twoFactorChallenge']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Link from the verification email (signature covers path + query only).
Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
    ->middleware(['signed:relative', 'throttle:12,1'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');
});
