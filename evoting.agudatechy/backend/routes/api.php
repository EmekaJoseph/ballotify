<?php

/*
| API routes, one file per area in routes/api/. This file only sets the shared
| prefixes and middleware; each area file lists its own endpoints.
|
|   api/auth.php       /api/auth/*            sign-in, sign-up, password, email
|   api/account.php    signed in              profile, two-step login, data rights, payouts
|   api/events.php     signed in              events, results, team, nominations, certificates
|   api/ballot.php     signed in              categories, candidates
|   api/voters.php     signed in              voter list, invitations, reset votes
|   api/admin.php      /api/admin/*           platform owner only
|   api/public.php     /api/public/*          voting pages, results, nominations
*/

use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(__DIR__ . '/api/auth.php');

// Signed in. scopeBindings(): a {category}/{candidate}/{voter}/{member} in the URL must
// belong to the {event} in the same URL, otherwise Laravel answers 404.
Route::middleware(['auth:sanctum', 'active'])->scopeBindings()->group(function () {
    require __DIR__ . '/api/account.php';
    require __DIR__ . '/api/events.php';
    require __DIR__ . '/api/ballot.php';
    require __DIR__ . '/api/voters.php';

    Route::prefix('admin')->middleware('superadmin')->group(__DIR__ . '/api/admin.php');
});

Route::prefix('public')->group(__DIR__ . '/api/public.php');
