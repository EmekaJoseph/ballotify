<?php

/*
| Events and everything about them that isn't the ballot or the voter list:
| results, activity, branding, nominations, certificates, co-organisers, messaging.
| Loaded by routes/api.php inside the signed-in group.
*/

use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MessagingController;
use App\Http\Controllers\Api\NominationController;
use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('event-templates', [EventController::class, 'templates']);

Route::get('events', [EventController::class, 'index']);
Route::post('events', [EventController::class, 'store']);
Route::get('events/{event}', [EventController::class, 'show']);
Route::patch('events/{event}', [EventController::class, 'update']);
Route::delete('events/{event}', [EventController::class, 'destroy']);
Route::get('events/{event}/voting-link', [EventController::class, 'link']);
Route::get('events/{event}/results', [EventController::class, 'adminResults']);
Route::get('events/{event}/activity', [EventController::class, 'activity']);
Route::post('events/{event}/logo', [EventController::class, 'logo'])->middleware('throttle:20,1');
Route::post('events/{event}/duplicate', [EventController::class, 'duplicate'])->middleware('throttle:20,1');

// Nominations (organiser view) and certified results
Route::get('events/{event}/nominations', [NominationController::class, 'index']);
Route::post('events/{event}/nominations/promote', [NominationController::class, 'promote']);
Route::put('events/{event}/nominations/suggestions', [NominationController::class, 'suggestions']);
Route::get('events/{event}/certificates', [CertificateController::class, 'index']);
Route::post('events/{event}/certificates', [CertificateController::class, 'issue'])->middleware('throttle:10,1');

// Co-organisers
Route::get('events/{event}/members', [TeamController::class, 'index']);
Route::post('events/{event}/members', [TeamController::class, 'store'])->middleware('throttle:30,1');
Route::patch('events/{event}/members/{member}', [TeamController::class, 'update']);
Route::delete('events/{event}/members/{member}', [TeamController::class, 'destroy']);

Route::get('messaging/status', [MessagingController::class, 'status']);
