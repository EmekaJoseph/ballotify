<?php

use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Models\Voter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ballotify.billing.enabled' => true,
        'services.paystack.secret_key' => 'sk_test_secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
    ]);
});

function billingEvent(?User $user = null, array $attrs = []): Event
{
    $user ??= User::factory()->create();
    return Event::create(array_merge([
        'user_id' => $user->id,
        'name' => 'Club vote',
        'link_token' => (string) Str::uuid(),
    ], $attrs));
}

function paystackTx(Payment $p, array $over = []): array
{
    return array_merge([
        'status' => 'success', 'reference' => $p->reference, 'amount' => $p->amount_kobo,
        'currency' => 'NGN', 'channel' => 'card',
    ], $over);
}

// ------------------------------------------------------------------ free limits

it('allows unlimited drafts but only one live free event at a time', function () {
    $user = User::factory()->create();
    $first = $this->actingAs($user)->postJson('/api/events', ['name' => 'First'])->assertCreated()
        ->assertJsonPath('limits.plan', 'free')->assertJsonPath('limits.max_voters', 30)->json('id');
    // Drafts (no voters yet) are always allowed.
    $second = $this->actingAs($user)->postJson('/api/events', ['name' => 'Second'])->assertCreated()->json('id');
    $this->actingAs($user)->postJson('/api/events', ['name' => 'Third'])->assertCreated();

    // The first event goes live when it gets voters...
    $this->actingAs($user)->postJson("/api/events/{$first}/voters/generate", ['count' => 3])->assertCreated();
    // ...so a second one can't, until the first closes.
    $this->actingAs($user)->postJson("/api/events/{$second}/voters/generate", ['count' => 3])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'events');
    // The live event itself can keep adding voters.
    $this->actingAs($user)->postJson("/api/events/{$first}/voters", ['name' => 'Ada'])->assertCreated();

    Event::find($first)->update(['ends_at' => now()->subMinute()]);
    $this->actingAs($user)->postJson("/api/events/{$second}/voters/generate", ['count' => 3])->assertCreated();
});

it('tells the dashboard which events use the live allowance', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->getJson('/api/billing/plans')
        ->assertJsonPath('event_quota.can_create', true)->assertJsonPath('event_quota.can_go_live', true)
        ->assertJsonPath('event_quota.max', 1)->assertJsonCount(0, 'event_quota.open_events');

    billingEvent($user, ['name' => 'Draft only']);
    $live = billingEvent($user, ['name' => 'Choir awards']);
    Voter::create(['event_id' => $live->id, 'code' => 'LIVE0001']);
    $this->actingAs($user)->getJson('/api/billing/plans')
        ->assertJsonPath('event_quota.can_create', true)
        ->assertJsonPath('event_quota.can_go_live', false)
        ->assertJsonCount(1, 'event_quota.open_events')
        ->assertJsonPath('event_quota.open_events.0.name', 'Choir awards');

    $user->forceFill(['pro_account_at' => now()])->save();
    $this->actingAs($user->fresh())->getJson('/api/billing/plans')
        ->assertJsonPath('event_quota.can_go_live', true)->assertJsonPath('event_quota.max', null);
});

it('does not count Pro or paid-voting events against the free limit', function () {
    $user = User::factory()->create();
    $pro = billingEvent($user);
    $pro->forceFill(['plan' => 'pro'])->save();
    Voter::create(['event_id' => $pro->id, 'code' => 'PRO00001']);
    $paid = billingEvent($user, ['voting_mode' => 'paid', 'vote_price_naira' => 50]);

    $draft = billingEvent($user);
    $this->actingAs($user)->postJson("/api/events/{$draft->id}/voters/generate", ['count' => 2])->assertCreated();
    expect($paid->fresh()->voting_mode)->toBe('paid');
});

it('caps voters on free events and lifts the cap on Pro', function () {
    $event = billingEvent();
    $user = $event->user;

    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 31])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'voters')->assertJsonPath('upgrade', true);
    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/generate", ['count' => 30])->assertCreated();
    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters", ['name' => 'One more'])->assertStatus(402);
    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters/import", ['voters' => [['name' => 'X']]])->assertStatus(402);

    $event->forceFill(['plan' => 'pro'])->save();
    $this->actingAs($user)->postJson("/api/events/{$event->id}/voters", ['name' => 'One more'])->assertCreated();
});

it('blocks email and SMS invitations on free events', function () {
    $event = billingEvent();
    Voter::create(['event_id' => $event->id, 'code' => 'AAAA2222', 'email' => 'a@example.com']);
    $event->user->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/voters/invite", ['channel' => 'email', 'audience' => 'not_invited'])
        ->assertStatus(402)->assertJsonPath('plan_limit', 'messaging');
});

it('shows “Powered by Ballotify” only on free ballots', function () {
    $event = billingEvent();
    $this->getJson("/api/public/events/{$event->link_token}")->assertOk()->assertJsonPath('branding', true);
    $event->forceFill(['plan' => 'pro'])->save();
    $this->getJson("/api/public/events/{$event->link_token}")->assertOk()->assertJsonPath('branding', false);
});

it('treats every event as Pro when billing is disabled', function () {
    config(['ballotify.billing.enabled' => false]);
    $user = User::factory()->create();
    $this->actingAs($user)->postJson('/api/events', ['name' => 'A'])->assertCreated()->assertJsonPath('limits.max_voters', null);
    $this->actingAs($user)->postJson('/api/events', ['name' => 'B'])->assertCreated();
});

// ------------------------------------------------------------------ checkout

it('starts a Paystack checkout for the configured price', function () {
    Http::fake(['api.paystack.co/transaction/initialize' => Http::response([
        'status' => true, 'data' => ['authorization_url' => 'https://checkout.paystack.com/abc'],
    ])]);
    $event = billingEvent();

    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/upgrade")
        ->assertOk()->assertJsonPath('authorization_url', 'https://checkout.paystack.com/abc');

    $payment = Payment::first();
    expect($payment->status)->toBe('pending')->and($payment->amount_kobo)->toBe(4_500_000);
    Http::assertSent(fn ($req) => $req['amount'] === 4_500_000 && $req['reference'] === $payment->reference
        && $req->hasHeader('Authorization', 'Bearer sk_test_secret'));
});

it('answers clearly when payments are not configured, without breaking', function () {
    config(['services.paystack.secret_key' => null]);
    $event = billingEvent();
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/upgrade")
        ->assertStatus(503)->assertJsonPath('payments_enabled', false);
    $this->actingAs($event->user)->getJson('/api/billing/plans')->assertOk()->assertJsonPath('payments_enabled', false);
});

it('refuses to sell an upgrade for a closed or already-Pro event', function () {
    $event = billingEvent(attrs: ['ends_at' => now()->subDay()]);
    $this->actingAs($event->user)->postJson("/api/events/{$event->id}/upgrade")->assertStatus(422);
    $pro = billingEvent();
    $pro->forceFill(['plan' => 'pro'])->save();
    $this->actingAs($pro->user)->postJson("/api/events/{$pro->id}/upgrade")->assertStatus(409);
});

// ------------------------------------------------------------------ verification

it('upgrades the event once Paystack confirms the payment', function () {
    $event = billingEvent();
    $payment = Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'reference' => 'BLT-T1', 'amount_kobo' => 1_000_000, 'currency' => 'NGN']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => paystackTx($payment)])]);

    $this->actingAs($event->user)->postJson('/api/billing/verify', ['reference' => 'BLT-T1'])
        ->assertOk()->assertJsonPath('status', 'success');

    expect($event->fresh()->plan)->toBe('pro')->and($event->fresh()->upgraded_via)->toBe('payment')
        ->and($payment->fresh()->paid_at)->not->toBeNull();

    // Verifying again is harmless.
    $this->actingAs($event->user)->postJson('/api/billing/verify', ['reference' => 'BLT-T1'])->assertOk()->assertJsonPath('status', 'success');
    Http::assertSentCount(1);
});

it('does not upgrade when the amount paid is less than the price', function () {
    $event = billingEvent();
    $payment = Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'reference' => 'BLT-T2', 'amount_kobo' => 1_000_000, 'currency' => 'NGN']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => paystackTx($payment, ['amount' => 100])])]);

    $this->actingAs($event->user)->postJson('/api/billing/verify', ['reference' => 'BLT-T2'])->assertJsonPath('status', 'failed');
    expect($event->fresh()->plan)->toBe('free');
});

it('keeps an abandoned checkout pending so it can still be paid', function () {
    $event = billingEvent();
    $payment = Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'reference' => 'BLT-T3', 'amount_kobo' => 1_000_000, 'currency' => 'NGN']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => paystackTx($payment, ['status' => 'abandoned'])])]);

    $this->actingAs($event->user)->postJson('/api/billing/verify', ['reference' => 'BLT-T3'])->assertJsonPath('status', 'pending');
});

it('does not let one organiser verify another’s payment', function () {
    $event = billingEvent();
    Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'reference' => 'BLT-T4', 'amount_kobo' => 1_000_000, 'currency' => 'NGN']);
    $this->actingAs(User::factory()->create())->postJson('/api/billing/verify', ['reference' => 'BLT-T4'])->assertNotFound();
});

// ------------------------------------------------------------------ webhook

it('upgrades from a signed Paystack webhook and ignores unsigned ones', function () {
    $event = billingEvent();
    $payment = Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'reference' => 'BLT-W1', 'amount_kobo' => 1_000_000, 'currency' => 'NGN']);
    Http::fake(['api.paystack.co/transaction/verify/*' => Http::response(['status' => true, 'data' => paystackTx($payment)])]);
    $body = json_encode(['event' => 'charge.success', 'data' => ['reference' => 'BLT-W1', 'amount' => 1_000_000]]);

    $this->call('POST', '/api/paystack/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => 'forged'], $body)
        ->assertStatus(401);
    expect($event->fresh()->plan)->toBe('free');

    $sig = hash_hmac('sha512', $body, 'sk_test_secret');
    $this->call('POST', '/api/paystack/webhook', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => $sig], $body)
        ->assertOk();
    expect($event->fresh()->plan)->toBe('pro');
});

// ------------------------------------------------------------------ admin

it('lets the admin upgrade an event or a whole account without payment', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $event = billingEvent();

    $this->actingAs($admin)->postJson("/api/admin/events/{$event->id}/plan", ['plan' => 'pro'])
        ->assertOk()->assertJsonPath('upgraded_via', 'admin');
    expect($event->fresh()->plan)->toBe('pro');

    $owner = User::factory()->create();
    $this->actingAs($admin)->postJson("/api/admin/users/{$owner->id}/pro", ['enabled' => true])->assertOk();
    $owner = $owner->fresh(); // a real request loads the account fresh
    // A Pro account has no free-event limit and its events are Pro.
    $this->actingAs($owner)->postJson('/api/events', ['name' => 'A'])->assertCreated()
        ->assertJsonPath('limits.plan', 'pro')->assertJsonPath('limits.via_account', true);
    $this->actingAs($owner)->postJson('/api/events', ['name' => 'B'])->assertCreated();
});

it('lets the admin change prices and limits, which apply straight away', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();

    $this->actingAs($admin)->putJson('/api/admin/plans', ['pro_price_naira' => 25000, 'free_max_voters' => 10])
        ->assertOk()->assertJsonPath('pro_price_naira', 25000)->assertJsonPath('free_max_voters', 10);
    $this->getJson('/api/public/plans')->assertOk()
        ->assertJsonPath('pro_price_naira', 25000)->assertJsonPath('free.max_voters', 10);

    $this->actingAs($admin)->putJson('/api/admin/plans', ['pro_price_naira' => 5])->assertStatus(422);
    $this->actingAs(User::factory()->create())->putJson('/api/admin/plans', ['pro_price_naira' => 1])->assertNotFound();
});

it('lists payments and revenue for the admin', function () {
    $admin = User::factory()->create();
    $admin->forceFill(['is_admin' => true])->save();
    $event = billingEvent();
    Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'event_name' => 'Club vote', 'reference' => 'BLT-P1', 'amount_kobo' => 1_500_000, 'currency' => 'NGN', 'status' => 'success', 'paid_at' => now()]);
    Payment::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'reference' => 'BLT-P2', 'amount_kobo' => 1_500_000, 'currency' => 'NGN', 'status' => 'failed']);

    $this->actingAs($admin)->getJson('/api/admin/payments?status=success')->assertOk()->assertJsonPath('total', 1)
        ->assertJsonPath('data.0.reference', 'BLT-P1');
    $this->actingAs($admin)->getJson('/api/admin/overview')->assertOk()->assertJsonPath('revenue_naira', 15000);
});
