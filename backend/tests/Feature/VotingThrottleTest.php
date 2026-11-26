<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\User;
use App\Models\Voter;
use App\Support\CodeGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** An open codes election with $voters codes V0000001, V0000002, ... */
function busyElection(int $voters): array
{
    $event = Event::create([
        'user_id' => User::factory()->create()->id, 'name' => 'AGM', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'results_visibility' => 'live',
    ]);
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Chairman']);
    $cand = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    Voter::insert(collect(range(1, $voters))->map(fn ($i) => [
        'event_id' => $event->id, 'code' => sprintf('V%07d', $i), 'created_at' => now(), 'updated_at' => now(),
    ])->all());

    return [$event, $cat, $cand];
}

it('lets a full hall on one shared network vote at once, typos included', function () {
    [$event, $cat, $cand] = busyElection(300);
    $t = $event->link_token;

    // 300 people on the same IP address, each entering their code and voting within the minute;
    // one in ten mistypes their code first.
    foreach (range(1, 300) as $i) {
        $code = sprintf('V%07d', $i);
        if ($i % 10 === 0) {
            $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'WRONG' . $i])->assertStatus(422);
        }
        $this->postJson("/api/public/events/{$t}/code/validate", ['code' => $code])->assertOk();
        $this->postJson("/api/public/events/{$t}/vote", ['code' => $code, 'choices' => [['category_id' => $cat->id, 'candidate_id' => $cand->id]]])->assertOk();
    }

    expect($cand->fresh()->votes)->toBe(300)
        ->and(Voter::whereNull('used_at')->count())->toBe(0);
});

it('stops someone guessing codes, without blocking other networks', function () {
    [$event] = busyElection(3);
    $t = $event->link_token;
    $guess = fn (string $code, string $ip = '10.0.0.1') => $this->withServerVariables(['REMOTE_ADDR' => $ip])
        ->postJson("/api/public/events/{$t}/code/validate", ['code' => $code]);

    foreach (range(1, CodeGuard::MAX_MISSES) as $i) {
        $guess(sprintf('G%07d', $i))->assertStatus(422);
    }
    // Blocked now, even with a real code, with a clear message and how long to wait.
    $guess('V0000001')->assertStatus(429)->assertJsonStructure(['message', 'retry_after'])->assertHeader('Retry-After');
    // A different network is unaffected.
    $guess('V0000001', '10.0.0.2')->assertOk();
});

it('gives a busy network more room for typos as real codes come in', function () {
    [$event] = busyElection(5);
    $t = $event->link_token;
    $post = fn (string $code) => $this->postJson("/api/public/events/{$t}/code/validate", ['code' => $code]);

    foreach (range(1, 5) as $i) {
        $post(sprintf('V%07d', $i))->assertOk(); // 5 correct codes: 5 more typos allowed
    }
    foreach (range(1, CodeGuard::MAX_MISSES + 5) as $i) {
        $post(sprintf('G%07d', $i))->assertStatus(422);
    }
    $post('G9999999')->assertStatus(429);
});

it('behind a proxy, sees each voter\'s real IP when TRUSTED_PROXIES is set', function () {
    [$event] = busyElection(1);
    $t = $event->link_token;
    // Every request arrives from the proxy (127.0.0.1); the visitor is in X-Forwarded-For.
    $via = fn (string $client, string $code) => $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
        ->withHeader('X-Forwarded-For', $client)
        ->postJson("/api/public/events/{$t}/code/validate", ['code' => $code]);

    config(['app.trusted_proxies' => '127.0.0.1']);
    (new \App\Providers\AppServiceProvider($this->app))->boot();
    try {
        foreach (range(1, CodeGuard::MAX_MISSES) as $i) {
            $via('41.58.10.1', sprintf('G%07d', $i))->assertStatus(422);
        }
        $via('41.58.10.1', 'V0000001')->assertStatus(429); // the guesser is blocked…
        $via('105.112.3.7', 'V0000001')->assertOk();      // …another voter behind the same proxy is not
    } finally {
        \Illuminate\Http\Middleware\TrustProxies::flushState();
    }
});

it('limits how often one code can be tried', function () {
    [$event] = busyElection(1);
    $t = $event->link_token;
    foreach (range(1, 10) as $i) {
        $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'V0000001'])->assertOk();
    }
    // Same code an 11th time within the minute: the route limiter (no retry_after body: the app retries).
    $this->postJson("/api/public/events/{$t}/code/validate", ['code' => 'v0000001'])->assertStatus(429)->assertJsonMissing(['retry_after']);
});
