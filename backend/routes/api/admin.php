<?php

/*
| Platform owner (Admin page). Loaded by routes/api.php inside the signed-in group,
| under the /api/admin prefix and the superadmin middleware.
*/

use App\Http\Controllers\Api\PlatformAdminController;
use Illuminate\Support\Facades\Route;

Route::get('overview', [PlatformAdminController::class, 'overview']);
Route::get('users', [PlatformAdminController::class, 'users']);
Route::post('users/{user}/suspend', [PlatformAdminController::class, 'suspend']);
Route::post('users/{user}/unsuspend', [PlatformAdminController::class, 'unsuspend']);
Route::post('users/{user}/pro', [PlatformAdminController::class, 'setAccountPro']);
Route::get('events', [PlatformAdminController::class, 'events']);
Route::post('events/{event}/plan', [PlatformAdminController::class, 'setEventPlan']);
Route::get('plans', [PlatformAdminController::class, 'plans']);
Route::put('plans', [PlatformAdminController::class, 'updatePlans']);
Route::get('payments', [PlatformAdminController::class, 'payments']);
Route::get('payouts', [PlatformAdminController::class, 'payouts']);
Route::post('events/{event}/payouts', [PlatformAdminController::class, 'recordPayout']);
Route::get('health', [PlatformAdminController::class, 'health']);
Route::post('health/failed-jobs/{uuid}/retry', [PlatformAdminController::class, 'retryFailedJob']);
Route::delete('health/failed-jobs/{uuid}', [PlatformAdminController::class, 'forgetFailedJob']);
