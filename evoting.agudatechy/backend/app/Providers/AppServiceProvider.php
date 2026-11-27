<?php

namespace App\Providers;

use App\Services\Sms\SmsManager;
use App\Services\Sms\SmsSender;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
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
        // Resolved per use so a config change (or a test override) takes effect.
        $this->app->bind(SmsSender::class, fn () => SmsManager::make());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // See config/app.php: the proxies whose forwarded client IP is believed.
        if ($proxies = trim((string) config('app.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        // Login / signup: slow down credential stuffing.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // Code entry and vote submission. A whole hall can share one IP address (venue
        // Wi-Fi, or a mobile network), so the per-IP ceiling is high: it only stops floods.
        // Guessing is stopped separately by App\Support\CodeGuard, which counts WRONG codes
        // only; and each single code gets its own small limit (scripts hammering one ballot).
        RateLimiter::for('voting', function (Request $request) {
            $token = (string) $request->route('token');
            $limits = [Limit::perMinute(1200)->by('ip|' . $request->ip() . '|' . $token)];
            $code = $request->input('code');
            if (is_string($code) && $code !== '') {
                $limits[] = Limit::perMinute(10)->by('code|' . $token . '|' . sha1(\App\Http\Controllers\Api\VoterController::normalizeCode($code)));
            }
            return $limits;
        });

        // Public pages: ballot, candidate photos (one request each), live results.
        // High enough for a full hall on one network loading every photo at once.
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(1000)->by($request->ip()));
    }
}
