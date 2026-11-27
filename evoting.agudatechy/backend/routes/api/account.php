<?php

/*
| The signed-in organiser's own account: profile, two-step login, data rights. Loaded by routes/api.php inside the signed-in group.
*/

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('me', [AuthController::class, 'me']);
Route::patch('me', [AuthController::class, 'updateProfile']);
Route::post('me/two-factor', [AuthController::class, 'twoFactorSetup'])->middleware('throttle:6,1');
Route::post('me/two-factor/confirm', [AuthController::class, 'twoFactorConfirm'])->middleware('throttle:12,1');
Route::delete('me/two-factor', [AuthController::class, 'twoFactorDisable'])->middleware('throttle:6,1');
Route::get('me/two-factor', [AuthController::class, 'twoFactorStatus']);
Route::post('me/two-factor/recovery-codes', [AuthController::class, 'twoFactorRecoveryCodes'])->middleware('throttle:6,1');
Route::post('me/onboarded', [AuthController::class, 'onboarded']);

// Data rights
Route::get('me/export', [AccountController::class, 'export'])->middleware('throttle:6,1');
Route::delete('me', [AccountController::class, 'destroy'])->middleware('throttle:6,1');
