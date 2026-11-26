<?php

/*
| The ballot: categories (and motions) and candidates.
| Loaded by routes/api.php inside the signed-in group.
*/

use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\CandidateProfileController;
use App\Http\Controllers\Api\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get('events/{event}/categories', [CategoryController::class, 'index']);
Route::post('events/{event}/categories', [CategoryController::class, 'store']);
Route::patch('events/{event}/categories/{category}', [CategoryController::class, 'update']);
Route::delete('events/{event}/categories/{category}', [CategoryController::class, 'destroy']);

Route::get('events/{event}/candidates', [CandidateController::class, 'index']);
Route::post('events/{event}/candidates', [CandidateController::class, 'store']);
// Keep before candidates/{candidate}: both answer POST, and the first match wins.
Route::post('events/{event}/candidates/import', [CandidateController::class, 'import']);
// POST alias: browsers cannot send multipart/form-data (image upload) with PATCH to PHP.
Route::match(['patch', 'post'], 'events/{event}/candidates/{candidate}', [CandidateController::class, 'update']);
Route::delete('events/{event}/candidates/{candidate}', [CandidateController::class, 'destroy']);
Route::get('events/{event}/candidates/{candidate}/profile-link', [CandidateProfileController::class, 'show']);
Route::delete('events/{event}/candidates/{candidate}/profile-link', [CandidateProfileController::class, 'revoke']);
