<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\VoterController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\MessagingController;

// ---------------------------------------------------------------- authentication
Route::prefix('auth')->group(function () {
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

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');
    });
});

// ---------------------------------------------------------------- organiser area
// scopeBindings(): a {category}/{candidate}/{voter} in the URL must belong to the
// {event} in the same URL, otherwise Laravel answers 404.
Route::middleware('auth:sanctum')->scopeBindings()->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me', [AuthController::class, 'updateProfile']);
    Route::post('me/two-factor', [AuthController::class, 'twoFactorSetup'])->middleware('throttle:6,1');
    Route::post('me/two-factor/confirm', [AuthController::class, 'twoFactorConfirm'])->middleware('throttle:12,1');
    Route::delete('me/two-factor', [AuthController::class, 'twoFactorDisable'])->middleware('throttle:6,1');

    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::patch('events/{event}', [EventController::class, 'update']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);
    Route::get('events/{event}/voting-link', [EventController::class, 'link']);
    Route::get('events/{event}/results', [EventController::class, 'adminResults']);
    Route::get('events/{event}/activity', [EventController::class, 'activity']);

    Route::get('events/{event}/categories', [CategoryController::class, 'index']);
    Route::post('events/{event}/categories', [CategoryController::class, 'store']);
    Route::patch('events/{event}/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('events/{event}/categories/{category}', [CategoryController::class, 'destroy']);

    Route::get('events/{event}/candidates', [CandidateController::class, 'index']);
    Route::post('events/{event}/candidates', [CandidateController::class, 'store']);
    // POST alias: browsers cannot send multipart/form-data (image upload) with PATCH to PHP.
    Route::match(['patch', 'post'], 'events/{event}/candidates/{candidate}', [CandidateController::class, 'update']);
    Route::delete('events/{event}/candidates/{candidate}', [CandidateController::class, 'destroy']);

    Route::get('events/{event}/voters', [VoterController::class, 'index']);
    Route::post('events/{event}/voters', [VoterController::class, 'store']);
    Route::patch('events/{event}/voters/{voter}', [VoterController::class, 'update']);
    Route::delete('events/{event}/voters/{voter}', [VoterController::class, 'destroy']);
    Route::post('events/{event}/voters/import', [VoterController::class, 'import']);
    Route::post('events/{event}/voters/generate', [VoterController::class, 'generate']);
    Route::post('events/{event}/voters/erase-contacts', [VoterController::class, 'eraseContacts']);
    // Sending messages requires a confirmed email address (stops spam from throwaway accounts).
    Route::post('events/{event}/voters/invite', [VoterController::class, 'invite'])->middleware(['verified', 'throttle:20,1']);

    Route::get('messaging/status', [MessagingController::class, 'status']);
});

// ---------------------------------------------------------------- public (voters)
Route::prefix('public')->group(function () {
    Route::get('events/{token}', [EventController::class, 'publicShow'])->middleware('throttle:public');
    Route::get('events/{token}/results', [EventController::class, 'results'])->middleware('throttle:public');
    // Code guessing endpoints are tightly limited per IP to stop brute forcing.
    Route::post('events/{token}/code/validate', [VoterController::class, 'validateCode'])->middleware('throttle:voting');
    Route::post('events/{token}/vote', [VoteController::class, 'store'])->middleware('throttle:voting');
});
