<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CandidateController;
use App\Http\Controllers\Api\VoterController;
use App\Http\Controllers\Api\VoteController;
use App\Http\Controllers\Api\MessagingController;
use App\Http\Controllers\Api\PlatformAdminController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\PaidVoteController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\CandidateProfileController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\NominationController;
use App\Http\Controllers\Api\SelfRegistrationController;
use App\Http\Controllers\Api\UssdController;

// ---------------------------------------------------------------- authentication
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
        Route::post('two-factor', [AuthController::class, 'twoFactorChallenge']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });

    // Link from the verification email (signature covers path + query only).
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed:relative', 'throttle:12,1'])
        ->name('verification.verify');

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('email/resend', [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');
    });
});

// ---------------------------------------------------------------- organiser area
// scopeBindings(): a {category}/{candidate}/{voter} in the URL must belong to the
// {event} in the same URL, otherwise Laravel answers 404.
Route::middleware(['auth:sanctum', 'active'])->scopeBindings()->group(function () {
    Route::get('me', [AuthController::class, 'me']);
    Route::patch('me', [AuthController::class, 'updateProfile']);
    Route::post('me/two-factor', [AuthController::class, 'twoFactorSetup'])->middleware('throttle:6,1');
    Route::post('me/two-factor/confirm', [AuthController::class, 'twoFactorConfirm'])->middleware('throttle:12,1');
    Route::delete('me/two-factor', [AuthController::class, 'twoFactorDisable'])->middleware('throttle:6,1');
    Route::get('me/two-factor', [AuthController::class, 'twoFactorStatus']);
    Route::post('me/two-factor/recovery-codes', [AuthController::class, 'twoFactorRecoveryCodes'])->middleware('throttle:6,1');
    Route::post('me/onboarded', [AuthController::class, 'onboarded']);
    // Data rights + payout account (automatic payouts via Paystack)
    Route::get('me/export', [AccountController::class, 'export'])->middleware('throttle:6,1');
    Route::delete('me', [AccountController::class, 'destroy'])->middleware('throttle:6,1');
    Route::get('billing/banks', [AccountController::class, 'banks']);
    Route::post('billing/resolve-account', [AccountController::class, 'resolve'])->middleware('throttle:20,1');
    Route::put('me/payout-account', [AccountController::class, 'savePayoutAccount'])->middleware('throttle:10,1');

    Route::get('event-templates', [EventController::class, 'templates']);

    Route::get('events', [EventController::class, 'index']);
    Route::post('events', [EventController::class, 'store']);
    Route::get('events/{event}', [EventController::class, 'show']);
    Route::patch('events/{event}', [EventController::class, 'update']);
    Route::delete('events/{event}', [EventController::class, 'destroy']);
    Route::get('events/{event}/voting-link', [EventController::class, 'link']);
    Route::get('events/{event}/results', [EventController::class, 'adminResults']);
    Route::get('events/{event}/activity', [EventController::class, 'activity']);
    Route::get('events/{event}/earnings', [EventController::class, 'earnings']);
    // POST: carries a logo upload (multipart).
    Route::post('events/{event}/branding', [EventController::class, 'branding']);

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

    Route::get('events/{event}/categories', [CategoryController::class, 'index']);
    Route::post('events/{event}/categories', [CategoryController::class, 'store']);
    Route::patch('events/{event}/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('events/{event}/categories/{category}', [CategoryController::class, 'destroy']);

    Route::get('events/{event}/candidates', [CandidateController::class, 'index']);
    Route::post('events/{event}/candidates', [CandidateController::class, 'store']);
    Route::post('events/{event}/candidates/import', [CandidateController::class, 'import']);
    // POST alias: browsers cannot send multipart/form-data (image upload) with PATCH to PHP.
    Route::match(['patch', 'post'], 'events/{event}/candidates/{candidate}', [CandidateController::class, 'update']);
    Route::delete('events/{event}/candidates/{candidate}', [CandidateController::class, 'destroy']);
    Route::get('events/{event}/candidates/{candidate}/profile-link', [CandidateProfileController::class, 'show']);
    Route::delete('events/{event}/candidates/{candidate}/profile-link', [CandidateProfileController::class, 'revoke']);

    Route::get('events/{event}/voters', [VoterController::class, 'index']);
    Route::post('events/{event}/voters', [VoterController::class, 'store']);
    Route::patch('events/{event}/voters/{voter}', [VoterController::class, 'update']);
    Route::delete('events/{event}/voters/{voter}', [VoterController::class, 'destroy']);
    Route::post('events/{event}/voters/import', [VoterController::class, 'import']);
    Route::get('events/{event}/voter-groups', [VoterController::class, 'groups']);
    Route::post('events/{event}/voters/generate', [VoterController::class, 'generate']);
    Route::post('events/{event}/voters/erase-contacts', [VoterController::class, 'eraseContacts']);
    Route::post('events/{event}/voters/{voter}/shared', [VoterController::class, 'markShared']);
    Route::post('events/{event}/voters/numeric-codes', [VoterController::class, 'numericCodes']);
    // Sending messages requires a confirmed email address (stops spam from throwaway accounts).
    Route::post('events/{event}/duplicate', [EventController::class, 'duplicate'])->middleware('throttle:20,1');
    Route::post('events/{event}/voters/invite', [VoterController::class, 'invite'])->middleware(['verified', 'throttle:20,1']);
    Route::post('events/{event}/voters/remind', [VoterController::class, 'remind'])->middleware(['verified', 'throttle:10,1']);

    Route::get('messaging/status', [MessagingController::class, 'status']);

    // Billing (pay per event)
    Route::get('billing/plans', [BillingController::class, 'plans']);
    Route::post('events/{event}/upgrade', [BillingController::class, 'checkout'])->middleware('throttle:10,1');
    Route::post('billing/sms-credits', [BillingController::class, 'smsCheckout'])->middleware('throttle:10,1');
    Route::post('billing/verify', [BillingController::class, 'verify'])->middleware('throttle:30,1');
    Route::get('billing/payments', [BillingController::class, 'myPayments']);
    Route::get('billing/payments/{reference}', [BillingController::class, 'receipt']);

    // ------------------------------------------------------------ platform owner
    Route::prefix('admin')->middleware('superadmin')->group(function () {
        Route::get('overview', [PlatformAdminController::class, 'overview']);
        Route::get('users', [PlatformAdminController::class, 'users']);
        Route::post('users/{user}/suspend', [PlatformAdminController::class, 'suspend']);
        Route::post('users/{user}/unsuspend', [PlatformAdminController::class, 'unsuspend']);
        Route::get('events', [PlatformAdminController::class, 'events']);
        Route::post('events/{event}/plan', [PlatformAdminController::class, 'setEventPlan']);
        Route::post('users/{user}/pro', [PlatformAdminController::class, 'setAccountPro']);
        Route::get('plans', [PlatformAdminController::class, 'plans']);
        Route::put('plans', [PlatformAdminController::class, 'updatePlans']);
        Route::get('payments', [PlatformAdminController::class, 'payments']);
        Route::get('payouts', [PlatformAdminController::class, 'payouts']);
        Route::post('events/{event}/payouts', [PlatformAdminController::class, 'recordPayout']);
        Route::get('health', [PlatformAdminController::class, 'health']);
        Route::post('health/failed-jobs/{uuid}/retry', [PlatformAdminController::class, 'retryFailedJob']);
        Route::delete('health/failed-jobs/{uuid}', [PlatformAdminController::class, 'forgetFailedJob']);
    });
});

// ---------------------------------------------------------------- USSD (Africa's Talking)
// The secret in the path stands in for a signature (Africa's Talking doesn't sign USSD calls).
Route::post('ussd/{secret}', [UssdController::class, 'handle'])->middleware('throttle:240,1');

// ---------------------------------------------------------------- payments provider
Route::post('paystack/webhook', [BillingController::class, 'webhook'])->middleware('throttle:120,1');
Route::post('flutterwave/webhook', [BillingController::class, 'flutterwaveWebhook'])->middleware('throttle:120,1');

// ---------------------------------------------------------------- public (voters)
Route::prefix('public')->group(function () {
    Route::get('plans', [BillingController::class, 'publicPlans'])->middleware('throttle:public');
    Route::get('events/{token}', [EventController::class, 'publicShow'])->middleware('throttle:public');
    Route::get('events/{token}/results', [EventController::class, 'results'])->middleware('throttle:public');
    // Code guessing endpoints are tightly limited per IP to stop brute forcing.
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
    // Candidate self-service (private link)
    Route::get('candidate-profile/{token}', [CandidateProfileController::class, 'publicShow'])->middleware('throttle:public');
    Route::post('candidate-profile/{token}', [CandidateProfileController::class, 'publicUpdate'])->middleware('throttle:20,1');
    // Results certificates
    Route::get('certificates/{serial}', [CertificateController::class, 'verify'])->middleware('throttle:public');
});
