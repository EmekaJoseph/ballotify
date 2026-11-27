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
// New accounts can create events once approved.
Route::post('users/{user}/approval', [PlatformAdminController::class, 'setApproval']);
Route::get('events', [PlatformAdminController::class, 'events']);
Route::get('health', [PlatformAdminController::class, 'health']);
Route::post('health/failed-jobs/{uuid}/retry', [PlatformAdminController::class, 'retryFailedJob']);
Route::delete('health/failed-jobs/{uuid}', [PlatformAdminController::class, 'forgetFailedJob']);
