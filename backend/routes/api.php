<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\VoterController;
use App\Http\Controllers\Api\VoteController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me']);

    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::patch('events/{event}', [EventController::class, 'update']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);

    Route::get('events/{event}/categories', [CategoryController::class, 'index']);
    Route::post('events/{event}/categories', [CategoryController::class, 'store']);
    Route::patch('events/{event}/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('events/{event}/categories/{category}', [CategoryController::class, 'destroy']);
    Route::post('events/{event}/candidates', [CandidateController::class, 'store']);
    Route::patch('events/{event}/candidates/{candidate}', [CandidateController::class, 'update']);
    Route::delete('events/{event}/candidates/{candidate}', [CandidateController::class, 'destroy']);
    Route::get('events/{event}/voters', [VoterController::class, 'index']);
    Route::post('events/{event}/voters', [VoterController::class, 'store']);
    Route::patch('events/{event}/voters/{voter}', [VoterController::class, 'update']);
    Route::delete('events/{event}/voters/{voter}', [VoterController::class, 'destroy']);
    Route::post('events/{event}/voters/import', [VoterController::class, 'import']);
    Route::post('events/{event}/voters/generate', [VoterController::class, 'generate']);
    Route::get('events/{event}/voting-link', [EventController::class, 'link']);
});

Route::prefix('public')->group(function () {
    Route::get('events/{token}', [EventController::class, 'publicShow']);
    Route::get('events/{token}/results', [EventController::class, 'results']);
    Route::post('events/{token}/code/validate', [VoterController::class, 'validateCode']);
    Route::post('events/{token}/vote', [VoteController::class, 'store']);
    Route::post('events/{event}/code/validate', [VoterController::class, 'validateCodeByEvent']);
});
