<?php

/*
| Paying for plans and SMS credits (organisers). Paystack's webhooks are in webhooks.php.
| Loaded by routes/api.php inside the signed-in group.
*/

use App\Http\Controllers\Api\BillingController;
use Illuminate\Support\Facades\Route;

Route::get('billing/plans', [BillingController::class, 'plans']);
Route::post('events/{event}/upgrade', [BillingController::class, 'checkout'])->middleware('throttle:10,1');
Route::post('billing/sms-credits', [BillingController::class, 'smsCheckout'])->middleware('throttle:10,1');
Route::post('billing/verify', [BillingController::class, 'verify'])->middleware('throttle:30,1');
Route::get('billing/payments', [BillingController::class, 'myPayments']);
Route::get('billing/payments/{reference}', [BillingController::class, 'receipt']);
