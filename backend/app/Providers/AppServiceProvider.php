<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Login / signup: slow down credential stuffing.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Code validation and vote submission: limit guesses per IP per event.
        // Kept generous enough for many voters behind one shared network (e.g. a campus lab).
        RateLimiter::for('voting', fn (Request $request) => Limit::perMinute(30)
            ->by($request->ip() . '|' . $request->route('token')));

        // Public ballot / results reads (results pages refresh on each live vote).
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));
    }
}
