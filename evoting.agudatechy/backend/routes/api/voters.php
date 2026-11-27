<?php

/*
| The voter list: codes, imports, groups, invitations, and resetting votes.
| Loaded by routes/api.php inside the signed-in group.
*/

use App\Http\Controllers\Api\VoteResetController;
use App\Http\Controllers\Api\VoterController;
use Illuminate\Support\Facades\Route;

Route::get('events/{event}/voters', [VoterController::class, 'index']);
Route::post('events/{event}/voters', [VoterController::class, 'store']);
Route::patch('events/{event}/voters/{voter}', [VoterController::class, 'update']);
Route::delete('events/{event}/voters/{voter}', [VoterController::class, 'destroy']);
Route::post('events/{event}/voters/bulk-delete', [VoterController::class, 'bulkDestroy'])->middleware('throttle:30,1');
Route::post('events/{event}/voters/import', [VoterController::class, 'import']);
Route::post('events/{event}/voters/generate', [VoterController::class, 'generate']);
Route::post('events/{event}/voters/erase-contacts', [VoterController::class, 'eraseContacts']);
Route::post('events/{event}/voters/{voter}/shared', [VoterController::class, 'markShared']);

// Sending messages requires a confirmed email address (stops spam from throwaway accounts).
Route::post('events/{event}/voters/invite', [VoterController::class, 'invite'])->middleware(['verified', 'throttle:20,1']);
Route::post('events/{event}/voters/remind', [VoterController::class, 'remind'])->middleware(['verified', 'throttle:10,1']);

// Test runs: erase every vote and result (owner only; refused once closed, paid or certified).
Route::post('events/{event}/reset-votes', [VoteResetController::class, 'store'])->middleware('throttle:6,1');
