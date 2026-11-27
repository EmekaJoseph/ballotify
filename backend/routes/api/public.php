<?php

/*
| Public pages (no sign-in): ballots, voting, results, paid votes, nominations,
| candidate self-service, certificate checks. Loaded by routes/api.php under /api/public.
*/

use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\CandidateProfileController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\NominationController;
use App\Http\Controllers\Api\PaidVoteController;
use App\Http\Controllers\Api\SelfRegistrationController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\VoterController;
use Illuminate\Support\Facades\Route;

Route::get('plans', [BillingController::class, 'publicPlans'])->middleware('throttle:public');
Route::get('events/{token}', [EventController::class, 'publicShow'])->middleware('throttle:public');
Route::get('events/{token}/results', [EventController::class, 'results'])->middleware('throttle:public');

// Voting with a code ('voting' limiter: per-IP flood ceiling + per-code limit; wrong codes: App\Support\CodeGuard).
Route::post('events/{token}/code/validate', [VoterController::class, 'validateCode'])->middleware('throttle:voting');
Route::get('events/{token}/candidates/{candidate}/photo', [EventController::class, 'candidatePhoto'])->whereNumber('candidate')->middleware('throttle:public');
Route::post('events/{token}/vote', [VoteController::class, 'store'])->middleware('throttle:voting');
Route::post('events/{token}/receipt', [EventController::class, 'checkReceipt'])->middleware('throttle:voting');

// Paid public voting
Route::post('events/{token}/paid-votes', [PaidVoteController::class, 'checkout'])->middleware('throttle:30,1');
Route::post('paid-votes/verify', [PaidVoteController::class, 'verify'])->middleware('throttle:60,1');

// Voter self-registration: code sent to the contact the organiser has on file.
Route::post('events/{token}/request-code', [SelfRegistrationController::class, 'requestCode'])->middleware('throttle:voting');

// Nominations
Route::get('events/{token}/nominations', [NominationController::class, 'form'])->middleware('throttle:public');
Route::post('events/{token}/nominations', [NominationController::class, 'store'])->middleware('throttle:20,1');
// The whole form at once (every category, several names each).
Route::post('events/{token}/nominations/batch', [NominationController::class, 'storeMany'])->middleware('throttle:20,1');

// Candidate self-service (private link)
Route::get('candidate-profile/{token}', [CandidateProfileController::class, 'publicShow'])->middleware('throttle:public');
Route::post('candidate-profile/{token}', [CandidateProfileController::class, 'publicUpdate'])->middleware('throttle:20,1');

// Results certificates
Route::get('certificates/{serial}', [CertificateController::class, 'verify'])->middleware('throttle:public');
