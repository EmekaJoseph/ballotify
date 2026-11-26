<?php

/*
| Calls from outside services: USSD (Africa's Talking) and payment providers.
| No sign-in: each checks its own secret or signature. Loaded by routes/api.php.
*/

use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\UssdController;
use Illuminate\Support\Facades\Route;

// The secret in the path stands in for a signature (Africa's Talking doesn't sign USSD calls).
Route::post('ussd/{secret}', [UssdController::class, 'handle'])->middleware('throttle:240,1');

Route::post('paystack/webhook', [BillingController::class, 'webhook'])->middleware('throttle:120,1');
Route::post('flutterwave/webhook', [BillingController::class, 'flutterwaveWebhook'])->middleware('throttle:120,1');
