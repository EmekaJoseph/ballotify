<?php

use App\Models\Candidate;
use App\Models\Category;
use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Models\VotePurchase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config([
        'ballotify.billing.enabled' => true,
        'ballotify.billing.providers' => 'paystack,flutterwave',
        'services.paystack.secret_key' => 'sk_test_secret',
        'services.paystack.base_url' => 'https://api.paystack.co',
        'services.flutterwave.secret_key' => 'FLWSECK_TEST-x',
        'services.flutterwave.webhook_hash' => 'flw-hash',
        'services.flutterwave.base_url' => 'https://api.flutterwave.com/v3',
    ]);
});

function pfPaidEvent(): array
{
    $owner = User::factory()->create(['paystack_subaccount_code' => 'ACCT_org']);
    $event = Event::create([
        'user_id' => $owner->id, 'name' => 'Awards', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->subHour(), 'ends_at' => now()->addDay(), 'results_visibility' => 'live',
        'voting_mode' => 'paid', 'vote_price_naira' => 50,
    ]);
    $cat = Category::create(['event_id' => $event->id, 'name' => 'Best Voice']);
    $ada = Candidate::create(['event_id' => $event->id, 'category_id' => $cat->id, 'name' => 'Ada']);
    return [$event, $ada];
}

function flwTx(string $ref, float $naira, string $status = 'successful'): array
{
    return ['status' => 'success', 'data' => [
        'status' => $status, 'tx_ref' => $ref, 'amount' => $naira, 'currency' => 'NGN',
        'payment_type' => 'bank_transfer', 'created_at' => now()->toIso8601String(), 'processor_response' => 'Approved',
    ]];
}

it('sends payers to Flutterwave when Paystack is down, and counts the votes', function () {
    [$event, $ada] = pfPaidEvent();
    Http::fake([
        'api.paystack.co/*' => Http::response(['status' => false, 'message' => 'Service unavailable'], 503),
        'api.flutterwave.com/v3/payments' => Http::response(['status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/pay/abc']]),
    ]);

    $this->postJson("/api/public/events/{$event->link_token}/paid-votes", ['candidate_id' => $ada->id, 'quantity' => 2, 'email' => 'fan@example.com', 'name' => 'Fan'])
        ->assertOk()->assertJsonPath('authorization_url', 'https://checkout.flutterwave.com/pay/abc');

    $purchase = VotePurchase::first();
    // No Paystack split through the backup: the organiser's share is owed instead.
    expect($purchase->provider)->toBe('flutterwave')->and($purchase->subaccount)->toBeNull();
    Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'flutterwave.com/v3/payments')
        && $r['tx_ref'] === $purchase->reference && (float) $r['amount'] === 100.0 && $r['customer']['email'] === 'fan@example.com'
        && str_contains($r['redirect_url'], "/vote/{$event->link_token}?ref={$purchase->reference}"));

    // While Paystack is marked down, the next payer goes straight to Flutterwave.
    $this->postJson("/api/public/events/{$event->link_token}/paid-votes", ['candidate_id' => $ada->id, 'quantity' => 1, 'email' => 'b@example.com'])->assertOk();
    $paystackCalls = collect(Http::recorded())->filter(fn ($pair) => str_contains($pair[0]->url(), 'paystack.co'))->count();
    expect($paystackCalls)->toBe(2); // the first payer's attempt and its retry only

    Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response(flwTx($purchase->reference, 100))]);
    $this->postJson('/api/public/paid-votes/verify', ['reference' => $purchase->reference])->assertOk()->assertJsonPath('status', 'success');
    expect($ada->fresh()->votes)->toBe(2)->and($purchase->fresh()->channel)->toBe('bank transfer');

    $this->actingAs($event->user)->getJson("/api/events/{$event->id}/earnings")->assertOk()
        ->assertJsonPath('gross_naira', 100)->assertJsonPath('net_naira', 90)->assertJsonPath('auto_paid_naira', 0);
});

it('keeps a Flutterwave payment pending until the payer actually pays', function () {
    [$event, $ada] = pfPaidEvent();
    $p = VotePurchase::create(['event_id' => $event->id, 'candidate_id' => $ada->id, 'candidate_name' => 'Ada', 'quantity' => 1,
        'amount_kobo' => 5000, 'reference' => 'PV-FLWNOTYET', 'provider' => 'flutterwave', 'email' => 'fan@example.com']);
    Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response(['status' => 'error', 'message' => 'No transaction was found for this id', 'data' => null], 400)]);

    $this->postJson('/api/public/paid-votes/verify', ['reference' => 'PV-FLWNOTYET'])->assertOk()->assertJsonPath('status', 'pending');
    expect($p->fresh()->status)->toBe('pending')->and($ada->fresh()->votes)->toBe(0);
});

it('rejects a Flutterwave payment for less than the price', function () {
    [$event, $ada] = pfPaidEvent();
    $p = VotePurchase::create(['event_id' => $event->id, 'candidate_id' => $ada->id, 'candidate_name' => 'Ada', 'quantity' => 4,
        'amount_kobo' => 20000, 'reference' => 'PV-FLWLOW', 'provider' => 'flutterwave', 'email' => 'fan@example.com']);
    Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response(flwTx('PV-FLWLOW', 50))]);

    $this->postJson('/api/public/paid-votes/verify', ['reference' => 'PV-FLWLOW'])->assertOk()->assertJsonPath('status', 'failed');
    expect($ada->fresh()->votes)->toBe(0);
});

it('settles from the Flutterwave webhook only with the right secret hash', function () {
    [$event, $ada] = pfPaidEvent();
    VotePurchase::create(['event_id' => $event->id, 'candidate_id' => $ada->id, 'candidate_name' => 'Ada', 'quantity' => 3,
        'amount_kobo' => 15000, 'reference' => 'PV-FLWHOOK', 'provider' => 'flutterwave', 'email' => 'fan@example.com']);
    // A Paystack purchase is never settled through Flutterwave's webhook.
    VotePurchase::create(['event_id' => $event->id, 'candidate_id' => $ada->id, 'candidate_name' => 'Ada', 'quantity' => 9,
        'amount_kobo' => 45000, 'reference' => 'PV-PSKONLY', 'provider' => 'paystack', 'email' => 'fan@example.com']);
    Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => fn (HttpRequest $r) => Http::response(flwTx($r['tx_ref'], $r['tx_ref'] === 'PV-FLWHOOK' ? 150 : 450))]);

    $hook = fn (string $ref, string $hash) => $this->postJson('/api/flutterwave/webhook', ['event' => 'charge.completed', 'data' => ['tx_ref' => $ref, 'status' => 'successful']], ['verif-hash' => $hash]);
    $hook('PV-FLWHOOK', 'wrong')->assertStatus(401);
    expect($ada->fresh()->votes)->toBe(0);

    $hook('PV-FLWHOOK', 'flw-hash')->assertOk();
    $hook('PV-FLWHOOK', 'flw-hash')->assertOk(); // retries are harmless
    $hook('PV-PSKONLY', 'flw-hash')->assertOk();
    expect($ada->fresh()->votes)->toBe(3);
});

it('upgrades an event through Flutterwave when it is the only provider', function () {
    config(['services.paystack.secret_key' => null]);
    $owner = User::factory()->create();
    $event = Event::create(['user_id' => $owner->id, 'name' => 'AGM', 'link_token' => (string) Str::uuid(),
        'starts_at' => now()->addDay(), 'ends_at' => now()->addDays(2)]);
    Http::fake([
        'api.flutterwave.com/v3/payments' => Http::response(['status' => 'success', 'data' => ['link' => 'https://checkout.flutterwave.com/pay/up']]),
    ]);

    $this->actingAs($owner)->getJson('/api/billing/plans')->assertJsonPath('payments_enabled', true);
    $this->actingAs($owner)->postJson("/api/events/{$event->id}/upgrade")->assertOk()->assertJsonPath('authorization_url', 'https://checkout.flutterwave.com/pay/up');
    $payment = Payment::first();
    expect($payment->provider)->toBe('flutterwave');

    Http::fake(['api.flutterwave.com/v3/transactions/verify_by_reference*' => Http::response(flwTx($payment->reference, $payment->amount_kobo / 100))]);
    $this->actingAs($owner)->postJson('/api/billing/verify', ['reference' => $payment->reference])->assertOk()->assertJsonPath('status', 'success');
    expect($event->fresh()->plan)->toBe('pro');
});

it('says so when no provider can take the payment', function () {
    [$event, $ada] = pfPaidEvent();
    Http::fake([
        'api.paystack.co/*' => Http::response(['status' => false], 500),
        'api.flutterwave.com/*' => Http::response(['status' => 'error', 'message' => 'down'], 500),
    ]);
    $this->postJson("/api/public/events/{$event->link_token}/paid-votes", ['candidate_id' => $ada->id, 'quantity' => 1, 'email' => 'fan@example.com'])
        ->assertStatus(502);
    expect(VotePurchase::first()->status)->toBe('failed');

    // Nothing configured: paid voting shows as unavailable.
    config(['services.paystack.secret_key' => null, 'services.flutterwave.secret_key' => null]);
    $this->getJson("/api/public/events/{$event->link_token}")->assertJsonPath('payments_enabled', false);
});
