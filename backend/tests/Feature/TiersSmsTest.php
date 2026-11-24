<?php

use App\Jobs\SendVoterInvitation;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voter;
use App\Services\Sms\SmsSender;
use App\Support\Plans;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** Counts SMS instead of sending them; can be told to fail. */
class TsFakeSms implements SmsSender
{
    public array $sent = [];
    public bool $fail = false;
    public function send(string $to, string $message): void
    {
        if ($this->fail) throw new RuntimeException('provider down');
        $this->sent[] = compact('to', 'message');
    }
    public function isLive(): bool { return true; }
    public function name(): string { return 'Fake'; }
}

beforeEach(function () {
    Mail::fake();
    config([
        'ballotify.billing.enabled' => true,
        'services.paystack.secret_key' => 'sk_test_secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);
    $this->sms = new TsFakeSms();
    $this->app->instance(SmsSender::class, $this->sms);
    // Paystack confirms whichever payment is asked about, for its full amount.
    Http::fake([
        'api.paystack.co/transaction/initialize' => Http::response(['status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/x']]),
        'api.paystack.co/transaction/verify/*' => function ($request) {
            $ref = urldecode(basename(parse_url($request->url(), PHP_URL_PATH)));
            $p = Payment::where('reference', $ref)->first();
            return Http::response(['status' => true, 'data' => [
                'status' => 'success', 'reference' => $ref, 'amount' => $p?->amount_kobo ?? 0, 'currency' => 'NGN', 'channel' => 'card',
            ]]);
        },
    ]);
});

function tsEvent(array $attrs = []): Event
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    return Event::create(array_merge([
        'user_id' => $user->id, 'name' => 'Annual Elections', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(),
    ], $attrs));
}

/** Pretend Paystack confirmed this payment. */
function tsPay($test, Payment $payment): void
{
    $test->actingAs($payment->user)->postJson('/api/billing/verify', ['reference' => $payment->reference])->assertOk()->assertJsonPath('status', 'success');
}

it('sells Pro in three sizes, and bigger sizes for the difference', function () {
    $event = tsEvent();
    $as = $this->actingAs($event->user);
    $as->getJson('/api/billing/plans')->assertOk()
        ->assertJsonPath('tiers.0.key', 'small')->assertJsonPath('tiers.0.price_naira', 10000)->assertJsonPath('tiers.0.max_voters', 200)
        ->assertJsonPath('tiers.1.price_naira', 25000)->assertJsonPath('tiers.1.max_voters', 1000)
        ->assertJsonPath('tiers.2.price_naira', 60000)->assertJsonPath('tiers.2.max_voters', 5000)
        ->assertJsonPath('sms_price_naira', 8);

    // Small: ₦10,000.
    $as->postJson("/api/events/{$event->id}/upgrade", ['tier' => 'small'])->assertOk();
    $small = Payment::latest('id')->first();
    expect($small->amount_kobo)->toBe(1_000_000)->and($small->tier)->toBe('small');
    tsPay($this, $small);
    $event->refresh();
    expect($event->plan)->toBe('pro')->and($event->pro_tier)->toBe('small')->and($event->sms_included)->toBe(200);
    $as->getJson("/api/events/{$event->id}")->assertJsonPath('limits.tier', 'small')->assertJsonPath('limits.max_voters', 200)
        ->assertJsonPath('limits.sms.included', 200)->assertJsonPath('limits.sms.left', 200);

    // Same size or smaller: refused. Bigger: pay the difference (₦25,000 - ₦10,000).
    $as->postJson("/api/events/{$event->id}/upgrade", ['tier' => 'small'])->assertStatus(409);
    $as->postJson("/api/events/{$event->id}/upgrade", ['tier' => 'standard'])->assertOk();
    $up = Payment::latest('id')->first();
    expect($up->amount_kobo)->toBe(1_500_000);
    tsPay($this, $up);
    expect($event->fresh()->pro_tier)->toBe('standard')->and($event->fresh()->sms_included)->toBe(1000);

    // A size must fit the voters already on the list.
    $big = tsEvent(['user_id' => $event->user_id]);
    Voter::insert(collect(range(1, 201))->map(fn ($i) => ['event_id' => $big->id, 'code' => "C{$i}XYZ", 'created_at' => now(), 'updated_at' => now()])->all());
    $as->postJson("/api/events/{$big->id}/upgrade", ['tier' => 'small'])->assertStatus(422);
});

it('caps voters by size and points to the next size up', function () {
    $event = tsEvent();
    $event->forceFill(['plan' => 'pro', 'pro_tier' => 'small', 'sms_included' => 200])->save();
    Voter::insert(collect(range(1, 200))->map(fn ($i) => ['event_id' => $event->id, 'code' => "V{$i}ABCD", 'created_at' => now(), 'updated_at' => now()])->all());

    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters", ['name' => 'One more'])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'voters')->assertJsonPath('upgrade', true)
        ->assertJsonFragment(['message' => 'Pro Small events can have up to 200 voters and this event is full. Upgrade this event to Pro Medium for up to 1,000 voters.']);
});

it('sells SMS credits for the account', function () {
    $event = tsEvent();
    $this->actingAs($event->user)->postJson('/api/billing/sms-credits', ['quantity' => 10])->assertStatus(422); // too few
    $this->actingAs($event->user)->postJson('/api/billing/sms-credits', ['quantity' => 500, 'event_id' => $event->id])
        ->assertOk()->assertJsonPath('authorization_url', 'https://checkout.paystack.com/x');
    Http::assertSent(fn ($r) => str_contains($r->url(), 'initialize') && str_contains($r['callback_url'], "/dashboard/events/{$event->id}?tab=voters&payment=1"));

    $p = Payment::latest('id')->first();
    expect($p->kind)->toBe('sms')->and($p->sms_credits)->toBe(500)->and($p->amount_kobo)->toBe(500 * 8 * 100);
    tsPay($this, $p);
    expect($event->user->fresh()->sms_credits)->toBe(500);
    // Verifying again (or the webhook) never adds them twice.
    $this->actingAs($event->user)->postJson('/api/billing/verify', ['reference' => $p->reference])->assertOk();
    expect($event->user->fresh()->sms_credits)->toBe(500);
});

it('uses the event’s SMS first, then credits, and refuses when short', function () {
    $event = tsEvent();
    $event->forceFill(['plan' => 'pro', 'pro_tier' => 'small', 'sms_included' => 3])->save();
    foreach (range(1, 5) as $i) {
        Voter::create(['event_id' => $event->id, 'code' => "PHON{$i}XX", 'phone' => '+23480000000' . $i, 'email' => "v{$i}@example.com"]);
    }
    $send = fn (string $channel) => $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => $channel, 'audience' => 'pending']);

    // 5 SMS needed, 3 included, 0 credits: refused, nothing taken.
    $send('sms')->assertStatus(402)->assertJsonPath('plan_limit', 'sms')->assertJsonPath('sms_short', 2)->assertJsonPath('sms_available', 3);
    expect($event->fresh()->sms_used)->toBe(0)->and($this->sms->sent)->toBeEmpty();

    // Email is free.
    $send('email')->assertOk();
    expect($event->fresh()->sms_used)->toBe(0);

    // With 2 credits it goes through: 3 from the event, 2 from credits.
    $event->user->forceFill(['sms_credits' => 2])->save();
    $send('sms')->assertOk()->assertJsonPath('sms', 5);
    expect($event->fresh()->sms_used)->toBe(3)->and($event->user->fresh()->sms_credits)->toBe(0)->and($this->sms->sent)->toHaveCount(5);
    expect(collect($this->sms->sent)->every(fn ($m) => mb_strlen($m['message']) <= 160))->toBeTrue();
});

it('gives an undelivered SMS back, and falls back to email when nothing is left', function () {
    $event = tsEvent();
    $event->forceFill(['plan' => 'pro', 'pro_tier' => 'small', 'sms_included' => 2, 'sms_used' => 2])->save();
    $voter = Voter::create(['event_id' => $event->id, 'code' => 'REFUND01', 'phone' => '+2348000000001', 'email' => 'r@example.com']);

    // A failed delivery is refunded to the event.
    $this->sms->fail = true;
    (new SendVoterInvitation($voter->id, ['sms'], 'invite', true))->handle($this->sms);
    expect($event->fresh()->sms_used)->toBe(1);

    // Scheduled reminders don't fail on SMS: with none left they go by email only.
    Event::whereKey($event->id)->update(['sms_used' => 2]);
    $out = \App\Http\Controllers\Api\VoterController::sendMessages($event->fresh(), Voter::where('event_id', $event->id), 'both', 'reminder', false);
    expect($out['sms'])->toBe(0)->and($out['email'])->toBe(1);
});

it('never charges SMS when billing is off or on the platform owner’s events', function () {
    $event = tsEvent();
    $event->user->forceFill(['is_admin' => true])->save();
    Voter::create(['event_id' => $event->id, 'code' => 'ADMIN001', 'phone' => '+2348000000001']);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'sms', 'audience' => 'pending'])->assertOk();
    expect($event->fresh()->sms_used)->toBe(0)->and($this->sms->sent)->toHaveCount(1);

    config(['ballotify.billing.enabled' => false]);
    expect(Plans::reserveSms(tsEvent(), 1000))->toBe(1000);
});

it('lets the platform admin give an event a size', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $event = tsEvent();
    $this->actingAs($admin)->postJson("/api/admin/events/{$event->id}/plan", ['plan' => 'pro', 'tier' => 'small'])->assertOk()->assertJsonPath('pro_tier', 'small');
    expect($event->fresh()->sms_included)->toBe(200);
    $this->actingAs($admin)->postJson("/api/admin/events/{$event->id}/plan", ['plan' => 'pro', 'tier' => 'large'])->assertOk();
    expect($event->fresh()->pro_tier)->toBe('large')->and($event->fresh()->sms_included)->toBe(5000);
});

it('keeps every SMS to one message', function () {
    $long = str_repeat('Very Long Organisation Name ', 6);
    $url = 'https://vote.example-organisation.com/vote/' . Str::uuid();
    expect(mb_strlen(SendVoterInvitation::smsText($long, 'ABCD-EFGH-IJ', $url)))->toBeLessThanOrEqual(160)
        ->and(mb_strlen(SendVoterInvitation::smsText($long, 'ABCD-EFGH-IJ', $url, true, now()->addDay())))->toBeLessThanOrEqual(160)
        ->and(SendVoterInvitation::smsText('Club', 'CODE1234', 'https://b.ng/vote/x'))->toBe('Club: your voting code is CODE1234. Vote at https://b.ng/vote/x');
});
