<?php

use Illuminate\Support\Facades\Route;

// This app is an API. The website lives in the separate Nuxt frontend.
Route::get('/', fn () => response()->json([
    'name' => config('app.name') . ' API',
    'frontend' => config('app.frontend_url'),
    'docs' => 'See backend/docs/API.md',
]));
